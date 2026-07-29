<?php

declare(strict_types=1);

namespace Modules\Appointment\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Appointment\Application\Commands\AddFollowUpCallHandler;
use Modules\Appointment\Application\Commands\BulkDeleteAppointmentsHandler;
use Modules\Appointment\Application\Commands\BulkRestoreAppointmentsHandler;
use Modules\Appointment\Application\Commands\CancelAppointmentHandler;
use Modules\Appointment\Application\Commands\ConfirmAppointmentHandler;
use Modules\Appointment\Application\Commands\CreateAppointmentHandler;
use Modules\Appointment\Application\Commands\DeleteAppointmentHandler;
use Modules\Appointment\Application\Commands\MarkAllAppointmentsReadHandler;
use Modules\Appointment\Application\Commands\MarkAppointmentReadHandler;
use Modules\Appointment\Application\Commands\RescheduleAppointmentHandler;
use Modules\Appointment\Application\Commands\RestoreAppointmentHandler;
use Modules\Appointment\Application\Commands\UpdateAppointmentHandler;
use Modules\Appointment\Application\DTOs\AddFollowUpCallData;
use Modules\Appointment\Application\DTOs\AppointmentData;
use Modules\Appointment\Application\DTOs\AppointmentFilterData;
use Modules\Appointment\Application\DTOs\CancelAppointmentData;
use Modules\Appointment\Application\DTOs\ConfirmAppointmentData;
use Modules\Appointment\Application\DTOs\RescheduleAppointmentData;
use Modules\Appointment\Application\Queries\GetAppointmentHandler;
use Modules\Appointment\Application\Queries\GetAppointmentNotificationsHandler;
use Modules\Appointment\Application\Queries\ListAppointmentsHandler;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * Appointment pipeline management. Every route is authorized via
 * `permission:*_APPOINTMENTS` middleware (not roles) — see Routes. Controller
 * stays thin: validate → handler → response, branching Inertia vs JSON
 * (Controller Fusion Rule).
 */
