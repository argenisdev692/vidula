<?php

declare(strict_types=1);

namespace Modules\Clients\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Clients\Application\Commands\CreateClient\CreateClientCommand;
use Modules\Clients\Application\Commands\CreateClient\CreateClientHandler;
use Modules\Clients\Application\Commands\DeleteClient\DeleteClientCommand;
use Modules\Clients\Application\Commands\DeleteClient\DeleteClientHandler;
use Modules\Clients\Application\Commands\RestoreClient\RestoreClientCommand;
use Modules\Clients\Application\Commands\RestoreClient\RestoreClientHandler;
use Modules\Clients\Application\Commands\UpdateClient\UpdateClientCommand;
use Modules\Clients\Application\Commands\UpdateClient\UpdateClientHandler;
use Modules\Clients\Application\DTOs\ClientFilterDTO;
use Modules\Clients\Application\DTOs\CreateClientDTO;
use Modules\Clients\Application\DTOs\UpdateClientDTO;
use Modules\Clients\Application\Queries\GetClient\GetClientHandler;
use Modules\Clients\Application\Queries\GetClient\GetClientQuery;
use Modules\Clients\Application\Queries\ListClient\ListClientHandler;
use Modules\Clients\Application\Queries\ListClient\ListClientQuery;
use Modules\Clients\Infrastructure\Http\Requests\CreateClientRequest;
use Modules\Clients\Infrastructure\Http\Requests\UpdateClientRequest;
use Modules\Clients\Infrastructure\Http\Export\ClientPdfExport;
use Modules\Clients\Infrastructure\Persistence\Export\ClientExcelExport;
use Maatwebsite\Excel\Facades\Excel;

/**
 * ClientController
 *
 * @OA\Tag(name="Clients", description="Client CRUD operations")
 */
final class ClientController
{
    public function __construct(
        private readonly CreateClientHandler $createHandler,
        private readonly UpdateClientHandler $updateHandler,
        private readonly DeleteClientHandler $deleteHandler,
        private readonly RestoreClientHandler $restoreHandler,
        private readonly ListClientHandler $listHandler,
        private readonly GetClientHandler $getHandler
    ) {
    }

    /**
     * @OA\Get(
     *     path="/clients/data/admin/export",
     *     operationId="exportClients",
     *     tags={"Clients"},
     *     summary="Export clients to Excel or PDF",
     *     @OA\Parameter(name="format", in="query", required=false, @OA\Schema(type="string", enum={"excel", "pdf"})),
     *     @OA\Response(response=200, description="File download"),
     *     security={{"sanctum":{}}}
     * )
     */
    public function export(Request $request): mixed
    {
        $format = $request->query('format', 'excel');
        $filters = ClientFilterDTO::from($request->all());
        $query = new ListClientQuery($filters);

        if ($format === 'pdf') {
            $pdfExport = new ClientPdfExport($this->listHandler, $query);
            return $pdfExport->stream();
        }

        $excelExport = new ClientExcelExport($this->listHandler, $query);
        return Excel::download($excelExport, 'clients.xlsx');
    }

    /**
     * @OA\Get(
     *     path="/clients/data/admin",
     *     operationId="listClients",
     *     tags={"Clients"},
     *     summary="List all clients (paginated)",
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="perPage", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated list of clients"),
     *     security={{"sanctum":{}}}
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = ClientFilterDTO::from($request->all());
        $result = $this->listHandler->handle(new ListClientQuery($filters));

        return response()->json($result);
    }

    /**
     * @OA\Get(
     *     path="/clients/data/admin/{uuid}",
     *     operationId="showClient",
     *     tags={"Clients"},
     *     summary="Show a single client",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Client detail"),
     *     @OA\Response(response=404, description="Client not found"),
     *     security={{"sanctum":{}}}
     * )
     */
    public function show(Request $request, ?string $uuid = null): JsonResponse
    {
        $isUserUuid = $uuid === null;
        $targetUuid = $uuid ?? $request->user()?->uuid;

        if (!$targetUuid) {
            return response()->json(['message' => 'User context not found'], 401);
        }

        $result = $this->getHandler->handle(new GetClientQuery($targetUuid, $isUserUuid));

        return response()->json(['data' => $result]);
    }

    /**
     * @OA\Post(
     *     path="/clients/data/admin",
     *     operationId="storeClient",
     *     tags={"Clients"},
     *     summary="Create a new client",
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/CreateClientDTO")),
     *     @OA\Response(response=201, description="Client created"),
     *     @OA\Response(response=422, description="Validation error"),
     *     security={{"sanctum":{}}}
     * )
     */
    public function store(CreateClientRequest $request): JsonResponse
    {
        $dto = CreateClientDTO::from($request->validated());
        $uuid = $this->createHandler->handle(new CreateClientCommand($dto));

        return response()->json([
            'message' => 'Client created successfully',
            'uuid' => $uuid,
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/clients/data/admin/{uuid}",
     *     operationId="updateClient",
     *     tags={"Clients"},
     *     summary="Update an existing client",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UpdateClientDTO")),
     *     @OA\Response(response=200, description="Client updated"),
     *     @OA\Response(response=404, description="Client not found"),
     *     security={{"sanctum":{}}}
     * )
     */
    public function update(UpdateClientRequest $request, ?string $uuid = null): JsonResponse
    {
        $isUserId = $uuid === null;
        $targetUuid = $uuid ?? $request->user()?->uuid;

        if (!$targetUuid) {
            return response()->json(['message' => 'User context not found'], 401);
        }

        $dto = UpdateClientDTO::from($request->validated());
        $this->updateHandler->handle(new UpdateClientCommand($targetUuid, $dto, $isUserId));

        return response()->json([
            'message' => 'Client updated successfully',
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/clients/data/admin/{uuid}",
     *     operationId="deleteClient",
     *     tags={"Clients"},
     *     summary="Soft-delete a client",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Client deleted"),
     *     @OA\Response(response=404, description="Client not found"),
     *     security={{"sanctum":{}}}
     * )
     */
    public function destroy(string $uuid): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteClientCommand($uuid));

        return response()->json([
            'message' => 'Client deleted successfully',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/clients/data/admin/bulk-delete",
     *     operationId="bulkDeleteClients",
     *     tags={"Clients"},
     *     summary="Bulk soft-delete multiple clients",
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="uuids", type="array", @OA\Items(type="string", format="uuid"))
     *     )),
     *     @OA\Response(response=204, description="Clients deleted"),
     *     security={{"sanctum":{}}}
     * )
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uuids' => ['required', 'array'],
            'uuids.*' => ['required', 'string', 'uuid'],
        ]);

        foreach ($validated['uuids'] as $uuid) {
            $this->deleteHandler->handle(new DeleteClientCommand($uuid));
        }

        return response()->json(null, 204);
    }

    /**
     * @OA\Patch(
     *     path="/clients/data/admin/{uuid}/restore",
     *     operationId="restoreClient",
     *     tags={"Clients"},
     *     summary="Restore a soft-deleted client",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Client restored"),
     *     security={{"sanctum":{}}}
     * )
     */
    public function restore(string $uuid): JsonResponse
    {
        $this->restoreHandler->handle(new RestoreClientCommand($uuid));

        return response()->json([
            'message' => 'Client restored successfully',
        ]);
    }
}
