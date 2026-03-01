<?php

declare(strict_types=1);

namespace Modules\CompanyData\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\CompanyData\Application\Commands\CreateCompanyData\CreateCompanyDataCommand;
use Modules\CompanyData\Application\Commands\CreateCompanyData\CreateCompanyDataHandler;
use Modules\CompanyData\Application\Commands\DeleteCompanyData\DeleteCompanyDataCommand;
use Modules\CompanyData\Application\Commands\DeleteCompanyData\DeleteCompanyDataHandler;
use Modules\CompanyData\Application\Commands\RestoreCompanyData\RestoreCompanyDataCommand;
use Modules\CompanyData\Application\Commands\RestoreCompanyData\RestoreCompanyDataHandler;
use Modules\CompanyData\Application\Commands\UpdateCompanyData\UpdateCompanyDataCommand;
use Modules\CompanyData\Application\Commands\UpdateCompanyData\UpdateCompanyDataHandler;
use Modules\CompanyData\Application\DTOs\CompanyDataFilterDTO;
use Modules\CompanyData\Application\DTOs\CreateCompanyDataDTO;
use Modules\CompanyData\Application\DTOs\UpdateCompanyDataDTO;
use Modules\CompanyData\Application\Queries\GetCompanyData\GetCompanyDataHandler;
use Modules\CompanyData\Application\Queries\GetCompanyData\GetCompanyDataQuery;
use Modules\CompanyData\Application\Queries\ListCompanyData\ListCompanyDataHandler;
use Modules\CompanyData\Application\Queries\ListCompanyData\ListCompanyDataQuery;
use Modules\CompanyData\Infrastructure\Http\Requests\CreateCompanyDataRequest;
use Modules\CompanyData\Infrastructure\Http\Requests\UpdateCompanyDataRequest;

/**
 * CompanyDataController
 */
final class CompanyDataController
{
    public function __construct(
        private readonly CreateCompanyDataHandler $createHandler,
        private readonly UpdateCompanyDataHandler $updateHandler,
        private readonly DeleteCompanyDataHandler $deleteHandler,
        private readonly RestoreCompanyDataHandler $restoreHandler,
        private readonly ListCompanyDataHandler $listHandler,
        private readonly GetCompanyDataHandler $getHandler
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = CompanyDataFilterDTO::from($request->all());
        $result = $this->listHandler->handle(new ListCompanyDataQuery($filters));

        return response()->json($result);
    }

    public function show(Request $request, ?string $uuid = null): JsonResponse
    {
        $targetUuid = $uuid ?? $request->user()?->uuid;

        if (!$targetUuid) {
            return response()->json(['message' => 'User context not found'], 401);
        }

        $result = $this->getHandler->handle(new GetCompanyDataQuery($targetUuid));

        return response()->json(['data' => $result]);
    }

    public function store(CreateCompanyDataRequest $request): JsonResponse
    {
        $dto = CreateCompanyDataDTO::from($request->validated());
        $this->createHandler->handle(new CreateCompanyDataCommand($dto));

        return response()->json([
            'message' => 'Company data created successfully',
        ], 201);
    }

    public function update(UpdateCompanyDataRequest $request, ?string $uuid = null): JsonResponse
    {
        $targetUuid = $uuid ?? $request->user()?->uuid;

        if (!$targetUuid) {
            return response()->json(['message' => 'User context not found'], 401);
        }

        $dto = UpdateCompanyDataDTO::from($request->validated());
        $this->updateHandler->handle(new UpdateCompanyDataCommand($targetUuid, $dto));

        return response()->json([
            'message' => 'Company data updated successfully',
        ]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteCompanyDataCommand($uuid));

        return response()->json([
            'message' => 'Company data deleted successfully',
        ]);
    }

    public function restore(string $uuid): JsonResponse
    {
        $this->restoreHandler->handle(new RestoreCompanyDataCommand($uuid));

        return response()->json([
            'message' => 'Company data restored successfully',
        ]);
    }
}
