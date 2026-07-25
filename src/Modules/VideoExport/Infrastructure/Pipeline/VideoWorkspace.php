<?php

declare(strict_types=1);

namespace Modules\VideoExport\Infrastructure\Pipeline;

use Illuminate\Support\Facades\File;

final readonly class VideoWorkspace
{
    public function root(string $jobUuid): string
    {
        return rtrim((string) config('video-export.workspace_root'), DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR
            .$jobUuid;
    }

    public function path(string $jobUuid, string ...$segments): string
    {
        return $this->root($jobUuid).DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $segments);
    }

    public function ensureDir(string $dir): void
    {
        File::ensureDirectoryExists($dir);
    }

    public function wipe(string $jobUuid): void
    {
        $root = $this->root($jobUuid);
        if (File::isDirectory($root)) {
            File::deleteDirectory($root);
        }
    }
}
