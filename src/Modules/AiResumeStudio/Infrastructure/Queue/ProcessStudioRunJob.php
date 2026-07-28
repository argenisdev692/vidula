<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\AiResumeStudio\Domain\Enums\JobMatchSource;
use Modules\AiResumeStudio\Domain\Enums\LocationScope;
use Modules\AiResumeStudio\Domain\Enums\OutreachKind;
use Modules\AiResumeStudio\Domain\Enums\OutreachStatus;
use Modules\AiResumeStudio\Domain\Enums\ResumeLanguage;
use Modules\AiResumeStudio\Domain\Enums\SearchLanguage;
use Modules\AiResumeStudio\Domain\Enums\StudioMode;
use Modules\AiResumeStudio\Domain\Enums\StudioRunStatus;
use Modules\AiResumeStudio\Domain\Enums\StudioRunStep;
use Modules\AiResumeStudio\Domain\Ports\GithubEnrichmentRepositoryPort;
use Modules\AiResumeStudio\Domain\Ports\GithubPortfolioPort;
use Modules\AiResumeStudio\Domain\Ports\JobMatchRepositoryPort;
use Modules\AiResumeStudio\Domain\Ports\JobPageScraperPort;
use Modules\AiResumeStudio\Domain\Ports\OutreachDraftRepositoryPort;
use Modules\AiResumeStudio\Domain\Ports\RefinedCvRepositoryPort;
use Modules\AiResumeStudio\Domain\Ports\StudioRunRepositoryPort;
use Modules\AiResumeStudio\Domain\Services\CanonicalUrlNormalizer;
use Modules\AiResumeStudio\Infrastructure\Ai\ResumeStudioAiService;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\JobMatchEloquentModel;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\JobSearchConfigEloquentModel;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\StudioRunEloquentModel;
use Shared\Infrastructure\Research\TavilyClientInterface;
use Throwable;

