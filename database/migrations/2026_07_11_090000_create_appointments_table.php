<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sales-lead capture + scheduling aggregate. A lead is captured once (public
     * landing page or admin) and progresses through `status_lead` while the
     * booked meeting progresses through `meeting_status`. `follow_up_calls` is a
     * free-form JSON log of call attempts; `owner` is a free-text assignee label
     * (no FK — no per-operator ownership model exists yet).
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('first_name');
            $table->string('last_name');

            $table->string('client_type', 20)->default('individual');
            $table->string('company_name')->nullable();
            $table->string('project_type', 30)->nullable();

            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->text('address_2')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();

            $table->boolean('sms_consent')->default(false);
            $table->boolean('readed')->default(false);

            // Anti-spam verdict written by the public booking pipeline (SpamGuard
            // + spatie/laravel-honeypot): `is_spam` drives the admin "Spam"
            // filter; `spam_score` + `spam_reasons` explain the verdict for review.
            $table->boolean('is_spam')->default(false);
            $table->unsignedSmallInteger('spam_score')->default(0);
            $table->json('spam_reasons')->nullable();

            $table->string('status_lead', 20)->nullable();
            $table->string('meeting_status', 20)->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('previous_scheduled_at')->nullable();

            $table->json('follow_up_calls')->nullable();
            $table->text('notes')->nullable();
            $table->string('owner')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('status_lead', 'idx_appointments_status_lead');
            $table->index('readed', 'idx_appointments_readed');
            $table->index('is_spam', 'idx_appointments_is_spam');
            $table->index('scheduled_at', 'idx_appointments_scheduled_at');
            // Canonical list filter pattern (status via deleted_at + default
            // ordering by created_at) — BACKEND-PHP §4.1.
            $table->index(['deleted_at', 'created_at'], 'idx_appointments_deleted_at_created_at');
        });

        // One ACTIVE lead per email. Soft-deleted rows are ignored so a lead can
        // be re-captured after a prior record was purged. Partial unique indexes
        // aren't expressible via the Blueprint, hence the raw statement (mirrors
        // availability_exceptions_date_active).
        DB::statement(
            'CREATE UNIQUE INDEX idx_appointments_email_unique
             ON appointments (email) WHERE deleted_at IS NULL',
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
