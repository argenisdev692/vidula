<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Invitation / activation lifecycle columns for admin-managed users.
 *
 * `password` becomes nullable so a freshly-invited user can exist in the
 * `Pending` state (no password yet, cannot authenticate) until they activate
 * via the emailed signed link.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('password')->nullable()->change();
            $table->boolean('must_change_password')->default(false)->after('password_changed_at');
            $table->timestamp('invited_at')->nullable()->after('must_change_password');
            $table->string('invited_by')->nullable()->after('invited_at'); // inviter uuid (audit)
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['must_change_password', 'invited_at', 'invited_by']);
            // Restore NOT NULL only if no pending users remain; left nullable is safe.
        });
    }
};
