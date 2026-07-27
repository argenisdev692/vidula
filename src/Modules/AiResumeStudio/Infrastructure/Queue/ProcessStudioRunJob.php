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
use Modules\AiResumeStudio\Domain\Ports\GithubPortfolioPort;
use Modules\AiResumeStudio\Domain\Ports\JobMatchRepositoryPort;
use Modules\AiResumeStudio\Domain\Ports\JobPageScraperPort;
use Modules\AiResumeStudio\Domain\Ports\OutreachDraftRepositoryPort;
use Modules\AiResumeStudio\Domain\Ports\RefinedCvRepositoryPort;
use Modules\AiResumeStudio\Domain\Ports\StudioRunRepositoryPort;
use Modules\AiResumeStudio\Domain\Services\CanonicalUrlNormalizer;
use Modules\AiResumeStudio\Infrastructure\Ai\ResumeStudioAiService;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\GithubEnrichmentEloquentModel;
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

    public function handle(
        StudioRunRepositoryPort $runs,
        RefinedCvRepositoryPort $refinedCvs,
        JobMatchRepositoryPort $jobMatches,
        OutreachDraftRepositoryPort $drafts,
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

        try {
            $runs->update($run, [
                'status' => StudioRunStatus::Running->value,
                'started_at' => $run->started_at ?? now(),
            ]);

            $cv = $run->cv;
            $githubContext = '';

            if ($run->mode === StudioMode::Career) {
                $runs->update($run, ['step' => StudioRunStep::Enriching->value]);
                $enrichment = GithubEnrichmentEloquentModel::query()
                    ->where('cv_id', $run->cv_id)
                    ->where('user_id', $run->user_id)
                    ->latest('id')
                    ->first();

                if ($enrichment !== null) {
                    $repos = $github->listRepos($enrichment->github_username, $enrichment->selected_repos ?? []);
                    $enrichment->update([
                        'repos_summary' => $repos,
                        'last_synced_at' => now(),
                    ]);
                    $githubContext = json_encode($repos, JSON_THROW_ON_ERROR);
                }
            }

            $runs->update($run, ['step' => StudioRunStep::Refining->value]);
            $resumeLanguage = $this->resolveResumeLanguage($run);
            $refinePrompt = $this->buildRefinePrompt($run, (string) ($cv?->raw_text ?? ''), $githubContext, $resumeLanguage);
            $refined = $ai->refineCv($refinePrompt, $provider);

            $refinedCv = $refinedCvs->create([
                'user_id' => $run->user_id,
                'cv_id' => $run->cv_id,
                'studio_run_id' => $run->id,
                'mode' => $run->mode->value,
                'target_job_title' => $refined['target_job_title'] ?: ($meta['target_job_title'] ?? null),
                'resume_language' => $resumeLanguage->value,
                'provider' => $provider,
                'ats_score' => min(100, max(0, $refined['ats_score'])),
                'refined_md' => $refined['refined_md'],
                'feedback' => $refined['feedback'],
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

            $runs->update($run, [
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

    private function buildRefinePrompt(
        StudioRunEloquentModel $run,
        string $rawCv,
        string $githubContext,
        ResumeLanguage $resumeLanguage,
    ): string {
        $meta = (array) ($run->meta ?? []);

        return implode("\n\n", array_filter([
            'MODE: '.$run->mode->value,
            $resumeLanguage->outputInstruction(),
            isset($meta['target_job_title']) && $meta['target_job_title'] !== ''
              ? 'TARGET ROLE: '.$meta['target_job_title']
              : null,
            isset($meta['targeting_prompt']) && $meta['targeting_prompt'] !== ''
              ? 'TARGETING BRIEF:\n'.$meta['targeting_prompt']
              : null,
            isset($meta['location_scope']) ? 'LOCATION SCOPE: '.$meta['location_scope'] : null,
            isset($meta['search_language']) ? 'PREFERRED JOB LANGUAGE: '.$meta['search_language'] : null,
            isset($meta['keywords']) && $meta['keywords'] !== ''
              ? 'KEYWORDS: '.$meta['keywords']
              : null,
            $githubContext !== '' ? "GITHUB EVIDENCE (selected projects only):\n{$githubContext}" : null,
            'HARD RULE REMINDER: Never invent metrics, employers, or skills. ats_score is a heuristic.',
            "SOURCE CV:\n{$rawCv}",
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
