<?php

declare(strict_types=1);

namespace Modules\Students\Infrastructure\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Students\Application\Commands\BulkDeleteStudentsHandler;
use Modules\Students\Application\Commands\BulkRestoreStudentsHandler;
use Modules\Students\Application\Commands\CreateStudentHandler;
use Modules\Students\Application\Commands\DeleteStudentHandler;
use Modules\Students\Application\Commands\RestoreStudentHandler;
use Modules\Students\Application\Commands\UpdateStudentHandler;
use Modules\Students\Application\DTOs\StudentData;
use Modules\Students\Application\DTOs\StudentFilterData;
use Modules\Students\Application\Queries\GetStudentHandler;
use Modules\Students\Application\Queries\ListStudentsHandler;
use Modules\Students\Domain\Ports\StudentRepositoryPort;
use Modules\Students\Infrastructure\Persistence\Eloquent\Models\StudentEloquentModel;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * LMS students management. Authorization via `permission:*_STUDENTS` middleware.
 * Thin: validate → handler → Inertia or JSON.
 */
final readonly class StudentController
{
    public function index(Request $request, ListStudentsHandler $list): InertiaResponse|JsonResponse
    {
        $filters = StudentFilterData::validateAndCreate($request);
        $students = $list->handle($filters, min(max($request->integer('per_page', 15), 1), 100));

        return match ($request->expectsJson()) {
            true => response()->json($students),
            false => Inertia::render('students/Index', ['students' => $students, 'filters' => $filters]),
        };
    }

    public function show(Request $request, string $uuid, GetStudentHandler $get): InertiaResponse|JsonResponse
    {
        $student = $get->handle($uuid);

        return match ($request->expectsJson()) {
            true => response()->json(['data' => $student]),
            false => Inertia::render('students/Show', ['student' => $student]),
        };
    }

    public function store(StudentData $data, CreateStudentHandler $create): RedirectResponse
    {
        (void) $create->handle($data);

        return back()->with('success', __('Student created.'));
    }

    public function update(string $uuid, StudentData $data, StudentRepositoryPort $students, UpdateStudentHandler $update): RedirectResponse
    {
        $student = $students->findByUuid($uuid)
            ?? throw (new ModelNotFoundException)->setModel(StudentEloquentModel::class, [$uuid]);

        (void) $update->handle($student, $data);

        return back()->with('success', __('Student updated.'));
    }

    public function destroy(string $uuid, DeleteStudentHandler $delete): RedirectResponse
    {
        (void) $delete->handle($uuid);

        return back()->with('success', __('Student suspended.'));
    }

    public function restore(string $uuid, RestoreStudentHandler $restore): RedirectResponse
    {
        (void) $restore->handle($uuid);

        return back()->with('success', __('Student restored.'));
    }

    public function bulkDelete(BulkUuidsData $data, BulkDeleteStudentsHandler $handler): RedirectResponse
    {
        $count = $handler->handle($data);

        return back()->with('success', __(':count students suspended.', ['count' => $count]));
    }

    public function bulkRestore(BulkUuidsData $data, BulkRestoreStudentsHandler $handler): RedirectResponse
    {
        $count = $handler->handle($data);

        return back()->with('success', __(':count students restored.', ['count' => $count]));
    }
}
