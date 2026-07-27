<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 2 — AiResumeStudio aggregates (ATS refine, job search, outreach drafts).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('github_enrichments', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('cv_id')->nullable()->constrained('cvs')->nullOnDelete();
            $table->string('github_username');
            $table->json('selected_repos');
            $table->text('extra_prompt')->nullable();
            $table->json('repos_summary')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['deleted_at', 'created_at']);
        });

        Schema::create('job_search_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('cv_id')->constrained('cvs')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('mode', 16);
            $table->string('keywords');
            $table->string('location_scope', 64)->nullable();
            $table->string('search_language', 16)->default('both');
            $table->string('resume_language', 16)->default('en');
            $table->text('targeting_prompt')->nullable();
            $table->boolean('schedule_enabled')->default(false);
            $table->boolean('deep_extract_enabled')->default(false);
            $table->boolean('auto_send_enabled')->default(false);
            $table->string('provider', 32)->default('openai');
            $table->string('status', 16)->default('active');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['schedule_enabled', 'deleted_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['deleted_at', 'created_at']);
        });

        Schema::create('studio_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('cv_id')->constrained('cvs')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('job_search_config_id')->nullable()->constrained('job_search_configs')->nullOnDelete();
            $table->string('mode', 16);
            $table->string('step', 32);
            $table->string('status', 16);
            $table->text('error_summary')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['deleted_at', 'created_at']);
        });

        Schema::create('refined_cvs', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('cv_id')->constrained('cvs')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('studio_run_id')->nullable()->constrained('studio_runs')->nullOnDelete();
            $table->string('mode', 16);
            $table->string('target_job_title')->nullable();
            $table->string('resume_language', 16)->default('en');
            $table->string('provider', 32);
            $table->unsignedTinyInteger('ats_score')->default(0);
            $table->longText('refined_md');
            $table->json('feedback')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['cv_id', 'version']);
            $table->index(['deleted_at', 'created_at']);
        });

        Schema::create('job_matches', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('job_search_config_id')->nullable()->constrained('job_search_configs')->nullOnDelete();
            $table->foreignId('studio_run_id')->nullable()->constrained('studio_runs')->nullOnDelete();
            $table->string('job_title');
            $table->string('company_name')->nullable();
            $table->string('job_url', 2048);
            $table->string('canonical_url', 2048);
            $table->text('raw_snippet')->nullable();
            $table->longText('raw_md')->nullable();
            $table->unsignedTinyInteger('match_score')->default(0);
            $table->text('match_reasoning')->nullable();
            $table->string('source', 16)->default('tavily');
            $table->string('application_status', 16)->default('new');
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['user_id', 'canonical_url']);
            $table->index(['deleted_at', 'created_at']);
        });

        Schema::create('outreach_drafts', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('job_match_id')->nullable()->constrained('job_matches')->nullOnDelete();
            $table->foreignId('studio_run_id')->nullable()->constrained('studio_runs')->nullOnDelete();
            $table->string('kind', 16);
            $table->string('subject');
            $table->longText('body');
            $table->string('language', 8)->default('en');
            $table->string('status', 16)->default('draft');
            $table->string('provider', 32)->default('openai');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['deleted_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outreach_drafts');
        Schema::dropIfExists('job_matches');
        Schema::dropIfExists('refined_cvs');
        Schema::dropIfExists('studio_runs');
        Schema::dropIfExists('job_search_configs');
        Schema::dropIfExists('github_enrichments');
    }
};
