<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Support;

/**
 * Redis cache key helpers for invoice aggregate + generated PDF binaries.
 */
final readonly class InvoiceCacheKeys
{
    public static function invoice(string $uuid): string
    {
        return "invoice_{$uuid}";
    }

    public static function pdf(string $uuid): string
    {
        return "invoice_pdf_{$uuid}";
    }
}
