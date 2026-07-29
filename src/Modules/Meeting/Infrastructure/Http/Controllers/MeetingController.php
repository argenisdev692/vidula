<?php

declare(strict_types=1);

namespace Modules\Meeting\Infrastructure\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;
use Modules\Meeting\Application\Commands\BulkDeleteMeetingsHandler;
use Modules\Meeting\Application\Commands\BulkRestoreMeetingsHandler;
use Modules\Meeting\Application\Commands\CancelMeetingHandler;
use Modules\Meeting\Application\Commands\CreateMeetingHandler;
use Modules\Meeting\Application\Commands\DeleteMeetingHandler;
use Modules\Meeting\Application\Commands\RestoreMeetingHandler;
use Modules\Meeting\Application\Commands\UpdateMeetingHandler;
use Modules\Meeting\Application\DTOs\CreateMeetingData;
use Modules\Meeting\Application\DTOs\MeetingFilterData;
use Modules\Meeting\Application\DTOs\UpdateMeetingData;
use Modules\Meeting\Application\Queries\GetMeetingHandler;
use Modules\Meeting\Application\Queries\ListMeetingsHandler;
use Modules\Meeting\Infrastructure\Attendees\AttendeeOptionMapper;
use Modules\Meeting\Infrastructure\Persistence\Eloquent\Models\MeetingEloquentModel;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * Internal meeting scheduling. Every route is authorized via
 * `permission:*_MEETINGS` middleware (not roles) — see Routes. Object-level
 * authorization (organizer-or-elevated-permission) is enforced per-action via
 * {@see self::authorizeMeetingAccess()} — the route permission gate alone is
 * NOT sufficient (OWASP API1/BOLA, research.md §5).
 */
