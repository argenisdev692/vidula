<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Ports;

/**
 * StoragePort — Domain interface for file storage.
 *
 * Framework-agnostic: accepts a stream resource + filename
 * instead of Laravel's UploadedFile.
 */
interface StoragePort
{
    /**
     * @param resource $stream  Readable stream resource
     * @param string   $filename Original filename (for extension detection)
     */
    public function upload(mixed $stream, string $filename): string;

    public function delete(string $path): void;

    public function getUrl(string $path): string;
}
