<?php

declare(strict_types=1);

namespace Modules\VideoExport\Infrastructure\Pipeline;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Resolves http(s) source URLs into local workspace files (anti-LFI / SSRF).
 */
final readonly class InputResolver
{
    public function __construct(private VideoWorkspace $workspace) {}

    /**
     * @param  list<string>  $videoPaths
     * @return list<string> absolute local paths in merge order
     */
    public function resolveAll(string $jobUuid, array $videoPaths, bool $sortByCreationTime): array
    {
        $dir = $this->workspace->path($jobUuid, 'inputs');
        $this->workspace->ensureDir($dir);

        $resolved = [];
        foreach ($videoPaths as $index => $reference) {
            $this->assertSafeUrl($reference);
            $local = $dir.DIRECTORY_SEPARATOR.sprintf('part_%03d%s', $index, $this->extensionFromUrl($reference));
            $this->download($reference, $local);
            $resolved[] = [
                'path' => $local,
                'order' => $sortByCreationTime ? $this->creationOrderKey($local) : $index,
                'index' => $index,
            ];
        }

        usort($resolved, static function (array $a, array $b): int {
            $cmp = $a['order'] <=> $b['order'];

            return $cmp !== 0 ? $cmp : $a['index'] <=> $b['index'];
        });

        return array_values(array_map(static fn (array $r): string => $r['path'], $resolved));
    }

    public function assertSafeUrl(string $url): void
    {
        if (preg_match('#^(https?)://#i', $url) !== 1) {
            throw new RuntimeException('Only http(s) video URLs are allowed.');
        }

        try {
            $valid = filter_var($url, FILTER_VALIDATE_URL, FILTER_THROW_ON_FAILURE);
        } catch (\ValueError $e) {
            throw new RuntimeException('Invalid video URL.', 0, $e);
        }

        $host = strtolower((string) parse_url((string) $valid, PHP_URL_HOST));
        if ($host === '' || $this->isPrivateHost($host)) {
            throw new RuntimeException('Video URL host is not allowed.');
        }

        $allowed = $this->allowedHosts();
        if ($allowed !== [] && ! in_array($host, $allowed, true)) {
            throw new RuntimeException('Video URL host is outside the configured storage allowlist.');
        }
    }

    /**
     * @return list<string>
     */
    private function allowedHosts(): array
    {
        $hosts = [];
        foreach ([config('filesystems.disks.r2.url'), env('R2_PUBLIC_BASE_URL'), env('R2_URL')] as $base) {
            if (! is_string($base) || $base === '') {
                continue;
            }
            $host = strtolower((string) parse_url($base, PHP_URL_HOST));
            if ($host !== '') {
                $hosts[] = $host;
            }
        }

        return array_values(array_unique($hosts));
    }

    private function isPrivateHost(string $host): bool
    {
        if (in_array($host, ['localhost', 'metadata.google.internal'], true)) {
            return true;
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return ! filter_var(
                $host,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            );
        }

        return false;
    }

    private function download(string $url, string $localPath): void
    {
        $response = Http::timeout(600)->withOptions(['sink' => $localPath])->get($url);
        if (! $response->successful()) {
            throw new RuntimeException('Failed to download source video from storage.');
        }
        $max = (int) config('video-export.max_source_bytes', 2147483648);
        if (is_file($localPath) && filesize($localPath) > $max) {
            @unlink($localPath);
            throw new RuntimeException('Source video exceeds the maximum allowed size.');
        }
    }

    private function extensionFromUrl(string $url): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $ext !== '' ? '.'.Str::limit($ext, 8, '') : '.mp4';
    }

    private function creationOrderKey(string $localPath): int|float
    {
        return filemtime($localPath) ?: 0;
    }
}
