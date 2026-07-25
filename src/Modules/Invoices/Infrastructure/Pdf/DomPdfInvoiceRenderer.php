<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Pdf;

use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Invoices\Domain\Ports\InvoicePdfRendererPort;
use Modules\Invoices\Infrastructure\Persistence\Eloquent\Models\InvoiceEloquentModel;
use Shared\Infrastructure\Company\CompanyProfile;

/**
 * DomPDF adapter for a single invoice document (BACKEND-PHP §8).
 */
final readonly class DomPdfInvoiceRenderer implements InvoicePdfRendererPort
{
    #[\NoDiscard]
    public function render(InvoiceEloquentModel $invoice): string
    {
        $invoice->loadMissing(['items', 'client:id,uuid,client_name,email,tax_id,nif,address']);

        return Pdf::loadView('exports.pdf.invoice', [
            'invoice' => $invoice,
            'company' => CompanyProfile::pdfBranding(),
        ])
            ->setPaper('a4', 'portrait')
            ->output();
    }
}
