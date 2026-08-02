<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Support;

use Illuminate\Support\Carbon;
use Modules\Invoices\Infrastructure\Persistence\Eloquent\Models\InvoiceEloquentModel;
use Shared\Support\EuSchengenCountries;

/**
 * Locale-specific labels and formatting for the single-invoice PDF (dompdf).
 */
final class InvoicePdfViewAssembler
{
    /**
     * @param  array{
     *     country?: string|null,
     *     country_code?: string|null,
     *     ...
     * }  $company
     * @return array{
     *     html_lang: string,
     *     locale: string,
     *     labels: array<string, string>,
     *     issue_date: string,
     *     due_date: string,
     *     payment_date: string,
     *     client_tax_id_label: string,
     *     fiscal_notice: string|null,
     *     show_additional_notes: bool,
     *     currency_symbol: string
     * }
     */
    public function assemble(InvoiceEloquentModel $invoice, array $company): array
    {
        $clientCode = strtoupper((string) ($invoice->client_country_code ?? ''));
        $locale = self::resolveDocumentLocale($clientCode);
        $labels = self::labelsFor($locale);

        $issuerName = trim((string) ($company['country'] ?? ''));
        if ($issuerName === '') {
            $issuerName = 'Portugal';
        }

        $fiscalNotice = InvoiceCrossBorderVatNotice::forExemptInvoice(
            $invoice,
            $issuerName,
            strtoupper((string) ($company['country_code'] ?? 'PT')),
            $invoice->client_country,
            $invoice->client_country_code,
        );

        $storedFiscal = trim((string) ($invoice->notes ?? ''));
        $fiscalDisplay = $storedFiscal !== '' ? $storedFiscal : trim((string) $fiscalNotice);
        $fiscalDisplay = $fiscalDisplay !== '' ? $fiscalDisplay : null;

        $additionalNotes = trim((string) ($invoice->additional_notes ?? ''));

        return [
            'html_lang' => match ($locale) {
                'pt' => 'pt',
                'es' => 'es',
                default => 'en',
            },
            'locale' => $locale,
            'labels' => $labels,
            'issue_date' => self::formatDate($invoice->issue_date, $locale),
            'due_date' => self::formatDate($invoice->due_date, $locale),
            'payment_date' => $invoice->payment_date !== null
                ? self::formatDate($invoice->payment_date, $locale)
                : '',
            'client_tax_id_label' => self::clientTaxIdLabel($clientCode, $locale),
            'fiscal_notice' => $fiscalDisplay,
            'show_additional_notes' => $additionalNotes !== '',
            'currency_symbol' => self::currencySymbol((string) ($invoice->currency ?: 'USD')),
        ];
    }

    private static function resolveDocumentLocale(string $clientCountryCode): string
    {
        if ($clientCountryCode === 'PT') {
            return 'pt';
        }

        if ($clientCountryCode === 'ES') {
            return 'es';
        }

        if ($clientCountryCode === 'US') {
            return 'en';
        }

        if (EuSchengenCountries::includes($clientCountryCode)) {
            return 'es';
        }

        if ($clientCountryCode === '') {
            return 'en';
        }

        return 'en';
    }

