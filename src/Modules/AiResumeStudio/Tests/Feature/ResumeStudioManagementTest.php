<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\AiResumeStudio\Domain\Enums\OutreachStatus;
use Modules\AiResumeStudio\Domain\Enums\StudioRunStatus;
use Modules\AiResumeStudio\Domain\Enums\StudioRunStep;
use Modules\AiResumeStudio\Domain\Services\CanonicalUrlNormalizer;
use Modules\AiResumeStudio\Infrastructure\Ai\AtsRewriteAgent;
use Modules\AiResumeStudio\Infrastructure\Ai\CoverDraftAgent;
use Modules\AiResumeStudio\Infrastructure\Ai\CvJudgeAgent;
use Modules\AiResumeStudio\Infrastructure\Ai\DigestDraftAgent;
use Modules\AiResumeStudio\Infrastructure\Ai\JobMatchScorerAgent;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\JobMatchEloquentModel;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\JobSearchConfigEloquentModel;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\OutreachDraftEloquentModel;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\RefinedCvEloquentModel;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\StudioRunEloquentModel;
use Modules\AiResumeStudio\Infrastructure\Queue\ProcessStudioRunJob;
use Modules\Cvs\Infrastructure\Persistence\Eloquent\Models\CvEloquentModel;
use Shared\Infrastructure\Research\TavilyClientInterface;
use Tests\TestCase;

