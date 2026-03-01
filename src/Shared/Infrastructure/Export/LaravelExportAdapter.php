<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Export;

use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

final class LaravelExportAdapter implements ExportInterface
{
    public function excel(array $data, string $filename): string
    {
        // Simple implementation using a collection
        $path = "exports/{$filename}.xlsx";
        Excel::store(collect($data), $path, 'public');
        return $path;
    }

    public function pdf(string $view, array $data, string $filename): string
    {
        $path = "exports/{$filename}.pdf";
        Pdf::loadView($view, $data)->save(storage_path("app/public/{$path}"));
        return $path;
    }
}
