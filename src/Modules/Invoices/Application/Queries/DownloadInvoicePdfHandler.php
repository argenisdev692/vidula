<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Queries;

use Illuminate\Contracts\Cache\Repository as Cache;
use Modules\Invoices\Application\Support\InvoiceCacheKeys;
use Modules\Invoices\Domain\Ports\InvoicePdfRendererPort;

/**
 * Resolves a single invoice PDF binary. Redis caches the payload for 24h;
 * mutations forget the key and Horizon re-warms via GenerateInvoicePdfJob.
 */
final readonly class DownloadInvoicePdfHandler
{
    public function __construct(
        private GetInvoiceHandler $get,
        private InvoicePdfRendererPort $renderer,
        private Cache $cache,
    ) {}

    /**
     * @return array{binary: string, filename: string}
     */
    public function handle(string $uuid): array
    {
        $invoice = $this->get->handle($uuid);

        $binary = $this->cache->remember(
            InvoiceCacheKeys::pdf($uuid),
            now()->addDay(),
            fn (): string => $this->renderer->render($invoice),
        );

        $safeNumber = str_replace('/', '-', $invoice->invoice_number);

        return [
            'binary' => $binary,
            'filename' => "invoice-{$safeNumber}.pdf",
        ];
    }
}
