<?php

declare(strict_types=1);

namespace Modules\Backup\Infrastructure\Http\Presenters;

use Spatie\Backup\BackupDestination\Backup;

/**
 * Maps a spatie {@see Backup} (a ZIP on a storage disk — there is no DB row) to
 * the snake_case shape the Vue module consumes. `id` is the file basename, which
 * is what every route uses to re-resolve the backup against the live list (so no
 * caller-supplied path ever reaches the filesystem).
 */
final readonly class BackupPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function toItem(Backup $backup, string $diskName): array
    {
        $bytes = (int) $backup->sizeInBytes();

        return [
            'id' => basename($backup->path()),
            'path' => $backup->path(),
            'disk' => $diskName,
            'date' => $backup->date()->toIso8601String(),
            'size_bytes' => $bytes,
            'size_human' => self::humanSize($bytes),
        ];
    }

    public static function humanSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return sprintf('%s %s', round($value, $power === 0 ? 0 : 2), $units[$power]);
    }
}
