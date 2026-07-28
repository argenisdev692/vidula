<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Polymorphic attendee link. `attendable_type` stores the short morphMap
     * key ('user' | 'lead' | 'contact', registered in `MeetingServiceProvider`),
     * never a raw FQCN. `attendable_id` is the target's internal auto-increment
     * id (NOT its `uuid` column) — matches the default Eloquent `morphTo()`
     * owner key, confirmed against the users/appointments/contact_supports
     * migrations during planning (research.md §3).
     */
    public function up(): void
    {
        Schema::create('meeting_attendees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings')->cascadeOnDelete();
            $table->string('attendable_type');
            $table->unsignedBigInteger('attendable_id');
            $table->timestamps();

            $table->unique(['meeting_id', 'attendable_type', 'attendable_id'], 'meeting_attendees_unique');
            $table->index(['attendable_type', 'attendable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_attendees');
    }
};
