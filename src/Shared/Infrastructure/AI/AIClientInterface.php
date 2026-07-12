<?php

declare(strict_types=1);

namespace Shared\Infrastructure\AI;

use Laravel\Ai\Responses\StructuredTextResponse;

/**
 * Sole LLM bridge for the application (ARCHITECTURE-PHP §"Infrastructure/AI").
 * Wraps the official `laravel/ai` SDK so the provider switch (OpenAI / Anthropic
 * / Gemini) happens entirely via `config('ai.php')` + a runtime override string —
 * callers never touch a provider SDK directly.
 *
 * Scope note: this interface intentionally does NOT bundle a circuit breaker or
 * a bespoke rate limiter (Shared\Infrastructure\Resilience already ships a
 * generic CircuitBreaker for callers that need one; per-route `throttle:` guards
 * cover LLM10 Unbounded Consumption here — see Post module routes). Building a
 * dedicated LLM gateway pipeline is out of scope until a second AI-consuming
 * module needs it (YAGNI).
 */
interface AIClientInterface
{
    /**
     * Invoke a structured-output Agent (implements `Agent` + `HasStructuredOutput`)
     * and return its schema-validated response. Access fields via
     * `$response['field']` (StructuredTextResponse is array-accessible).
     *
     * @param  class-string  $agentClass
     */
    public function generateStructured(string $agentClass, string $prompt, ?string $provider = null): StructuredTextResponse;

    /**
     * Generate a single image and return its raw bytes (never persisted here —
     * the caller decides the StoragePort destination and visibility). The
     * installed `laravel/ai` 0.8.x `GeneratedImage` DTO exposes only base64
     * content + MIME — no revised-prompt/URL fields — so neither is returned.
     *
     * @return array{base64: string, mime: string}
     */
    public function generateImage(string $prompt, ?string $provider = null, string $size = '1:1', string $quality = 'high'): array;
}