final readonly class MeetingController
{
    public function index(Request $request, ListMeetingsHandler $list): InertiaResponse|JsonResponse
    {
        $filters = MeetingFilterData::validateAndCreate($request);
        $meetings = $list->handle($filters, min(max($request->integer('per_page', 15), 1), 100));

        return match ($request->expectsJson()) {
            true => response()->json($meetings),
            false => Inertia::render('meetings/Index', ['meetings' => $meetings, 'filters' => $filters]),
        };
    }

    public function show(Request $request, string $uuid, GetMeetingHandler $get): InertiaResponse
    {
        $meeting = $get->handle($uuid);
        $this->authorizeMeetingAccess($request, $meeting);

        return Inertia::render('meetings/Show', [
            'meeting' => $meeting,
            'attendeeLabels' => AttendeeOptionMapper::toOptions($meeting->attendees),
        ]);
    }

    public function create(Request $request): InertiaResponse
    {
        $prefill = null;

        if ($request->filled('lead')) {
            $prefill = $this->buildLeadPrefill((string) $request->string('lead'));
        }

        if ($request->filled('starts_at')) {
            $prefill ??= [];
            $startsAt = CarbonImmutable::parse((string) $request->string('starts_at'));
            $prefill['starts_at'] = $startsAt->toIso8601String();
            $prefill['ends_at'] = $startsAt->addMinutes((int) config('meeting.duration_minutes', 30))->toIso8601String();
        }

        return Inertia::render('meetings/Create', ['prefill' => $prefill]);
    }

    public function edit(Request $request, string $uuid, GetMeetingHandler $get): InertiaResponse|JsonResponse
    {
        $meeting = $get->handle($uuid);
        $this->authorizeMeetingAccess($request, $meeting);

        $payload = [
            'uuid' => $meeting->uuid,
            'title' => $meeting->title,
            'description' => $meeting->description,
            'starts_at' => $meeting->starts_at->toIso8601String(),
            'ends_at' => $meeting->ends_at->toIso8601String(),
            'attendees' => AttendeeOptionMapper::toOptions($meeting->attendees),
        ];

        return match ($request->expectsJson()) {
            true => response()->json(['data' => $payload]),
            false => Inertia::render('meetings/Edit', [
                'meeting' => $payload,
            ]),
        };
    }

    public function store(Request $request, CreateMeetingData $data, CreateMeetingHandler $create): RedirectResponse
    {
        (void) $create->handle($data, (int) $request->user()->id);

        return back()->with('success', __('Meeting created.'));
    }

    public function update(Request $request, string $uuid, UpdateMeetingData $data, GetMeetingHandler $get, UpdateMeetingHandler $update): RedirectResponse
    {
        $meeting = $get->handle($uuid);
        $this->authorizeMeetingAccess($request, $meeting);

        (void) $update->handle($meeting, $data);

        return back()->with('success', __('Meeting updated.'));
    }

    public function cancel(Request $request, string $uuid, GetMeetingHandler $get, CancelMeetingHandler $cancel): RedirectResponse
    {
        $meeting = $get->handle($uuid);
        $this->authorizeMeetingAccess($request, $meeting);

        (void) $cancel->handle($meeting);

        return back()->with('success', __('Meeting cancelled.'));
    }

    public function destroy(Request $request, string $uuid, GetMeetingHandler $get, DeleteMeetingHandler $delete): RedirectResponse
    {
        $this->authorizeMeetingAccess($request, $get->handle($uuid));

        (void) $delete->handle($uuid);

        return back()->with('success', __('Meeting deleted.'));
    }

    public function restore(Request $request, string $uuid, GetMeetingHandler $get, RestoreMeetingHandler $restore): RedirectResponse
    {
        $this->authorizeMeetingAccess($request, $get->handle($uuid));

        (void) $restore->handle($uuid);

        return back()->with('success', __('Meeting restored.'));
    }

    public function bulkDelete(Request $request, BulkUuidsData $data, BulkDeleteMeetingsHandler $handler): RedirectResponse
    {
        $user = $request->user();
        $count = $handler->handle(
            $data,
            (int) $user->id,
            $user->hasPermissionTo('VIEW_ANY_MEETINGS'),
        );

        return back()->with('success', __(':count meetings deleted.', ['count' => $count]));
    }

    public function bulkRestore(Request $request, BulkUuidsData $data, BulkRestoreMeetingsHandler $handler): RedirectResponse
    {
        $user = $request->user();
        $count = $handler->handle(
            $data,
            (int) $user->id,
            $user->hasPermissionTo('VIEW_ANY_MEETINGS'),
        );

        return back()->with('success', __(':count meetings restored.', ['count' => $count]));
    }

    /**
     * Object-level authorization gate (OWASP API1/BOLA): the route's
     * `permission:UPDATE_MEETINGS`-style gate only proves the caller holds
     * SOME grant on the module — it does not prove they own THIS meeting. A
     * caller passes only if they are the organizer OR hold the elevated
     * `VIEW_ANY_MEETINGS` permission (mirrors how `UPDATE_MEETINGS` alone is
     * NOT enough to edit someone else's meeting).
     */
    private function authorizeMeetingAccess(Request $request, MeetingEloquentModel $meeting): void
    {
        $user = $request->user();

        abort_if(
            $user === null || (! $user->hasPermissionTo('VIEW_ANY_MEETINGS') && $meeting->organizer_id !== $user->id),
            403,
        );
    }

    /**
     * @return array{title: string, attendees: array<int, array{type: string, uuid: string, label: string}>, starts_at?: string, ends_at?: string}|null
     */
    private function buildLeadPrefill(string $leadUuid): ?array
    {
        $lead = AppointmentEloquentModel::query()
            ->where('uuid', $leadUuid)
            ->first(['uuid', 'first_name', 'last_name', 'company_name', 'scheduled_at']);

        if ($lead === null) {
            return null;
        }

        $label = trim("{$lead->first_name} {$lead->last_name}");
        if ($lead->company_name) {
            $label .= " ({$lead->company_name})";
        }

        $prefill = [
            'title' => 'Meeting with '.trim("{$lead->first_name} {$lead->last_name}"),
            'attendees' => [[
                'type' => 'lead',
                'uuid' => $lead->uuid,
                'label' => $label,
            ]],
        ];

        if ($lead->scheduled_at !== null) {
            $prefill['starts_at'] = $lead->scheduled_at->toIso8601String();
            $prefill['ends_at'] = $lead->scheduled_at->copy()
                ->addMinutes((int) config('meeting.duration_minutes', 30))
                ->toIso8601String();
        }

        return $prefill;
    }
}
