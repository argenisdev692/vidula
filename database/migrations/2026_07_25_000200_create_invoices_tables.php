<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnUpdate()->restrictOnDelete();

            $table->string('invoice_number', 32);
            $table->unsignedInteger('sequence');
            $table->unsignedSmallInteger('year');

            $table->date('issue_date');
            $table->date('due_date');

            $table->char('currency', 3)->default('USD');
            $table->string('tax_mode', 16)->default('EXEMPT');
            $table->decimal('tax_rate', 8, 4)->nullable()->default(0);
            $table->string('tax_label', 32)->default('IVA');

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->boolean('is_paid')->default(false);
            $table->string('payment_method')->nullable();
            $table->string('transfer_number')->nullable();
            $table->date('payment_date')->nullable();
            $table->decimal('amount_received', 12, 2)->nullable();

            $table->text('notes')->nullable();
            $table->text('additional_notes')->nullable();

            $table->string('client_name');
            $table->string('client_tax_id')->nullable();
            $table->string('client_address')->nullable();
            $table->string('client_city')->nullable();
            $table->string('client_country')->nullable();
            $table->char('client_country_code', 2)->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->unique(['year', 'sequence']);
            $table->unique('invoice_number');
            $table->index('deleted_at', 'idx_invoices_deleted_at');
            $table->index(['deleted_at', 'created_at']);
            $table->index(['deleted_at', 'issue_date']);
            $table->index(['client_id', 'created_at']);
            $table->index(['year', 'sequence']);
            $table->index('is_paid');
        });

        Schema::create('invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('title');
            $table->string('description', 500)->nullable();
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['invoice_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
