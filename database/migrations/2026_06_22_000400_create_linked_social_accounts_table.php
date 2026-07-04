<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('linked_social_accounts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30);                 // google | github
            $table->string('provider_user_id');
            $table->string('provider_email')->nullable();
            $table->string('avatar', 2048)->nullable();
            $table->text('token')->nullable();              // encrypted at rest
            $table->text('refresh_token')->nullable();      // encrypted at rest
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // One social identity maps to exactly one local account.
            $table->unique(['provider', 'provider_user_id']);
            $table->index(['user_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('linked_social_accounts');
    }
};
