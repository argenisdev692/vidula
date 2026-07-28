<?php

declare(strict_types=1);

namespace Modules\Products\Domain\Ports;

/**
 * Builds the downloadable course archive. ZIP only — no tar/7z (clarify Q2).
 */
interface ZipPackagePort
{
    /**
     * Writes the entries to a temporary local `.zip` and returns its absolute
     * path. The caller streams it to storage and is responsible for deleting
     * it afterwards.
     *
     * @param  list<array{path: string, contents: string}>  $entries  Entry paths are archive-relative.
     * @return string Absolute path of the temporary archive.
     */
    public function build(array $entries): string;
}
