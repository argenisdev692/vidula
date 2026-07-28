<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — Academic ops: enroll students into classrooms and mark
 * attendance per product_session (Lista Asistencia grain).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classroom_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('student_id')->constrained('students')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnUpdate()->cascadeOnDelete();
            $table->date('enrolled_at');
            $table->string('enrollment_status', 30)->default('active');
            $table->decimal('final_grade', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['student_id', 'classroom_id']);
            $table->index(['classroom_id', 'enrollment_status', 'deleted_at']);
            $table->index(['student_id', 'deleted_at']);
            $table->index(['enrolled_at', 'deleted_at']);
        });

        Schema::create('classroom_attendances', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('enrollment_id')
                ->constrained('classroom_enrollments')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('product_session_id')
                ->constrained('product_sessions')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('product_session_topic_id')
                ->nullable()
                ->constrained('product_session_topics')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->date('date');
            $table->string('attendance_status', 30)->default('present');
            $table->text('observation')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['enrollment_id', 'product_session_id']);
            $table->index(['enrollment_id', 'attendance_status', 'deleted_at']);
            $table->index(['product_session_id', 'date', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classroom_attendances');
        Schema::dropIfExists('classroom_enrollments');
    }
};
