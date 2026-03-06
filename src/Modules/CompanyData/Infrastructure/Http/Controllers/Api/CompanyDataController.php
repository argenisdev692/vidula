<?php

declare(strict_types=1);

namespace Modules\CompanyData\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\CompanyData\Domain\Exceptions\CompanyDataNotFoundException;
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
 * @OA\Tag(name="CompanyData", description="Company profile data management")
 */
final class CompanyDataController
{
    public function __construct(
        private readonly CreateCompanyDataHandler $createHandler,
        private readonly UpdateCompanyDataHandler $updateHandler,
        private readonly DeleteCompanyDataHandler $deleteHandler,
        private readonly RestoreCompanyDataHandler $restoreHandler,
        private readonly ListCompanyDataHandler $listHandler,
        private readonly GetCompanyDataHandler $getHandler,
    ) {
    }

    /**
     * @OA\Get(
     *     path="/company-data/data/admin",
     *     tags={"CompanyData"},
     *     summary="List company data (paginated)",
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", default=1)),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Paginated list", @OA\JsonContent(
     *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CompanyDataReadModel")),
     *         @OA\Property(property="meta", type="object")
     *     )),
     *     security={{"sanctum": {}}}
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = CompanyDataFilterDTO::from($request->all());
        $result = $this->listHandler->handle(new ListCompanyDataQuery($filters));

        return response()->json($result);
    }

    /**
     * @OA\Get(
     *     path="/company-data/data/admin/{uuid}",
     *     tags={"CompanyData"},
     *     summary="Show company data by UUID",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Company data", @OA\JsonContent(
     *         @OA\Property(property="data", ref="#/components/schemas/CompanyDataReadModel")
     *     )),
     *     @OA\Response(response=404, description="Not found"),
     *     security={{"sanctum": {}}}
     * )
     */
    public function show(Request $request, ?string $uuid = null): JsonResponse
    {
        try {
            $result = $uuid !== null
                ? $this->getHandler->handle(new GetCompanyDataQuery(companyUuid: $uuid))
                : $this->getCurrentUserCompanyData($request);

            return response()->json(['data' => $result]);
        } catch (CompanyDataNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/company-data/data/admin",
     *     tags={"CompanyData"},
     *     summary="Create company data",
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/CreateCompanyDataDTO")),
     *     @OA\Response(response=201, description="Created"),
     *     security={{"sanctum": {}}}
     * )
     */
    public function store(CreateCompanyDataRequest $request): JsonResponse
    {
        $dto = CreateCompanyDataDTO::from($request->validated());
        $this->createHandler->handle(new CreateCompanyDataCommand($dto));

        return response()->json([
            'message' => 'Company data created successfully',
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/company-data/data/admin/{uuid}",
     *     tags={"CompanyData"},
     *     summary="Update company data",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UpdateCompanyDataDTO")),
     *     @OA\Response(response=200, description="Updated"),
     *     security={{"sanctum": {}}}
     * )
     */
    public function update(UpdateCompanyDataRequest $request, ?string $uuid = null): JsonResponse
    {
        try {
            $companyUuid = $uuid ?? $this->getCurrentUserCompanyData($request)->uuid;

            $dto = UpdateCompanyDataDTO::from($request->validated());
            $this->updateHandler->handle(new UpdateCompanyDataCommand($companyUuid, $dto));

            return response()->json([
                'message' => 'Company data updated successfully',
            ]);
        } catch (CompanyDataNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/company-data/data/admin/{uuid}",
     *     tags={"CompanyData"},
     *     summary="Soft-delete company data",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Deleted"),
     *     security={{"sanctum": {}}}
     * )
     */
    public function destroy(string $uuid): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteCompanyDataCommand($uuid));

        return response()->json([
            'message' => 'Company data deleted successfully',
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/company-data/data/admin/{uuid}/restore",
     *     tags={"CompanyData"},
     *     summary="Restore soft-deleted company data",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Restored"),
     *     security={{"sanctum": {}}}
     * )
     */
    public function restore(string $uuid): JsonResponse
    {
        $this->restoreHandler->handle(new RestoreCompanyDataCommand($uuid));

        return response()->json([
            'message' => 'Company data restored successfully',
        ]);
    }

    private function getCurrentUserCompanyData(Request $request): \Modules\CompanyData\Application\Queries\ReadModels\CompanyDataReadModel
    {
        $userUuid = $request->user()?->uuid;

        if (!$userUuid) {
            abort(401, 'User context not found');
        }

        return $this->getHandler->handle(new GetCompanyDataQuery(userUuid: $userUuid));
    }
}
