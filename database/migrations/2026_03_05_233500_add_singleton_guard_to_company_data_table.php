<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::table('company_data')->count() > 1) {
            throw new RuntimeException('Cannot add singleton guard to company_data because multiple records already exist.');
        }

        Schema::table('company_data', function (Blueprint $table) {
            $table->unsignedTinyInteger('singleton_guard')->default(1);
            $table->unique('singleton_guard', 'company_data_singleton_guard_unique');
        });
    }

    public function down(): void
    {
        Schema::table('company_data', function (Blueprint $table) {
            $table->dropUnique('company_data_singleton_guard_unique');
            $table->dropColumn('singleton_guard');
        });
    }
};
