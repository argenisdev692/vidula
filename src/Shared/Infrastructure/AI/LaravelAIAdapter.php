<?php

declare(strict_types=1);

namespace Shared\Infrastructure\AI;

use Illuminate\Support\Facades\Log;
use Laravel\Ai\Exceptions\FailoverableException;
use Laravel\Ai\Image;
use Laravel\Ai\Responses\StructuredTextResponse;
use Throwable;

/**
 * @see AIClientInterface
 */
final readonly class LaravelAIAdapter implements AIClientInterface
{
    private const int DEFAULT_TIMEOUT_SECONDS = 60;

    public function generateStructured(string $agentClass, string $prompt, ?string $provider = null): StructuredTextResponse
    {
        try {
            /** @var StructuredTextResponse $response */
            $response = app($agentClass)->prompt(
                $prompt,
                provider: $provider,
                timeout: self::DEFAULT_TIMEOUT_SECONDS,
            );

            return $response;
        } catch (FailoverableException|Throwable $e) {
            Log::error('ai.structured_generation_failed', [
                'agent' => $agentClass,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function generateImage(string $prompt, ?string $provider = null, string $size = '1:1', string $quality = 'high'): array
    {
        try {
            $image = Image::of($prompt)
                ->size($size)
                ->quality($quality)
                ->generate(provider: $provider)
                ->firstImage();

            return [
                'base64' => $image->image,
                'mime' => $image->mime(),
            ];
        } catch (FailoverableException|Throwable $e) {
            Log::error('ai.image_generation_failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
