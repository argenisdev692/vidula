<?php

declare(strict_types=1);

namespace Modules\Student\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Student\Application\Commands\CreateStudent\CreateStudentCommand;
use Modules\Student\Application\Commands\CreateStudent\CreateStudentHandler;
use Modules\Student\Application\Commands\DeleteStudent\DeleteStudentCommand;
use Modules\Student\Application\Commands\DeleteStudent\DeleteStudentHandler;
use Modules\Student\Application\Commands\RestoreStudent\RestoreStudentCommand;
use Modules\Student\Application\Commands\RestoreStudent\RestoreStudentHandler;
use Modules\Student\Application\Commands\UpdateStudent\UpdateStudentCommand;
use Modules\Student\Application\Commands\UpdateStudent\UpdateStudentHandler;
use Modules\Student\Application\DTOs\StudentFilterDTO;
use Modules\Student\Application\DTOs\CreateStudentDTO;
use Modules\Student\Application\DTOs\UpdateStudentDTO;
use Modules\Student\Application\Queries\GetStudent\GetStudentHandler;
use Modules\Student\Application\Queries\GetStudent\GetStudentQuery;
use Modules\Student\Application\Queries\ListStudent\ListStudentHandler;
use Modules\Student\Application\Queries\ListStudent\ListStudentQuery;
use Modules\Student\Infrastructure\Http\Requests\CreateStudentRequest;
use Modules\Student\Infrastructure\Http\Requests\UpdateStudentRequest;
use Modules\Student\Infrastructure\Persistence\Export\StudentExcelExport;
use Modules\Student\Infrastructure\Persistence\Export\StudentPdfExport;
use Maatwebsite\Excel\Facades\Excel;
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

    public function export(Request $request): mixed
    {
        $format = $request->query('format', 'excel');
        $filters = StudentFilterDTO::from($request->all());
        $query = new ListStudentQuery($filters);

        if ($format === 'pdf') {
            $pdfExport = new StudentPdfExport($this->listHandler, $query);
            return $pdfExport->export();
        }

        $excelExport = new StudentExcelExport($this->listHandler, $query);
        return Excel::download($excelExport, 'students.xlsx');
    }

    public function index(Request $request): JsonResponse
    {
        $filters = StudentFilterDTO::from($request->all());
        $result = $this->listHandler->handle(new ListStudentQuery($filters));

        return response()->json($result);
    }

    public function show(Request $request, ?string $uuid = null): JsonResponse
    {
        $targetUuid = $uuid ?? $request->user()?->uuid;

        if (!$targetUuid) {
            return response()->json(['message' => 'User context not found'], 401);
        }

        $result = $this->getHandler->handle(new GetStudentQuery($targetUuid));

        return response()->json(['data' => $result]);
    }

    public function store(CreateStudentRequest $request): JsonResponse
    {
        $dto = CreateStudentDTO::from($request->validated());
        $this->createHandler->handle(new CreateStudentCommand($dto));

        return response()->json([
            'message' => 'Student created successfully',
        ], 201);
    }

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

    public function destroy(string $uuid): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteStudentCommand($uuid));

        return response()->json([
            'message' => 'Student deleted successfully',
        ]);
    }

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

    public function restore(string $uuid): JsonResponse
    {
        $this->restoreHandler->handle(new RestoreStudentCommand($uuid));

        return response()->json([
            'message' => 'Student restored successfully',
        ]);
    }
}