    /**
     * @return array<string, string>
     */
    private static function labelsFor(string $locale): array
    {
        return match ($locale) {
            'pt' => [
                'document_title' => 'FATURA',
                'invoice_no' => 'N.º da fatura',
                'issue_date' => 'Data de emissão',
                'due_date' => 'Data de vencimento',
                'from' => 'Emitente',
                'bill_to' => 'Cliente',
                'concept' => 'Descrição',
                'quantity' => 'Qtd.',
                'unit_price' => 'Preço unit.',
                'amount' => 'Montante',
                'subtotal' => 'Subtotal',
                'exempt' => 'Exento',
                'total_due' => 'Total a pagar',
                'total_paid' => 'Total pago',
                'fiscal_heading' => 'Informação fiscal',
                'notes_heading' => 'Notas adicionais',
                'payment_received' => 'Pagamento recebido',
                'payment_method' => 'Método de pagamento',
                'transfer_number' => 'N.º de transferência',
                'payment_date' => 'Data de pagamento',
                'amount_received' => 'Montante recebido',
                'bank_heading' => 'Dados bancários',
                'beneficiary' => 'Beneficiário',
                'bank' => 'Banco',
                'footer_thanks' => 'Obrigado pela confiança.',
                'status_paid' => 'Paga',
                'status_pending' => 'Pendente',
                'email' => 'Email',
                'phone' => 'Tel.',
            ],
            'es' => [
                'document_title' => 'FACTURA',
                'invoice_no' => 'N.º de factura',
                'issue_date' => 'Fecha de emisión',
                'due_date' => 'Fecha de vencimiento',
                'from' => 'Proveedor',
                'bill_to' => 'Cliente',
                'concept' => 'Concepto',
                'quantity' => 'Cantidad',
                'unit_price' => 'Precio unitario',
                'amount' => 'Importe',
                'subtotal' => 'Subtotal',
                'exempt' => 'Exento',
                'total_due' => 'Total a pagar',
                'total_paid' => 'Total pagado',
                'fiscal_heading' => 'Información fiscal',
                'notes_heading' => 'Notas adicionales',
                'payment_received' => 'Pago recibido',
                'payment_method' => 'Método de pago',
                'transfer_number' => 'N.º de transferencia',
                'payment_date' => 'Fecha de pago',
                'amount_received' => 'Importe recibido',
                'bank_heading' => 'Datos bancarios',
                'beneficiary' => 'Beneficiario',
                'bank' => 'Banco',
                'footer_thanks' => 'Gracias por su confianza.',
                'status_paid' => 'Pagada',
                'status_pending' => 'Pendiente',
                'email' => 'Email',
                'phone' => 'Tel.',
            ],
            default => [
                'document_title' => 'INVOICE',
                'invoice_no' => 'Invoice no.',
                'issue_date' => 'Issue date',
                'due_date' => 'Due date',
                'from' => 'From',
                'bill_to' => 'Bill to',
                'concept' => 'Description',
                'quantity' => 'Qty',
                'unit_price' => 'Unit price',
                'amount' => 'Amount',
                'subtotal' => 'Subtotal',
                'exempt' => 'Exempt',
                'total_due' => 'Total due',
                'total_paid' => 'Total paid',
                'fiscal_heading' => 'Tax information',
                'notes_heading' => 'Additional notes',
                'payment_received' => 'Payment received',
                'payment_method' => 'Payment method',
                'transfer_number' => 'Transfer number',
                'payment_date' => 'Payment date',
                'amount_received' => 'Amount received',
                'bank_heading' => 'Bank details',
                'beneficiary' => 'Beneficiary',
                'bank' => 'Bank',
                'footer_thanks' => 'Thank you for your business.',
                'status_paid' => 'Paid',
                'status_pending' => 'Pending',
                'email' => 'Email',
                'phone' => 'Phone',
            ],
        };
    }

    private static function clientTaxIdLabel(string $clientCode, string $locale): string
    {
        if ($clientCode === 'US') {
            return 'EIN / Tax ID';
        }

        if ($clientCode === 'PT') {
            return match ($locale) {
                'pt' => 'NIF',
                'es' => 'NIF',
                default => 'NIF / Tax ID',
            };
        }

        if ($clientCode === 'ES' || EuSchengenCountries::includes($clientCode)) {
            return match ($locale) {
                'es' => 'NIF / CIF',
                'pt' => 'NIF / CIF',
                default => 'Tax ID / VAT no.',
            };
        }

        return 'Tax ID';
    }

    private static function formatDate(?\DateTimeInterface $date, string $locale): string
    {
        if ($date === null) {
            return '';
        }

        $carbon = Carbon::instance($date);

        return match ($locale) {
            'pt' => $carbon->locale('pt')->translatedFormat('j \\d\\e F \\d\\e Y'),
            'es' => $carbon->locale('es')->translatedFormat('j \\d\\e F \\d\\e Y'),
            default => $carbon->locale('en')->translatedFormat('F j, Y'),
        };
    }

    private static function currencySymbol(string $currency): string
    {
        return match (strtoupper($currency)) {
            'EUR' => '€',
            'USD' => '$',
            default => '$',
        };
    }
}
