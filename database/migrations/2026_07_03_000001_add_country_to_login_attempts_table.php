<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_attempts', function (Blueprint $table): void {
            // ISO-3166 alpha-2 country resolved from the CDN edge header
            // (prompt §7 audit fields: ip_address, user_agent, country).
            $table->char('country', 2)->nullable()->after('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('login_attempts', function (Blueprint $table): void {
            $table->dropColumn('country');
        });
    }
};
