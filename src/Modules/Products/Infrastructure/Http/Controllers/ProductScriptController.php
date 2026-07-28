<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Products\Application\Commands\UpdateScriptHandler;
use Modules\Products\Application\DTOs\UpdateScriptData;
use Modules\Products\Application\Queries\GetProductHandler;
use Modules\Products\Domain\Ports\ProductScriptRepositoryPort;

/**
 * Review/edit of a per-topic script (spec US-6). The script is always resolved
 * through its product so a topic UUID from another product cannot be reached
 * via this route (OWASP API1).
 */
final readonly class ProductScriptController
{
    public function __construct(private ProductScriptRepositoryPort $scripts) {}

    public function show(string $uuid, string $topicUuid, GetProductHandler $get): JsonResponse
    {
        $product = $get->handle($uuid);

        $script = $this->scripts->findByTopicUuidForProduct($topicUuid, $product->id);
        abort_if($script === null, 404);

        return response()->json(['data' => $script]);
    }

    public function update(
        Request $request,
        string $uuid,
        string $topicUuid,
        UpdateScriptData $data,
        GetProductHandler $get,
        UpdateScriptHandler $update,
    ): RedirectResponse|JsonResponse {
        $product = $get->handle($uuid);

        $script = $this->scripts->findByTopicUuidForProduct($topicUuid, $product->id);
        abort_if($script === null, 404);

        $updated = $update->handle($script, $data, $request->user());

        return match ($request->expectsJson()) {
            true => response()->json(['data' => $updated]),
            false => back()->with('success', __('Script updated.')),
        };
    }
}
