<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Products\Application\Commands\StartContentGenerationHandler;
use Modules\Products\Application\DTOs\GenerateContentData;
use Modules\Products\Application\Queries\GetContentGenerationStatusHandler;
use Modules\Products\Application\Queries\GetProductHandler;

/**
 * Async content generation surface (spec US-2 / US-8).
 *
 * The seed can arrive either pasted (`markdown`) or as a `.md` upload; both are
 * normalised here so the Application layer only ever sees a string. Everything
 * past that point — type guard, size cap, one-run-per-product — lives in
 * {@see StartContentGenerationHandler}.
 */
final readonly class ProductGenerationController
{
    private const int MAX_MARKDOWN_KILOBYTES = 1024;

    public function store(
        Request $request,
        string $uuid,
        GetProductHandler $get,
        StartContentGenerationHandler $start,
    ): RedirectResponse|JsonResponse {
        $product = $get->handle($uuid);

        $validated = $request->validate([
            'markdown' => ['required_without:file', 'nullable', 'string'],
            'file' => [
                'required_without:markdown',
                'nullable',
                'file',
                'max:'.self::MAX_MARKDOWN_KILOBYTES,
                'extensions:md,markdown',
                'mimetypes:text/markdown,text/plain,text/x-markdown',
            ],
            'mode' => ['nullable', 'string', 'in:replace,force_replace'],
        ]);

        $markdown = $request->hasFile('file')
            ? (string) file_get_contents($request->file('file')->getRealPath())
            : (string) ($validated['markdown'] ?? '');

        $generation = $start->handle(
            $product,
            GenerateContentData::validateAndCreate([
                'markdown' => $markdown,
                'mode' => $validated['mode'] ?? 'replace',
            ]),
            $request->user(),
        );

        return match ($request->expectsJson()) {
            true => response()->json(['data' => ['uuid' => $generation->uuid]], 202),
            false => back()->with('success', __('Content generation queued.')),
        };
    }

    public function show(
        Request $request,
        string $uuid,
        string $generationUuid,
        GetProductHandler $get,
        GetContentGenerationStatusHandler $status,
    ): JsonResponse {
        return response()->json([
            'data' => $status->handle($get->handle($uuid), $generationUuid),
        ]);
    }
}
