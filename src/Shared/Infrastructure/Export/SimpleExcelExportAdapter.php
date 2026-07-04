<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Export;

use Illuminate\Http\Response;
use Shared\Domain\Ports\ExportPort;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bound implementation of {@see ExportPort}.
 *
 * Owns the tabular mechanism (CSV + Excel via spatie/simple-excel — the writer
 * is auto-selected from the filename extension, so CSV needs no extra package)
 * and delegates PDF to {@see DomPdfExportAdapter}, keeping each rendering
 * mechanism in a single-responsibility class while exposing ONE port to modules.
 */
final readonly class SimpleExcelExportAdapter implements ExportPort
{
    public function __construct(private DomPdfExportAdapter $pdf) {}

    public function tabular(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        $writer = SimpleExcelWriter::streamDownload($filename) // '.csv' | '.xlsx' → driver picked from extension
            ->addHeader($headers);

        foreach ($rows as $row) { // LazyCollection / generator → memory-safe streaming
            $writer->addRow($row);
        }

        return $writer->toBrowser();
    }

    public function pdf(
        string $filename,
        string $view,
        array $data,
        string $paper = 'a4',
        string $orientation = 'landscape',
    ): Response {
        return $this->pdf->render($filename, $view, $data, $paper, $orientation);
    }
}
