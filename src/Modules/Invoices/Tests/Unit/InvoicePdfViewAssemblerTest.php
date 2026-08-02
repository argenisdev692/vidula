<?php

declare(strict_types=1);

namespace Modules\Invoices\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Invoices\Application\Support\InvoicePdfViewAssembler;
use Modules\Invoices\Infrastructure\Persistence\Eloquent\Models\InvoiceEloquentModel;
use Tests\TestCase;

final class InvoicePdfViewAssemblerTest extends TestCase
{
    use RefreshDatabase;

    public function test_spanish_labels_for_schengen_client_outside_portugal(): void
    {
        $invoice = InvoiceEloquentModel::factory()->make([
            'tax_mode' => 'EXEMPT',
            'tax_rate' => 0,
            'client_country' => 'Germany',
            'client_country_code' => 'DE',
        ]);

        $assembler = new InvoicePdfViewAssembler;
        $pdf = $assembler->assemble($invoice, [
            'country' => 'Portugal',
            'country_code' => 'PT',
        ]);

        $this->assertSame('es', $pdf['html_lang']);
        $this->assertSame('FACTURA', $pdf['labels']['document_title']);
        $this->assertSame('Proveedor', $pdf['labels']['from']);
        $this->assertStringContainsString('Inversión del sujeto pasivo', (string) $pdf['fiscal_notice']);
    }

    public function test_english_labels_for_united_states_client(): void
    {
        $invoice = InvoiceEloquentModel::factory()->make([
            'tax_mode' => 'EXEMPT',
            'client_country_code' => 'US',
        ]);

        $assembler = new InvoicePdfViewAssembler;
        $pdf = $assembler->assemble($invoice, ['country' => 'Portugal', 'country_code' => 'PT']);

        $this->assertSame('en', $pdf['html_lang']);
        $this->assertSame('INVOICE', $pdf['labels']['document_title']);
        $this->assertStringContainsString('Reverse Charge', (string) $pdf['fiscal_notice']);
    }
}
