<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Packaging;

use Modules\Products\Domain\Ports\ZipPackagePort;
use RuntimeException;
use ZipArchive;

/**
 * Builds a temporary `.zip` of the course deliverable tree (clarify Q2 — ZIP only).
 */
final readonly class PhpZipPackageAdapter implements ZipPackagePort
{
    public function build(array $entries): string
    {
        $path = tempnam(sys_get_temp_dir(), 'product-zip-');

        if ($path === false) {
            throw new RuntimeException('Unable to allocate a temporary ZIP path.');
        }

        $zipPath = $path.'.zip';
        @unlink($path);

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to open temporary ZIP archive.');
        }

        foreach ($entries as $entry) {
            $relative = ltrim(str_replace('\\', '/', $entry['path']), '/');
            $zip->addFromString($relative, $entry['contents']);
        }

        $zip->close();

        return $zipPath;
    }
}