final readonly class AppointmentController
{
    public function index(Request $request, ListAppointmentsHandler $list): InertiaResponse|JsonResponse
    {
        $filters = AppointmentFilterData::validateAndCreate($request);
        $appointments = $list->handle($filters, min(max($request->integer('per_page', 15), 1), 100));

        return match ($request->expectsJson()) {
            true => response()->json($appointments),
            false => Inertia::render('appointments/Index', [
                'appointments' => $appointments,
                'filters' => $filters,
            ]),
        };
    }

    /**
     * Navbar notification-bell feed: unread count + recent leads.
     */
    public function notifications(GetAppointmentNotificationsHandler $get): JsonResponse
    {
        $feed = $get->handle();

        return response()->json([
            'unread_count' => $feed['unread_count'],
            'items' => $feed['items']->map(fn ($appointment) => [
                'uuid' => $appointment->uuid,
                'title' => trim("{$appointment->first_name} {$appointment->last_name}"),
                'message' => $appointment->company_name !== null
                    ? "{$appointment->company_name} — requested {$appointment->scheduled_at?->format('M j, g:ia')}"
                    : "Requested {$appointment->scheduled_at?->format('M j, g:ia')}",
                'time' => $appointment->created_at?->diffForHumans(),
                'unread' => ! $appointment->readed,
                'href' => route('appointments.show', $appointment->uuid),
            ])->values(),
        ]);
    }

    public function show(string $uuid, GetAppointmentHandler $get): InertiaResponse|JsonResponse
    {
        $appointment = $get->handle($uuid);

        return match (request()->expectsJson()) {
            true => response()->json(['data' => $appointment]),
            false => Inertia::render('appointments/Show', ['appointment' => $appointment]),
        };
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('appointments/Create');
    }

    public function edit(Request $request, string $uuid, GetAppointmentHandler $get): InertiaResponse|JsonResponse
    {
        // Project only the editable lead-profile columns (AppointmentData's field
        // set) — pipeline state (status_lead, meeting_status, scheduled_at, …)
        // never rides on this form, it changes only through the dedicated actions.
        $payload = $get->handle($uuid)->only([
            'uuid',
            'first_name',
            'last_name',
            'client_type',
            'company_name',
            'project_type',
            'email',
            'phone',
            'address',
            'address_2',
            'zip_code',
            'city',
            'state',
            'country',
            'country_code',
            'latitude',
            'longitude',
            'sms_consent',
            'notes',
            'owner',
        ]);

        return match ($request->expectsJson()) {
            true => response()->json(['data' => $payload]),
            false => Inertia::render('appointments/Edit', [
                'appointment' => $payload,
            ]),
        };
    }

    public function store(Request $request, AppointmentData $data, CreateAppointmentHandler $create): RedirectResponse
    {
        $scheduledAt = $request->validate(['scheduled_at' => ['nullable', 'date']])['scheduled_at'] ?? null;

        (void) $create->handle($data, $scheduledAt);

        return back()->with('success', __('Lead created.'));
    }

    public function update(string $uuid, AppointmentData $data, GetAppointmentHandler $get, UpdateAppointmentHandler $update): RedirectResponse
    {
        (void) $update->handle($get->handle($uuid), $data);

        return back()->with('success', __('Lead updated.'));
    }

    public function markRead(string $uuid, MarkAppointmentReadHandler $markRead): RedirectResponse|JsonResponse
    {
        (void) $markRead->handle($uuid);

        return match (request()->expectsJson()) {
            true => response()->json(['success' => true]),
            false => back()->with('success', __('Lead marked as read.')),
        };
    }

    /**
     * Navbar notification-bell "mark all as read" action.
     */
    public function markAllRead(MarkAllAppointmentsReadHandler $markAll): RedirectResponse|JsonResponse
    {
        $count = $markAll->handle();

        return match (request()->expectsJson()) {
            true => response()->json(['updated' => $count]),
            false => back()->with('success', __(':count leads marked as read.', ['count' => $count])),
        };
    }

    public function confirm(string $uuid, ConfirmAppointmentData $data, GetAppointmentHandler $get, ConfirmAppointmentHandler $confirm): RedirectResponse
    {
        (void) $confirm->handle($get->handle($uuid), $data);

        return back()->with('success', __('Appointment confirmed.'));
    }

    public function reschedule(string $uuid, RescheduleAppointmentData $data, GetAppointmentHandler $get, RescheduleAppointmentHandler $reschedule): RedirectResponse
    {
        (void) $reschedule->handle($get->handle($uuid), $data);

        return back()->with('success', __('Appointment rescheduled.'));
    }

    public function cancel(string $uuid, CancelAppointmentData $data, GetAppointmentHandler $get, CancelAppointmentHandler $cancel): RedirectResponse
    {
        (void) $cancel->handle($get->handle($uuid), $data);

        return back()->with('success', __('Appointment cancelled.'));
    }

    public function addFollowUpCall(string $uuid, AddFollowUpCallData $data, GetAppointmentHandler $get, AddFollowUpCallHandler $handler): RedirectResponse
    {
        (void) $handler->handle($get->handle($uuid), $data);

        return back()->with('success', __('Follow-up call logged.'));
    }

    public function destroy(string $uuid, DeleteAppointmentHandler $delete): RedirectResponse
    {
        (void) $delete->handle($uuid);

        return back()->with('success', __('Lead suspended.'));
    }

    public function restore(string $uuid, RestoreAppointmentHandler $restore): RedirectResponse
    {
        (void) $restore->handle($uuid);

        return back()->with('success', __('Lead restored.'));
    }

    public function bulkDelete(BulkUuidsData $data, BulkDeleteAppointmentsHandler $handler): RedirectResponse
    {
        $count = $handler->handle($data);

        return back()->with('success', __(':count leads suspended.', ['count' => $count]));
    }

    public function bulkRestore(BulkUuidsData $data, BulkRestoreAppointmentsHandler $handler): RedirectResponse
    {
        $count = $handler->handle($data);

        return back()->with('success', __(':count leads restored.', ['count' => $count]));
    }
}
