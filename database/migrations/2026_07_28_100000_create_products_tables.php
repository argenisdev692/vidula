<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 4 — Products: billable catalog + unified content tree
 * (`product_sessions` → `product_session_topics` → scripts/materials) + the
 * async content-generation ledger.
 *
 * The tree is unified on purpose (plan.md §4 supersedes the earlier
 * MODULE-PRODUCTS drafts): classroom and video products share sessions and
 * topics, and only the 1:1 detail rows (`classrooms` / `video_courses`) differ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->cascadeOnUpdate()->nullOnDelete();

            $table->string('type', 32);
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->char('currency', 3)->default('EUR');
            $table->string('status', 16)->default('draft');
            $table->string('thumbnail')->nullable();
            $table->string('level', 32)->default('beginner');
            $table->string('language', 8)->default('es');

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('total_hours', 8, 2)->nullable();
            $table->unsignedInteger('total_sessions')->nullable();
            $table->string('modality', 16)->nullable();
            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('user_id');
            $table->index('client_id');
            $table->index('type');
            $table->index(['deleted_at', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('classrooms', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->cascadeOnDelete();

            $table->unsignedInteger('max_students')->nullable();
            $table->string('meet_url')->nullable();
            $table->text('objectives')->nullable();
            $table->text('requirements')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->unique('product_id');
        });

        Schema::create('video_courses', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->cascadeOnDelete();

            $table->string('platform', 16)->nullable();
            $table->string('playlist_url')->nullable();
            $table->unsignedInteger('total_videos')->default(0);
            $table->unsignedInteger('total_duration_minutes')->nullable();
            $table->string('target_audience')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->unique('product_id');
        });

        // Replace-mode regeneration soft-deletes the whole tree and rebuilds it,
        // so (product_id, session_number) is a plain index — a unique constraint
        // would collide with the trashed rows still occupying the numbers.
        Schema::create('product_sessions', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->cascadeOnDelete();

            $table->unsignedInteger('session_number');
            $table->string('title');
            $table->date('session_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('hours', 6, 2)->nullable();
            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['product_id', 'session_number']);
            $table->index(['deleted_at', 'created_at']);
        });

        Schema::create('product_session_topics', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('product_session_id')
                ->constrained('product_sessions')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('hours', 6, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('sources_json')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['product_session_id', 'sort_order']);
        });

        Schema::create('product_scripts', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('product_session_topic_id')
                ->constrained('product_session_topics')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->text('intro')->nullable();
            $table->text('body')->nullable();
            $table->text('outro')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 16)->default('draft');
            $table->unsignedInteger('estimated_minutes')->nullable();
            $table->string('generated_by_model', 64)->nullable();
            $table->json('sources_json')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->unique('product_session_topic_id');
            $table->index(['status', 'created_at']);
        });

        Schema::create('product_materials', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_session_topic_id')
                ->nullable()
                ->constrained('product_session_topics')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('title');
            // pdf | markdown | link — no video binary type in v1 (clarify Q2).
            $table->string('type', 16);
            $table->string('storage_disk', 32)->nullable();
            $table->string('path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('url', 2048)->nullable();
            $table->text('content')->nullable();
            $table->boolean('is_downloadable')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->softDeletes();
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
            $table->index(['product_session_topic_id', 'sort_order']);
            $table->index(['deleted_at', 'created_at']);
        });

        // Generation ledger: append-only operational history, never soft-deleted
        // (deleting the product cascades it away).
        Schema::create('content_generations', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();

            $table->string('status', 16)->default('pending');
            $table->string('mode', 16)->default('replace');
            $table->longText('source_markdown');
            $table->string('model', 64)->nullable();
            $table->unsignedTinyInteger('progress')->default(0);

            $table->unsignedInteger('sessions_count')->default(0);
            $table->unsignedInteger('topics_count')->default(0);
            $table->unsignedInteger('scripts_count')->default(0);

            $table->string('pdf_path')->nullable();
            $table->string('md_path')->nullable();
            $table->string('zip_path')->nullable();
            $table->text('error')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Backs the "one non-terminal generation per product" lookup (FR-14).
            $table->index(['product_id', 'status']);
            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_generations');
        Schema::dropIfExists('product_materials');
        Schema::dropIfExists('product_scripts');
        Schema::dropIfExists('product_session_topics');
        Schema::dropIfExists('product_sessions');
        Schema::dropIfExists('video_courses');
        Schema::dropIfExists('classrooms');
        Schema::dropIfExists('products');
    }
};
