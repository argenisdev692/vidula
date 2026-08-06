<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Legacy post rows may store absolute R2 public URLs in `post_cover_image`.
 * Accessors then called StoragePort::publicUrl(), which prepended R2_URL again
 * → double-host URLs. Convert absolute URLs to object keys so cover_image_url
 * resolves via the configured R2_URL once (same fix as portfolios).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('posts')
            ->whereNotNull('post_cover_image')
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $raw = $row->post_cover_image;
                    if (! is_string($raw) || $raw === '') {
                        continue;
                    }

                    $key = $this->toObjectKey($raw);
                    if ($key === $raw) {
                        continue;
                    }

                    DB::table('posts')->where('id', $row->id)->update(['post_cover_image' => $key]);
                }
            });
    }

    public function down(): void
    {
        // Irreversible: absolute hosts cannot be reconstructed safely after
        // stripping (R2_URL may differ from the original public base).
    }

    private function toObjectKey(string $value): string
    {
        if (! str_starts_with($value, 'https://') && ! str_starts_with($value, 'http://')) {
            return $value;
        }

        $path = (string) parse_url($value, PHP_URL_PATH);

        return ltrim(rawurldecode($path), '/');
    }
};