final class ResumeStudioManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->fakeStudioAgents();
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');

        return $admin;
    }

    public function test_guest_and_user_without_permission_receive_403(): void
    {
        $plain = User::factory()->create();
        $plain->assignRole('USER');

        $this->get('/resume-studio')->assertRedirect('/login');

        $this->actingAs($plain)->get('/resume-studio')->assertForbidden();
        $this->actingAs($plain)->post('/resume-studio/runs', [
            'cv_uuid' => (string) Str::uuid7(),
            'mode' => 'career',
            'provider' => 'openai',
        ])->assertForbidden();
    }

    public function test_start_run_creates_studio_run_row(): void
    {
        Queue::fake();

        $admin = $this->superAdmin();
        $cv = CvEloquentModel::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->post('/resume-studio/runs', [
                'cv_uuid' => $cv->uuid,
                'mode' => 'career',
                'provider' => 'openai',
                'keywords' => 'laravel developer',
                'search_language' => 'en',
                'resume_language' => 'pt-PT',
                'job_description' => 'Looking for Laravel and Vue experience.',
                'github_extra_prompt' => 'Emphasize SaaS billing work.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('studio_runs', [
            'user_id' => $admin->id,
            'cv_id' => $cv->id,
            'mode' => 'career',
        ]);

        $this->assertDatabaseHas('job_search_configs', [
            'cv_id' => $cv->id,
            'resume_language' => 'pt-PT',
            'search_language' => 'en',
        ]);

        $run = StudioRunEloquentModel::query()->where('cv_id', $cv->id)->first();
        $this->assertNotNull($run);
        $this->assertSame('pt-PT', data_get($run->meta, 'resume_language'));
        $this->assertSame('Looking for Laravel and Vue experience.', data_get($run->meta, 'job_description'));
        $this->assertSame('Emphasize SaaS billing work.', data_get($run->meta, 'github_extra_prompt'));
        $this->assertSame('judge', data_get($run->meta, 'pipeline_phase'));
    }

    public function test_job_match_url_dedupe_normalizes_tracking_params(): void
    {
        $admin = $this->superAdmin();
        $cv = CvEloquentModel::factory()->create(['user_id' => $admin->id]);

        $urlA = 'https://Example.com/jobs/42?utm_source=linkedin';
        $urlB = 'https://example.com/jobs/42?utm_medium=email';

        $this->assertSame(
            CanonicalUrlNormalizer::normalize($urlA),
            CanonicalUrlNormalizer::normalize($urlB),
        );

        $this->mock(TavilyClientInterface::class, function ($mock) use ($urlA, $urlB): void {
            $mock->shouldReceive('search')->andReturn([
                ['title' => 'Laravel Dev', 'url' => $urlA, 'content' => 'Snippet A', 'score' => 0.9],
                ['title' => 'Laravel Dev duplicate', 'url' => $urlB, 'content' => 'Snippet B', 'score' => 0.8],
            ]);
        });

        $run = StudioRunEloquentModel::factory()->create([
            'user_id' => $admin->id,
            'cv_id' => $cv->id,
            'meta' => [
                'provider' => 'openai',
                'keywords' => 'laravel',
                'deep_extract' => false,
                'pipeline_phase' => 'judge',
            ],
        ]);

        $job = new ProcessStudioRunJob($run->uuid);
        app()->call([$job, 'handle']);

        $this->assertSame(1, JobMatchEloquentModel::query()->where('user_id', $admin->id)->count());
        $this->assertDatabaseHas('refined_cvs', ['cv_id' => $cv->id]);
    }

    public function test_judge_pauses_for_metric_questions_then_continues_after_submit(): void
    {
        $this->fakeStudioAgents(withMetricQuestions: true);

        $admin = $this->superAdmin();
        $cv = CvEloquentModel::factory()->create([
            'user_id' => $admin->id,
            'raw_text' => "Jane Doe\nBuilt Laravel APIs without metrics.",
        ]);

        $this->mock(TavilyClientInterface::class, function ($mock): void {
            $mock->shouldReceive('search')->andReturn([]);
        });

        $run = StudioRunEloquentModel::factory()->create([
            'user_id' => $admin->id,
            'cv_id' => $cv->id,
            'meta' => [
                'provider' => 'openai',
                'keywords' => '',
                'deep_extract' => false,
                'pipeline_phase' => 'judge',
                'job_description' => 'Senior Laravel engineer — APIs, queues, MySQL.',
                'github_extra_prompt' => 'Highlight Horizon experience.',
            ],
        ]);

        $job = new ProcessStudioRunJob($run->uuid);
        app()->call([$job, 'handle']);

        $run->refresh();
        $this->assertSame(StudioRunStatus::AwaitingInput, $run->status);
        $this->assertSame(StudioRunStep::AwaitingMetrics, $run->step);
        $this->assertNotEmpty(data_get($run->meta, 'audit.metric_questions'));
        $this->assertSame(0, RefinedCvEloquentModel::query()->where('cv_id', $cv->id)->count());

        $this->actingAs($admin)
            ->post("/resume-studio/runs/{$run->uuid}/metrics", [
                'metric_answers' => [
                    ['id' => 'q1', 'answer' => 'Cut deploy time from ~2 hours to ~30 minutes'],
                ],
                'skip_metrics' => false,
            ])
            ->assertRedirect();

        $run->refresh();
        $this->assertSame(StudioRunStatus::Completed, $run->status);
        $this->assertSame(StudioRunStep::Completed, $run->step);
        $this->assertDatabaseHas('refined_cvs', ['cv_id' => $cv->id]);
        $this->assertSame(
            'Cut deploy time from ~2 hours to ~30 minutes',
            data_get($run->meta, 'metric_answers.0.answer'),
        );
    }

    public function test_mark_draft_sent_manually(): void
    {
        $admin = $this->superAdmin();
        $draft = OutreachDraftEloquentModel::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->post("/resume-studio/drafts/{$draft->uuid}/mark-sent")
            ->assertRedirect();

        $draft->refresh();
        $this->assertSame(OutreachStatus::SentManually, $draft->status);
    }

    public function test_user_cannot_access_another_users_run_or_draft(): void
    {
        $owner = $this->superAdmin();
        $intruder = User::factory()->create();
        $intruder->assignRole('SUPER_ADMIN');

        $cv = CvEloquentModel::factory()->create(['user_id' => $owner->id]);
        $run = StudioRunEloquentModel::factory()->create([
            'user_id' => $owner->id,
            'cv_id' => $cv->id,
        ]);
        $draft = OutreachDraftEloquentModel::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($intruder)
            ->get("/resume-studio/runs/{$run->uuid}")
            ->assertNotFound();

        $this->actingAs($intruder)
            ->post("/resume-studio/drafts/{$draft->uuid}/mark-sent")
            ->assertNotFound();
    }

    public function test_schedule_command_starts_runs_for_enabled_configs(): void
    {
        $admin = $this->superAdmin();
        $cv = CvEloquentModel::factory()->create(['user_id' => $admin->id]);

        JobSearchConfigEloquentModel::factory()->scheduled()->create([
            'user_id' => $admin->id,
            'cv_id' => $cv->id,
            'auto_send_enabled' => false,
        ]);

        JobSearchConfigEloquentModel::factory()->create([
            'user_id' => $admin->id,
            'cv_id' => $cv->id,
            'schedule_enabled' => false,
        ]);

        Artisan::call('resume-studio:run-daily');

        $this->assertSame(1, StudioRunEloquentModel::query()->count());
        $this->assertStringContainsString('Started 1 scheduled studio run(s).', Artisan::output());
    }

    public function test_new_job_search_config_defaults_auto_send_to_false(): void
    {
        $admin = $this->superAdmin();
        $cv = CvEloquentModel::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)->post('/resume-studio/configs', [
            'cv_uuid' => $cv->uuid,
            'mode' => 'career',
            'keywords' => 'php developer',
            'provider' => 'openai',
            'status' => 'active',
            'search_language' => 'both',
            'resume_language' => 'es',
        ])->assertRedirect();

        $this->assertDatabaseHas('job_search_configs', [
            'cv_id' => $cv->id,
            'auto_send_enabled' => false,
            'resume_language' => 'es',
        ]);
    }

    public function test_refined_cv_pdf_downloads_for_owner(): void
    {
        $admin = $this->superAdmin();
        $cv = CvEloquentModel::factory()->create(['user_id' => $admin->id]);
        $refined = RefinedCvEloquentModel::factory()->create([
            'user_id' => $admin->id,
            'cv_id' => $cv->id,
            'refined_md' => "# Jane Doe\n\n## Summary\nBuilt Laravel APIs.\n\n## Experience\n- Shipped billing features for B2B SaaS.\n",
        ]);

        $this->actingAs($admin)
            ->get("/resume-studio/refined/{$refined->uuid}/pdf")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    private function fakeStudioAgents(bool $withMetricQuestions = false): void
    {
        CvJudgeAgent::fake([[
            'target_job_title' => 'Senior Laravel Developer',
            'strengths' => ['Clear stack'],
            'improvements' => ['Add metrics'],
            'keyword_gaps' => ['Horizon'],
            'weak_lines' => ['Generic summary'],
            'xyz_gaps' => ['Built Laravel APIs without a measurable Y'],
            'metric_questions' => $withMetricQuestions
              ? [[
                  'id' => 'q1',
                  'question' => 'Roughly how much did deploy time drop after your pipeline work?',
                  'related_bullet' => 'Built Laravel APIs without metrics.',
              ]]
              : [],
        ]]);

        AtsRewriteAgent::fake([[
            'refined_md' => "# Refined CV\n\nLaravel experience.",
            'ats_score' => 84,
            'feedback' => [
                'strengths' => ['Clear stack'],
                'improvements' => ['Add metrics'],
                'keyword_gaps' => [],
                'weak_lines' => ['Generic summary'],
            ],
            'target_job_title' => 'Senior Laravel Developer',
        ]]);

        JobMatchScorerAgent::fake([[
            'match_score' => 78,
            'match_reasoning' => 'Strong Laravel overlap.',
            'company_name' => 'Acme Corp',
        ]]);

        CoverDraftAgent::fake([[
            'subject' => 'Application — Senior Laravel Developer',
            'body' => 'Dear hiring manager,',
            'language' => 'en',
        ]]);

        DigestDraftAgent::fake([[
            'subject' => 'Your daily job digest',
            'body' => 'Top matches for today:',
            'language' => 'en',
        ]]);
    }
}
