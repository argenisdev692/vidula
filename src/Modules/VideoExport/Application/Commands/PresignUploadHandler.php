<?php

declare(strict_types=1);

namespace Modules\VideoExport\Application\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Modules\VideoExport\Application\DTOs\PresignUploadData;
use Shared\Domain\Ports\AuditPort;
use Shared\Domain\Ports\StoragePort;

final readonly class PresignUploadHandler
{
    public function __construct(
        private StoragePort $storage,
        private AuditPort $audit,
    ) {}

    /**
     * @return array{upload_url: string, public_url: string, key: string, headers: array<string, string>, expires_in_seconds: int}
     */
    #[\NoDiscard]
    public function handle(PresignUploadData $data, int|string $userId): array
    {
        $safe = $this->safeName($data->filename);
        $key = rtrim((string) config('video-export.upload_parts_prefix'), '/').'/'
            .Str::uuid()->toString().'/'.$safe;
        $expires = (int) config('video-export.presign_expires_seconds', 900);
        $expiresAt = CarbonImmutable::now()->addSeconds($expires);

        $upload = $this->storage->temporaryUploadUrl($key, $expiresAt);
        $publicUrl = $this->storage->publicUrl($key);

        $this->audit->log(
            'video_export.upload_presigned',
            null,
            [
                'key' => $key,
                'content_type' => $data->contentType,
                'size_bytes' => $data->sizeBytes,
            ],
            null,
            'video_export',
        );

        return [
            'upload_url' => $upload['upload_url'],
            'public_url' => $publicUrl,
            'key' => $key,
            'headers' => $upload['headers'],
            'expires_in_seconds' => $expires,
        ];
    }

    private function safeName(string $filename): string
    {
        $base = basename($filename);
        $safe = preg_replace('/[^\w.\-]/', '_', $base) ?? 'part.mp4';
        $safe = substr($safe, -128);

        return $safe !== '' ? $safe : 'part.mp4';
    }
}
