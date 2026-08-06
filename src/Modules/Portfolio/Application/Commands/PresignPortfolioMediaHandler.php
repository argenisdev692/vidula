<?php

declare(strict_types=1);

namespace Modules\Portfolio\Application\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Portfolio\Application\DTOs\PresignPortfolioMediaData;
use Shared\Domain\Ports\AuditPort;
use Shared\Domain\Ports\StoragePort;

/**
 * Mints a StoragePort temporaryUploadUrl so the browser can PUT cover/video
 * bytes straight to Cloudflare R2 (same pattern as VideoExport).
 */
final readonly class PresignPortfolioMediaHandler
{
    public function __construct(
        private StoragePort $storage,
        private AuditPort $audit,
    ) {}

    /**
     * @return array{upload_url: string, key: string, headers: array<string, string>, expires_in_seconds: int}
     */
    #[\NoDiscard]
    public function handle(PresignPortfolioMediaData $data): array
    {
        $this->assertKindMatchesContentType($data);

        $prefix = $data->kind === 'cover'
            ? (string) config('portfolio.cover_prefix', 'portfolios/cover')
            : (string) config('portfolio.video_prefix', 'portfolios/video');

        $safe = $this->safeName($data->filename, $data->kind);
        $key = rtrim($prefix, '/').'/'.Str::uuid()->toString().'/'.$safe;
        $expires = (int) config('portfolio.presign_expires_seconds', 900);
        $expiresAt = CarbonImmutable::now()->addSeconds($expires);

        $upload = $this->storage->temporaryUploadUrl($key, $expiresAt);

        $this->audit->log(
            'portfolio.media_upload_presigned',
            null,
            [
                'key' => $key,
                'kind' => $data->kind,
                'content_type' => $data->contentType,
                'size_bytes' => $data->sizeBytes,
            ],
            null,
            'portfolio',
        );

        return [
            'upload_url' => $upload['upload_url'],
            'key' => $key,
            'headers' => $upload['headers'],
            'expires_in_seconds' => $expires,
        ];
    }

    private function assertKindMatchesContentType(PresignPortfolioMediaData $data): void
    {
        $type = strtolower($data->contentType);
        $ok = $data->kind === 'cover'
            ? in_array($type, ['image/jpeg', 'image/png', 'image/webp'], true)
            : in_array($type, ['video/mp4', 'video/webm'], true);

        if (! $ok) {
            throw ValidationException::withMessages([
                'content_type' => __('Content type does not match media kind.'),
            ]);
        }

        $max = $data->kind === 'cover'
            ? (int) config('portfolio.max_cover_bytes', 4 * 1024 * 1024)
            : (int) config('portfolio.max_video_bytes', 50 * 1024 * 1024);

        if ($data->sizeBytes > $max) {
            throw ValidationException::withMessages([
                'size_bytes' => $data->kind === 'cover'
                    ? __('Cover must be 4 MB or smaller.')
                    : __('Video must be 50 MB or smaller.'),
            ]);
        }
    }

    private function safeName(string $filename, string $kind): string
    {
        $base = basename($filename);
        $safe = preg_replace('/[^\w.\-]+/', '_', $base) ?? ($kind === 'cover' ? 'cover.jpg' : 'video.mp4');
        $safe = substr($safe, -128);

        return $safe !== '' ? $safe : ($kind === 'cover' ? 'cover.jpg' : 'video.mp4');
    }
}
