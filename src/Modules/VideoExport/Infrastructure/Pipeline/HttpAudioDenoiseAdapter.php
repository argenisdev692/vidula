<?php

declare(strict_types=1);

namespace Modules\VideoExport\Infrastructure\Pipeline;

use Illuminate\Support\Facades\Http;
use Modules\VideoExport\Domain\Ports\AudioDenoisePort;
use RuntimeException;

/**
 * External Audio Cleaner AI over HTTPS. URL comes only from config (no user input) — SSRF-safe.
 *
 * Expected contract: POST multipart field `file` → 200 body = denoised audio bytes.
 */
final readonly class HttpAudioDenoiseAdapter implements AudioDenoisePort
{
    public function __construct(
        private string $endpointUrl,
        private string $token = '',
        private int $timeoutSeconds = 600,
    ) {}

    public function isConfigured(): bool
    {
        if ($this->endpointUrl === '') {
            return false;
        }

        $parts = parse_url($this->endpointUrl);

        return is_array($parts)
            && ($parts['scheme'] ?? '') === 'https'
            && filled($parts['host'] ?? null);
    }

    public function enhance(string $inputPath, string $outputPath): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException(
                'AI denoise HTTP endpoint is not configured. Set VIDEO_EXPORT_AI_DENOISE_URL to an https:// URL.',
            );
        }

        $contents = file_get_contents($inputPath);
        if ($contents === false || $contents === '') {
            throw new RuntimeException('AI denoise input audio could not be read.');
        }

        $pending = Http::timeout($this->timeoutSeconds)
            ->connectTimeout(15)
            ->attach('file', $contents, basename($inputPath), ['Content-Type' => 'audio/wav']);

        if ($this->token !== '') {
            $pending = $pending->withToken($this->token);
        }

        $response = $pending->post($this->endpointUrl);

        if (! $response->successful()) {
            throw new RuntimeException('AI denoise HTTP request failed.');
        }

        $body = $response->body();
        if ($body === '') {
            throw new RuntimeException('AI denoise HTTP response was empty.');
        }

        if (file_put_contents($outputPath, $body) === false) {
            throw new RuntimeException('AI denoise output could not be written.');
        }
    }
}
