<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Ports;

use Illuminate\Http\UploadedFile;

/**
 * StoragePort
 */
interface StoragePort
{
    public function upload(UploadedFile $file): string;

    public function delete(string $path): void;

    public function getUrl(string $path): string;
}
