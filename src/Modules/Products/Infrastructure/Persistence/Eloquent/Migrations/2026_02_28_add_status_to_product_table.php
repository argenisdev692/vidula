<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product', function (Blueprint $table) {
            if (!Schema::hasColumn('product', 'status')) {
                $table->string('status')->default('active')->after('signature_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
