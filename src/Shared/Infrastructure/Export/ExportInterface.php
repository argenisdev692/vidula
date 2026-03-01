<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Export;

interface ExportInterface
{
    /**
     * @param array<int, array<string, mixed>> $data
     * @param string $filename
     * @return string Path to the exported file
     */
    public function excel(array $data, string $filename): string;

    /**
     * @param string $view
     * @param array<string, mixed> $data
     * @param string $filename
     * @return string Path to the exported file
     */
    public function pdf(string $view, array $data, string $filename): string;
}
