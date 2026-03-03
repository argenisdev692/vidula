<?php

declare(strict_types=1);

namespace Modules\Students\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Students\Application\Commands\CreateStudent\CreateStudentCommand;
use Modules\Students\Application\Commands\CreateStudent\CreateStudentHandler;
use Modules\Students\Application\Commands\DeleteStudent\DeleteStudentCommand;
use Modules\Students\Application\Commands\DeleteStudent\DeleteStudentHandler;
use Modules\Students\Application\Commands\RestoreStudent\RestoreStudentCommand;
use Modules\Students\Application\Commands\RestoreStudent\RestoreStudentHandler;
use Modules\Students\Application\Commands\UpdateStudent\UpdateStudentCommand;
use Modules\Students\Application\Commands\UpdateStudent\UpdateStudentHandler;
use Modules\Students\Application\DTOs\StudentFilterDTO;
use Modules\Students\Application\DTOs\CreateStudentDTO;
use Modules\Students\Application\DTOs\UpdateStudentDTO;
use Modules\Students\Application\Queries\GetStudent\GetStudentHandler;
use Modules\Students\Application\Queries\GetStudent\GetStudentQuery;
use Modules\Students\Application\Queries\ListStudent\ListStudentHandler;
use Modules\Students\Application\Queries\ListStudent\ListStudentQuery;
use Modules\Students\Infrastructure\Http\Requests\CreateStudentRequest;
use Modules\Students\Infrastructure\Http\Requests\UpdateStudentRequest;

/**
 * StudentController
 */
final class StudentController
{
    public function __construct(
        private readonly CreateStudentHandler $createHandler,
        private readonly UpdateStudentHandler $updateHandler,
        private readonly DeleteStudentHandler $deleteHandler,
        private readonly RestoreStudentHandler $restoreHandler,
        private readonly ListStudentHandler $listHandler,
        private readonly GetStudentHandler $getHandler
    ) {
    }

    /**
     * @OA\Get(
     *     path="/api/students/admin",
     *     summary="List students (paginated)",
     *     tags={"Students"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="page",   in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/StudentReadModel")),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = StudentFilterDTO::from($request->all());
        $result = $this->listHandler->handle(new ListStudentQuery($filters));

        return response()->json($result);
    }

    /**
     * @OA\Get(
     *     path="/api/students/admin/{uuid}",
     *     summary="Get student details",
     *     tags={"Students"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(
     *         response=200,
     *         description="Student detail",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/StudentReadModel")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(Request $request, ?string $uuid = null): JsonResponse
    {
        $targetUuid = $uuid ?? $request->user()?->uuid;

        if (!$targetUuid) {
            return response()->json(['message' => 'User context not found'], 401);
        }

        $result = $this->getHandler->handle(new GetStudentQuery($targetUuid));

        return response()->json(['data' => $result]);
    }

    /**
     * @OA\Post(
     *     path="/api/students/admin",
     *     summary="Create student",
     *     tags={"Students"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/CreateStudentDTO")),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(CreateStudentRequest $request): JsonResponse
    {
        $dto = CreateStudentDTO::from($request->validated());
        $this->createHandler->handle(new CreateStudentCommand($dto));

        return response()->json([
            'message' => 'Student created successfully',
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/students/admin/{uuid}",
     *     summary="Update student",
     *     tags={"Students"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UpdateStudentDTO")),
     *     @OA\Response(response=200, description="Updated"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateStudentRequest $request, ?string $uuid = null): JsonResponse
    {
        $targetUuid = $uuid ?? $request->user()?->uuid;

        if (!$targetUuid) {
            return response()->json(['message' => 'User context not found'], 401);
        }

        $dto = UpdateStudentDTO::from($request->validated());
        $this->updateHandler->handle(new UpdateStudentCommand($targetUuid, $dto));

        return response()->json([
            'message' => 'Student updated successfully',
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/students/admin/{uuid}",
     *     summary="Soft delete student",
     *     tags={"Students"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(string $uuid): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteStudentCommand($uuid));

        return response()->json([
            'message' => 'Student deleted successfully',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/students/admin/bulk-delete",
     *     summary="Bulk delete students",
     *     tags={"Students"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="uuids", type="array", @OA\Items(type="string", format="uuid"))
     *     )),
     *     @OA\Response(response=204, description="Deleted")
     * )
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uuids' => ['required', 'array'],
            'uuids.*' => ['required', 'string', 'uuid'],
        ]);

        foreach ($validated['uuids'] as $uuid) {
            $this->deleteHandler->handle(new DeleteStudentCommand($uuid));
        }

        return response()->json(null, 204);
    }

    /**
     * @OA\Patch(
     *     path="/api/students/admin/{uuid}/restore",
     *     summary="Restore student",
     *     tags={"Students"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Restored"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function restore(string $uuid): JsonResponse
    {
        $this->restoreHandler->handle(new RestoreStudentCommand($uuid));

        return response()->json([
            'message' => 'Student restored successfully',
        ]);
    }
}
