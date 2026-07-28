<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Internal scheduling aggregate — distinct from `appointments` (the public
     * lead-intake pipeline). A meeting has one organizer (staff) and any number
     * of polymorphic attendees (see `meeting_attendees`).
     */
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('organizer_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status')->default('Scheduled');
            // Tracks the pushed Google Calendar event id so an update/cancel
            // targets the same event instead of duplicating it. Null when sync
            // is disabled/not configured or the initial push failed — sync
            // degrades gracefully and never blocks meeting CRUD.
            $table->string('google_event_id')->nullable();
            // Google Meet join URL returned after OAuth push-sync with
            // `addMeetLink()`. Null when Meet is disabled or sync failed.
            $table->string('meet_link')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('starts_at');
            // Backs `scopeApplyFilters()`'s status + created_at window
            // (BACKEND-PHP §5.2 / §4.1 #6) — list + export share this scope.
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
