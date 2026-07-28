<?php

declare(strict_types=1);

namespace Modules\Clients\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Clients\Application\DTOs\ClientFilterData;
use Modules\Clients\Application\Queries\GetClientHandler;
use Modules\Clients\Application\Queries\ListClientsHandler;

/**
 * Sanctum-authenticated Clients API (secondary). Primary UI remains Inertia/web.
 * Scramble documents via return types + `auth:sanctum` — no manual annotations.
 * Authorization is route middleware (`permission:*_CLIENTS`).
 */
final readonly class ClientApiController
{
    /**
     * List clients.
     *
     * Returns a paginated, filterable CRM client list. `per_page` is capped at
     * 100 to bound resource consumption (OWASP API4).
     */
    public function index(Request $request, ListClientsHandler $list): JsonResponse
    {
        $filters = ClientFilterData::validateAndCreate($request);

        return response()->json($list->handle($filters, min(max($request->integer('per_page', 15), 1), 100)));
    }

    /**
     * Show a client.
     *
     * Returns a single CRM client by UUID, including invoice/product counts.
     */
    public function show(string $uuid, GetClientHandler $get): JsonResponse
    {
        return response()->json(['data' => $get->handle($uuid)]);
    }
}
