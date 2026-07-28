<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 billing: link an invoice header to an optional catalog product
 * (classroom / video). Line items stay free-text + optional service_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->foreignId('product_id')
                ->nullable()
                ->after('client_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('product_id');
        });
    }
};
