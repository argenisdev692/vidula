<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Adds a public `uuid` identifier and soft-delete support to Spatie's `roles`
 * and `permissions` tables so the Authorization module can:
 *   - bind routes by UUID instead of exposing sequential ids (enumeration / IDOR),
 *   - "suspend" a role/permission (soft delete) with a lossless restore, coherent
 *     with the RESTORE_* / BULK_RESTORE_* permissions the seeder already declares.
 *
 * The extended `Modules\Authorization\...\{Role,Permission}` Eloquent models carry
 * the `SoftDeletes` global scope, so a suspended row is hidden from Spatie's
 * runtime authorization checks as well as the management list.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [];

    public function up(): void
    {
        foreach ($this->tables() as $table) {
            Schema::table($table, static function (Blueprint $blueprint): void {
                $blueprint->uuid('uuid')->nullable()->after('id');
                $blueprint->softDeletes();
                // Admin list/export filter pattern (BACKEND-PHP §4.1 #6 / §5.2):
                // status filters soft-delete state, date range filters created_at.
                $blueprint->index(['deleted_at', 'created_at']);
            });

            $this->backfillUuids($table);

            Schema::table($table, static function (Blueprint $blueprint): void {
                $blueprint->unique('uuid');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables() as $table) {
            Schema::table($table, static function (Blueprint $blueprint): void {
                $blueprint->dropIndex(['deleted_at', 'created_at']);
                $blueprint->dropUnique(['uuid']);
                $blueprint->dropColumn(['uuid', 'deleted_at']);
            });
        }
    }

    /**
     * @return list<string>
     */
    private function tables(): array
    {
        if ($this->tables === []) {
            $names = config('permission.table_names');
            $this->tables = [$names['permissions'] ?? 'permissions', $names['roles'] ?? 'roles'];
        }

        return $this->tables;
    }

    private function backfillUuids(string $table): void
    {
        DB::table($table)
            ->whereNull('uuid')
            ->orderBy('id')
            ->pluck('id')
            ->each(static function (int|string $id) use ($table): void {
                DB::table($table)->where('id', $id)->update(['uuid' => (string) Str::uuid7()]);
            });
    }
};
