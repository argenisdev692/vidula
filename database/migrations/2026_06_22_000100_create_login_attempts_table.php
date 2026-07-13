<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('email')->index();
            $table->string('ip_address', 45)->nullable();
            // ISO-3166 alpha-2 country resolved from the CDN edge header
            // (prompt §7 audit fields: ip_address, user_agent, country).
            $table->char('country', 2)->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('successful')->default(false);
            $table->uuid('user_uuid')->nullable()->index();
            $table->string('guard', 20)->default('web');
            $table->timestamps();

            // Lockout window query: failed attempts for an email over time.
            $table->index(['email', 'successful', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
