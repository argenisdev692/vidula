<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\VideoExport;

use Illuminate\Support\Facades\Http;
use Modules\VideoExport\Infrastructure\Pipeline\InputResolver;
use Modules\VideoExport\Infrastructure\Pipeline\VideoWorkspace;
use Shared\Domain\Ports\StoragePort;
use Tests\TestCase;

final class InputResolverTest extends TestCase
{
    public function test_downloads_allowlisted_r2_url_via_storage_port_not_http(): void
    {
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ve-test-'.uniqid('', true);
        config([
            'filesystems.disks.r2.url' => 'https://cdn.example.test',
            'video-export.workspace_root' => $root,
        ]);

        $jobUuid = '11111111-1111-1111-1111-111111111111';

        $storage = new class implements StoragePort
        {
            public string $copiedKey = '';

            public string $copiedLocal = '';

            public function put(string $path, string $contents, string $visibility = 'private'): string
            {
                return $path;
            }

            public function putFile(string $directory, \SplFileInfo $file, string $visibility = 'private'): string
            {
                return $directory.'/file.bin';
            }

            public function temporaryUrl(string $path, \DateTimeInterface $expiresAt): string
            {
                return 'https://signed.example/'.$path;
            }

            public function temporaryUploadUrl(string $path, \DateTimeInterface $expiresAt): array
            {
                return ['upload_url' => 'https://upload.example/'.$path, 'headers' => []];
            }

            public function publicUrl(string $path): string
            {
                return 'https://cdn.example.test/'.$path;
            }

            public function copyToLocal(string $path, string $localPath): void
            {
                $this->copiedKey = $path;
                $this->copiedLocal = $localPath;
                file_put_contents($localPath, 'fake-video-bytes');
            }

            public function delete(string $path): bool
            {
                return true;
            }

            public function exists(string $path): bool
            {
                return true;
            }
        };

        Http::fake();

        $workspace = new VideoWorkspace;
        $resolver = new InputResolver($workspace, $storage);
        $paths = $resolver->resolveAll(
            $jobUuid,
            ['https://cdn.example.test/video-exports/_parts/a%20b.mp4'],
            false,
        );

        $this->assertCount(1, $paths);
        $this->assertFileExists($paths[0]);
        $this->assertSame('video-exports/_parts/a b.mp4', $storage->copiedKey);
        Http::assertNothingSent();

        $workspace->wipe($jobUuid);
    }

    public function test_rejects_non_allowlisted_host(): void
    {
        config(['filesystems.disks.r2.url' => 'https://cdn.example.test']);

        $storage = new class implements StoragePort
        {
            public function put(string $path, string $contents, string $visibility = 'private'): string
            {
                return $path;
            }

            public function putFile(string $directory, \SplFileInfo $file, string $visibility = 'private'): string
            {
                return $directory.'/file.bin';
            }

            public function temporaryUrl(string $path, \DateTimeInterface $expiresAt): string
            {
                return 'https://signed.example/'.$path;
            }

            public function temporaryUploadUrl(string $path, \DateTimeInterface $expiresAt): array
            {
                return ['upload_url' => 'https://upload.example/'.$path, 'headers' => []];
            }

            public function publicUrl(string $path): string
            {
                return 'https://cdn.example.test/'.$path;
            }

            public function copyToLocal(string $path, string $localPath): void {}

            public function delete(string $path): bool
            {
                return true;
            }

            public function exists(string $path): bool
            {
                return true;
            }
        };

        $resolver = new InputResolver(new VideoWorkspace, $storage);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('outside the configured storage allowlist');

        $resolver->assertSafeUrl('https://evil.example/video.mp4');
    }
}
