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

    public function index(Request $request): JsonResponse
    {
        $filters = ClientFilterDTO::from($request->all());
        $result = $this->listHandler->handle(new ListClientQuery($filters));

        return response()->json($result);
    }

    public function show(Request $request, ?string $uuid = null): JsonResponse
    {
        $targetUuid = $uuid ?? $request->user()?->uuid;

        if (!$targetUuid) {
            return response()->json(['message' => 'User context not found'], 401);
        }

        $result = $this->getHandler->handle(new GetClientQuery($targetUuid));

        return response()->json(['data' => $result]);
    }

    public function store(CreateClientRequest $request): JsonResponse
    {
        $dto = CreateClientDTO::from($request->validated());
        $this->createHandler->handle(new CreateClientCommand($dto));

        return response()->json([
            'message' => 'Client created successfully',
        ], 201);
    }

    public function update(UpdateClientRequest $request, ?string $uuid = null): JsonResponse
    {
        $targetUuid = $uuid ?? $request->user()?->uuid;

        if (!$targetUuid) {
            return response()->json(['message' => 'User context not found'], 401);
        }

        $dto = UpdateClientDTO::from($request->validated());
        $this->updateHandler->handle(new UpdateClientCommand($targetUuid, $dto));

        return response()->json([
            'message' => 'Client updated successfully',
        ]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteClientCommand($uuid));

        return response()->json([
            'message' => 'Client deleted successfully',
        ]);
    }

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

    public function restore(string $uuid): JsonResponse
    {
        $this->restoreHandler->handle(new RestoreClientCommand($uuid));

        return response()->json([
            'message' => 'Client restored successfully',
        ]);
    }
}
