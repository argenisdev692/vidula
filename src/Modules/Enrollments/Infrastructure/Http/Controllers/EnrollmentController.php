<?php

declare(strict_types=1);

namespace Modules\Enrollments\Infrastructure\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Enrollments\Application\Commands\BulkDeleteEnrollmentsHandler;
use Modules\Enrollments\Application\Commands\BulkRestoreEnrollmentsHandler;
use Modules\Enrollments\Application\Commands\CreateEnrollmentHandler;
use Modules\Enrollments\Application\Commands\DeleteEnrollmentHandler;
use Modules\Enrollments\Application\Commands\RestoreEnrollmentHandler;
use Modules\Enrollments\Application\Commands\SyncAttendanceHandler;
use Modules\Enrollments\Application\Commands\UpdateEnrollmentHandler;
use Modules\Enrollments\Application\DTOs\EnrollmentData;
use Modules\Enrollments\Application\DTOs\EnrollmentFilterData;
use Modules\Enrollments\Application\DTOs\SyncAttendanceData;
use Modules\Enrollments\Application\Queries\GetAttendanceSheetHandler;
use Modules\Enrollments\Application\Queries\GetEnrollmentFormOptionsHandler;
use Modules\Enrollments\Application\Queries\GetEnrollmentHandler;
use Modules\Enrollments\Application\Queries\ListEnrollmentsHandler;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * Inertia/web primary surface for enrollments + attendance sheet.
 */
final readonly class EnrollmentController
{
    public function index(
        Request $request,
        ListEnrollmentsHandler $list,
        GetEnrollmentFormOptionsHandler $formOptions,
    ): InertiaResponse {
        $filters = EnrollmentFilterData::validateAndCreate($request);
        $options = $formOptions->handle();

        return Inertia::render('enrollments/Index', [
            'enrollments' => $list->handle($filters, min(max($request->integer('per_page', 15), 1), 100)),
            'filters' => $filters,
            'students' => $options['students'],
            'classrooms' => $options['classrooms'],
        ]);
    }

    public function show(string $uuid, GetEnrollmentHandler $get): InertiaResponse
    {
        return Inertia::render('enrollments/Show', [
            'enrollment' => $get->handle($uuid),
        ]);
    }

    public function store(EnrollmentData $data, CreateEnrollmentHandler $create): RedirectResponse
    {
        (void) $create->handle($data);

        return back()->with('success', __('Enrollment created.'));
    }

    public function update(
        string $uuid,
        EnrollmentData $data,
        GetEnrollmentHandler $get,
        UpdateEnrollmentHandler $update,
    ): RedirectResponse {
        (void) $update->handle($get->handle($uuid), $data);

        return back()->with('success', __('Enrollment updated.'));
    }

    public function destroy(string $uuid, DeleteEnrollmentHandler $delete): RedirectResponse
    {
        $delete->handle($uuid);

        return back()->with('success', __('Enrollment suspended.'));
    }

    public function restore(string $uuid, RestoreEnrollmentHandler $restore): RedirectResponse
    {
        $restore->handle($uuid);

        return back()->with('success', __('Enrollment restored.'));
    }

    public function bulkDelete(BulkUuidsData $data, BulkDeleteEnrollmentsHandler $handler): RedirectResponse
    {
        $count = $handler->handle($data);

        return back()->with('success', __(':count enrollments suspended.', ['count' => $count]));
    }

    public function bulkRestore(BulkUuidsData $data, BulkRestoreEnrollmentsHandler $handler): RedirectResponse
    {
        $count = $handler->handle($data);

        return back()->with('success', __(':count enrollments restored.', ['count' => $count]));
    }

    public function attendanceSheet(
        string $classroomUuid,
        GetAttendanceSheetHandler $get,
    ): InertiaResponse {
        return Inertia::render('enrollments/AttendanceSheet', $get->handle($classroomUuid));
    }

    public function syncAttendance(
        string $classroomUuid,
        SyncAttendanceData $data,
        SyncAttendanceHandler $sync,
    ): RedirectResponse {
        $count = $sync->handle($classroomUuid, $data);

        return back()->with('success', __(':count attendance marks saved.', ['count' => $count]));
    }
}