#[Queue('default')]
#[Tries(1)]
#[Timeout(300)]
final class ProcessStudioRunJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly string $studioRunUuid) {}

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('resume-studio-run:'.$this->studioRunUuid))
                ->expireAfter(360)
                ->dontRelease(),
        ];
    }

    public function handle(
        StudioRunRepositoryPort $runs,
        RefinedCvRepositoryPort $refinedCvs,
        JobMatchRepositoryPort $jobMatches,
        OutreachDraftRepositoryPort $drafts,
        GithubEnrichmentRepositoryPort $githubEnrichments,
        ResumeStudioAiService $ai,
        TavilyClientInterface $tavily,
        GithubPortfolioPort $github,
        JobPageScraperPort $scraper,
    ): void {
        $run = $runs->findByUuid($this->studioRunUuid);

        if ($run === null) {
            Log::warning('resume_studio.run_missing', ['uuid' => $this->studioRunUuid]);

            return;
        }

        $meta = (array) ($run->meta ?? []);
        $provider = (string) ($meta['provider'] ?? 'openai');
        $keywords = (string) ($meta['keywords'] ?? '');
        $deepExtract = (bool) ($meta['deep_extract'] ?? false);
        $phase = (string) ($meta['pipeline_phase'] ?? 'judge');

        try {
            $runs->update($run, [
                'status' => StudioRunStatus::Running->value,
                'started_at' => $run->started_at ?? now(),
            ]);

            $cv = $run->cv;
            $githubContext = '';
            $githubExtraPrompt = trim((string) ($meta['github_extra_prompt'] ?? ''));

            if ($run->mode === StudioMode::Career) {
                $runs->update($run, ['step' => StudioRunStep::Enriching->value]);
                $enrichment = $githubEnrichments->latestForUserCv((int) $run->user_id, (int) $run->cv_id);

                if ($enrichment !== null) {
                    $repos = $github->listRepos($enrichment->github_username, $enrichment->selected_repos ?? []);
                    $githubEnrichments->update($enrichment, [
                        'repos_summary' => $repos,
                        'last_synced_at' => now(),
                    ]);
                    $githubContext = json_encode($repos, JSON_THROW_ON_ERROR);

                    if ($githubExtraPrompt === '' && filled($enrichment->extra_prompt)) {
                        $githubExtraPrompt = trim((string) $enrichment->extra_prompt);
                    }
                }
            }

            $resumeLanguage = $this->resolveResumeLanguage($run);
            $rawCv = (string) ($cv?->raw_text ?? '');

            if ($phase !== 'rewrite' || ! isset($meta['audit']) || ! is_array($meta['audit'])) {
                $runs->update($run, ['step' => StudioRunStep::Judging->value]);
                $judgePrompt = $this->buildJudgePrompt($run, $rawCv, $githubContext, $githubExtraPrompt, $resumeLanguage);
                $audit = $ai->judgeCv($judgePrompt, $provider);

                $meta['audit'] = $audit;
                $meta['github_extra_prompt'] = $githubExtraPrompt !== '' ? $githubExtraPrompt : ($meta['github_extra_prompt'] ?? null);

                if ($audit['target_job_title'] !== '' && blank($meta['target_job_title'] ?? null)) {
                    $meta['target_job_title'] = $audit['target_job_title'];
                }

                $needsMetrics = $audit['metric_questions'] !== [];
                $alreadyAnswered = isset($meta['metric_answers']) || (bool) ($meta['skip_metrics'] ?? false);

                if ($needsMetrics && ! $alreadyAnswered) {
                    $meta['pipeline_phase'] = 'awaiting_metrics';
                    $runs->update($run, [
                        'meta' => $meta,
                        'step' => StudioRunStep::AwaitingMetrics->value,
                        'status' => StudioRunStatus::AwaitingInput->value,
                    ]);

                    return;
                }

                $meta['pipeline_phase'] = 'rewrite';
                $runs->update($run, ['meta' => $meta]);
                $run->refresh();
                $meta = (array) ($run->meta ?? []);
            }

            $runs->update($run, ['step' => StudioRunStep::Refining->value]);
            $rewritePrompt = $this->buildRewritePrompt(
                $run,
                $rawCv,
                $githubContext,
                $githubExtraPrompt,
                $resumeLanguage,
                (array) ($meta['audit'] ?? []),
                (array) ($meta['metric_answers'] ?? []),
                (bool) ($meta['skip_metrics'] ?? false),
            );
            $refined = $ai->rewriteCv($rewritePrompt, $provider);

            $judgeFeedback = (array) ($meta['audit'] ?? []);
            $rewriteFeedback = (array) ($refined['feedback'] ?? []);
            $mergedFeedback = [
                'strengths' => array_values(array_unique([
                    ...array_map('strval', (array) ($rewriteFeedback['strengths'] ?? [])),
                    ...array_map('strval', (array) ($judgeFeedback['strengths'] ?? [])),
                ])),
                'improvements' => array_values(array_unique([
                    ...array_map('strval', (array) ($rewriteFeedback['improvements'] ?? [])),
                    ...array_map('strval', (array) ($judgeFeedback['improvements'] ?? [])),
                ])),
                'keyword_gaps' => array_values(array_unique([
                    ...array_map('strval', (array) ($rewriteFeedback['keyword_gaps'] ?? [])),
                    ...array_map('strval', (array) ($judgeFeedback['keyword_gaps'] ?? [])),
                ])),
                'weak_lines' => array_values(array_unique([
                    ...array_map('strval', (array) ($rewriteFeedback['weak_lines'] ?? [])),
                    ...array_map('strval', (array) ($judgeFeedback['weak_lines'] ?? [])),
                ])),
                'xyz_gaps' => array_values(array_map('strval', (array) ($judgeFeedback['xyz_gaps'] ?? []))),
                'metric_questions' => (array) ($judgeFeedback['metric_questions'] ?? []),
            ];

            $refinedCv = $refinedCvs->create([
                'user_id' => $run->user_id,
                'cv_id' => $run->cv_id,
                'studio_run_id' => $run->id,
                'mode' => $run->mode->value,
                'target_job_title' => $refined['target_job_title']
                  ?: ($meta['target_job_title'] ?? null)
                  ?: ($judgeFeedback['target_job_title'] ?? null),
                'resume_language' => $resumeLanguage->value,
                'provider' => $provider,
                'ats_score' => min(100, max(0, $refined['ats_score'])),
                'refined_md' => $refined['refined_md'],
                'feedback' => $mergedFeedback,
                'version' => $refinedCvs->nextVersionForCv($run->cv_id),
            ]);

            $config = $run->job_search_config_id !== null
              ? JobSearchConfigEloquentModel::query()->find($run->job_search_config_id)
              : null;

            $searchKeywords = $keywords !== '' ? $keywords : (string) ($config?->keywords ?? '');
            $discoveredMatches = [];

            if ($searchKeywords !== '') {
                $runs->update($run, ['step' => StudioRunStep::Searching->value]);

                $locationScope = LocationScope::tryFrom(
                    (string) ($meta['location_scope'] ?? $config?->location_scope ?? 'remote'),
                );
                $searchLanguage = SearchLanguage::tryFrom(
                    (string) ($meta['search_language'] ?? $config?->search_language ?? 'both'),
                );

                $composedKeywords = trim(implode(' ', array_filter([
                    $searchKeywords,
                    $locationScope?->searchFragment(),
                    $searchLanguage?->searchFragment(),
                ], static fn (?string $part): bool => $part !== null && $part !== '')));

                $query = str_replace(
                    '{keywords}',
                    $composedKeywords,
                    (string) config('cv_studio.tavily.query_template'),
                );
                $results = $tavily->search([$query]);

                $runs->update($run, ['step' => StudioRunStep::Scoring->value]);
                $topN = (int) config('cv_studio.deep_extract_top_n');
                $index = 0;

                foreach ($results as $result) {
                    $jobUrl = (string) ($result['url'] ?? '');
                    if ($jobUrl === '') {
                        continue;
                    }

                    $canonical = CanonicalUrlNormalizer::normalize($jobUrl);
                    $rawMd = null;
                    $source = JobMatchSource::Tavily;

                    if ($deepExtract && $index < $topN) {
                        $scraped = $scraper->scrape($jobUrl);
                        $rawMd = $scraped['markdown'];
                        if ($rawMd !== null) {
                            $source = JobMatchSource::Firecrawl;
                        }
                        $index++;
                    }

                    $scorePrompt = $this->buildScorePrompt(
                        $refinedCv->refined_md,
                        (string) ($result['title'] ?? ''),
                        (string) ($result['content'] ?? ''),
                        $rawMd,
                    );
                    $scored = $ai->scoreMatch($scorePrompt, $provider);

                    $match = $jobMatches->upsertByCanonicalUrl($run->user_id, $canonical, [
                        'job_search_config_id' => $run->job_search_config_id,
                        'studio_run_id' => $run->id,
                        'job_title' => (string) ($result['title'] ?? 'Untitled role'),
                        'company_name' => $scored['company_name'],
                        'job_url' => $jobUrl,
                        'raw_snippet' => (string) ($result['content'] ?? ''),
                        'raw_md' => $rawMd,
                        'match_score' => min(100, max(0, $scored['match_score'])),
                        'match_reasoning' => $scored['match_reasoning'],
                        'source' => $source->value,
                        'first_seen_at' => now(),
                        'last_seen_at' => now(),
                    ]);

                    $discoveredMatches[] = $match;
                }
            }

            $runs->update($run, ['step' => StudioRunStep::Drafting->value]);

            $topMatch = array_first($discoveredMatches);
            if ($topMatch !== null) {
                $cover = $ai->draftCover(
                    $this->buildCoverPrompt($refinedCv->refined_md, $topMatch->job_title, $topMatch->raw_snippet ?? '', $resumeLanguage),
                    $provider,
                );
                $drafts->create([
                    'user_id' => $run->user_id,
                    'job_match_id' => $topMatch->id,
                    'studio_run_id' => $run->id,
                    'kind' => OutreachKind::Cover->value,
                    'subject' => $cover['subject'],
                    'body' => $cover['body'],
                    'language' => $cover['language'] ?: $resumeLanguage->value,
                    'status' => OutreachStatus::Draft->value,
                    'provider' => $provider,
                ]);
            }

            if ($discoveredMatches !== []) {
                $digest = $ai->draftDigest($this->buildDigestPrompt($discoveredMatches, $resumeLanguage), $provider);
                $drafts->create([
                    'user_id' => $run->user_id,
                    'studio_run_id' => $run->id,
                    'kind' => OutreachKind::Digest->value,
                    'subject' => $digest['subject'],
                    'body' => $digest['body'],
                    'language' => $digest['language'] ?: $resumeLanguage->value,
                    'status' => OutreachStatus::Draft->value,
                    'provider' => $provider,
                ]);
            }

            // auto_send_enabled defaults false — never dispatch mail here.
            if ($config?->auto_send_enabled === true) {
                Log::info('resume_studio.auto_send_skipped', [
                    'run_uuid' => $run->uuid,
                    'reason' => 'automated_send_not_implemented',
                ]);
            }

            $meta['pipeline_phase'] = 'completed';
            $runs->update($run, [
                'meta' => $meta,
                'step' => StudioRunStep::Completed->value,
                'status' => StudioRunStatus::Completed->value,
                'finished_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('resume_studio.run_failed', [
                'uuid' => $this->studioRunUuid,
                'error' => $e->getMessage(),
            ]);

            $runs->update($run, [
                'step' => StudioRunStep::Failed->value,
                'status' => StudioRunStatus::Failed->value,
                'error_summary' => mb_substr($e->getMessage(), 0, 1000),
                'finished_at' => now(),
            ]);
        }
    }

    private function resolveResumeLanguage(StudioRunEloquentModel $run): ResumeLanguage
    {
        $meta = (array) ($run->meta ?? []);
        $fromMeta = $meta['resume_language'] ?? null;

        if (is_string($fromMeta) && $fromMeta !== '') {
            return ResumeLanguage::tryFrom($fromMeta) ?? ResumeLanguage::English;
        }

        if ($run->job_search_config_id !== null) {
            $fromConfig = JobSearchConfigEloquentModel::query()
                ->whereKey($run->job_search_config_id)
                ->value('resume_language');

            if (is_string($fromConfig) && $fromConfig !== '') {
                return ResumeLanguage::tryFrom($fromConfig) ?? ResumeLanguage::English;
            }
        }

        return ResumeLanguage::English;
    }

    private function buildJudgePrompt(
        StudioRunEloquentModel $run,
        string $rawCv,
        string $githubContext,
        string $githubExtraPrompt,
        ResumeLanguage $resumeLanguage,
    ): string {
        return implode("\n\n", array_filter([
            'TASK: Audit the SOURCE CV only. Do not rewrite it.',
            'MODE: '.$run->mode->value,
            $resumeLanguage->outputInstruction(),
            ...$this->sharedContextBlocks($run, $githubContext, $githubExtraPrompt),
            'HARD RULE REMINDER: Never invent metrics, employers, or skills. Ask metric_questions only when honest estimates would help.',
            "SOURCE CV:\n{$rawCv}",
        ], static fn (?string $part): bool => $part !== null && $part !== ''));
    }

    /**
     * @param  array<string, mixed>  $audit
     * @param  list<array{id?: string, answer?: string}>  $metricAnswers
     */
    private function buildRewritePrompt(
        StudioRunEloquentModel $run,
        string $rawCv,
        string $githubContext,
        string $githubExtraPrompt,
        ResumeLanguage $resumeLanguage,
        array $audit,
        array $metricAnswers,
        bool $skipMetrics,
    ): string {
        $auditJson = json_encode($audit, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $answersJson = json_encode($metricAnswers, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        return implode("\n\n", array_filter([
            'TASK: Rewrite ONE ATS-optimized Markdown resume using the JUDGE AUDIT and METRIC ANSWERS.',
            'MODE: '.$run->mode->value,
            $resumeLanguage->outputInstruction(),
            ...$this->sharedContextBlocks($run, $githubContext, $githubExtraPrompt),
            "JUDGE AUDIT (JSON):\n{$auditJson}",
            $skipMetrics
              ? 'METRIC ANSWERS: Candidate skipped metric Q&A — do not invent numbers.'
              : "METRIC ANSWERS (JSON):\n{$answersJson}",
            'HARD RULE REMINDER: Never invent metrics, employers, or skills. ats_score is a heuristic.',
            "SOURCE CV:\n{$rawCv}",
        ], static fn (?string $part): bool => $part !== null && $part !== ''));
    }

    /**
     * @return list<string>
     */
    private function sharedContextBlocks(
        StudioRunEloquentModel $run,
        string $githubContext,
        string $githubExtraPrompt,
    ): array {
        $meta = (array) ($run->meta ?? []);

        return array_values(array_filter([
            isset($meta['target_job_title']) && $meta['target_job_title'] !== ''
              ? 'TARGET ROLE: '.$meta['target_job_title']
              : null,
            isset($meta['targeting_prompt']) && $meta['targeting_prompt'] !== ''
              ? "TARGETING BRIEF:\n".$meta['targeting_prompt']
              : null,
            isset($meta['job_description']) && is_string($meta['job_description']) && $meta['job_description'] !== ''
              ? "TARGET JOB DESCRIPTION (optional — mirror exact keywords naturally):\n".$meta['job_description']
              : null,
            isset($meta['location_scope']) ? 'LOCATION SCOPE: '.$meta['location_scope'] : null,
            isset($meta['search_language']) ? 'PREFERRED JOB LANGUAGE: '.$meta['search_language'] : null,
            isset($meta['keywords']) && $meta['keywords'] !== ''
              ? 'KEYWORDS: '.$meta['keywords']
              : null,
            $githubExtraPrompt !== ''
              ? "GITHUB / CAREER EXTRA PROMPT:\n{$githubExtraPrompt}"
              : null,
            $githubContext !== '' ? "GITHUB EVIDENCE (selected projects only):\n{$githubContext}" : null,
        ], static fn (?string $part): bool => $part !== null && $part !== ''));
    }

    private function buildScorePrompt(string $refinedMd, string $title, string $snippet, ?string $rawMd): string
    {
        $jobText = $rawMd ?? $snippet;

        return implode("\n\n", [
            'TASK: Score fit between the refined CV and this job posting.',
            "JOB TITLE: {$title}",
            "JOB TEXT:\n{$jobText}",
            "REFINED CV:\n{$refinedMd}",
            'REMINDER: match_score is heuristic. Cite only evidenced gaps and strengths.',
        ]);
    }

    private function buildCoverPrompt(
        string $refinedMd,
        string $jobTitle,
        string $snippet,
        ResumeLanguage $resumeLanguage,
    ): string {
        return implode("\n\n", [
            'TASK: Draft a cover / application message for manual copy-paste send.',
            $resumeLanguage->coverInstruction(),
            "ROLE: {$jobTitle}",
            "JOB SNIPPET:\n{$snippet}",
            "REFINED CV:\n{$refinedMd}",
            'REMINDER: Draft only — candidate will send manually. No invented experience.',
        ]);
    }

    /**
     * @param  list<JobMatchEloquentModel>  $matches
     */
    private function buildDigestPrompt(array $matches, ResumeLanguage $resumeLanguage): string
    {
        $lines = collect($matches)
            ->take(10)
            ->map(static fn ($m): string => sprintf(
                '- %s @ %s (heuristic score %d) url=%s — %s',
                $m->job_title,
                $m->company_name ?? 'Unknown',
                $m->match_score,
                $m->job_url,
                mb_substr((string) $m->match_reasoning, 0, 160),
            ))
            ->implode("\n");

        return implode("\n\n", [
            'TASK: Write a personal digest of these matches for the job seeker.',
            $resumeLanguage->digestInstruction(),
            "TOP MATCHES:\n{$lines}",
            'REMINDER: Digest for the candidate only. Do not invent jobs. Draft only.',
        ]);
    }
}
