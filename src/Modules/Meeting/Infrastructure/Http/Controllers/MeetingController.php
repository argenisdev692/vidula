<?php

declare(strict_types=1);

namespace Modules\Meeting\Infrastructure\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
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
    public function index(Request $request, ListMeetingsHandler $list): InertiaResponse
    {
        $filters = MeetingFilterData::validateAndCreate($request);
        $meetings = $list->handle($filters, min(max($request->integer('per_page', 15), 1), 100));

        return Inertia::render('meetings/Index', ['meetings' => $meetings, 'filters' => $filters]);
    }

    public function show(Request $request, string $uuid, GetMeetingHandler $get): InertiaResponse
    {
        $meeting = $get->handle($uuid);
        $this->authorizeMeetingAccess($request, $meeting);

        return Inertia::render('meetings/Show', ['meeting' => $meeting]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('meetings/Create');
    }

    public function edit(Request $request, string $uuid, GetMeetingHandler $get): InertiaResponse
    {
        $meeting = $get->handle($uuid);
        $this->authorizeMeetingAccess($request, $meeting);

        return Inertia::render('meetings/Edit', [
            'meeting' => [
                'uuid' => $meeting->uuid,
                'title' => $meeting->title,
                'description' => $meeting->description,
                'starts_at' => $meeting->starts_at->toIso8601String(),
                'ends_at' => $meeting->ends_at->toIso8601String(),
                'attendees' => AttendeeOptionMapper::toOptions($meeting->attendees),
            ],
        ]);
    }

    public function store(Request $request, CreateMeetingData $data, CreateMeetingHandler $create): RedirectResponse
    {
        $meeting = $create->handle($data, (int) $request->user()->id);

        return redirect()->route('meetings.show', $meeting->uuid)->with('success', __('Meeting created.'));
    }

    public function update(Request $request, string $uuid, UpdateMeetingData $data, GetMeetingHandler $get, UpdateMeetingHandler $update): RedirectResponse
    {
        $meeting = $get->handle($uuid);
        $this->authorizeMeetingAccess($request, $meeting);

        $update->handle($meeting, $data);

        return redirect()->route('meetings.show', $uuid)->with('success', __('Meeting updated.'));
    }

    public function cancel(Request $request, string $uuid, GetMeetingHandler $get, CancelMeetingHandler $cancel): RedirectResponse
    {
        $meeting = $get->handle($uuid);
        $this->authorizeMeetingAccess($request, $meeting);

        $cancel->handle($meeting);

        return back()->with('success', __('Meeting cancelled.'));
    }

    public function destroy(Request $request, string $uuid, GetMeetingHandler $get, DeleteMeetingHandler $delete): RedirectResponse
    {
        $this->authorizeMeetingAccess($request, $get->handle($uuid));

        $delete->handle($uuid);

        return back()->with('success', __('Meeting deleted.'));
    }

    public function restore(Request $request, string $uuid, GetMeetingHandler $get, RestoreMeetingHandler $restore): RedirectResponse
    {
        $this->authorizeMeetingAccess($request, $get->handle($uuid));

        $restore->handle($uuid);

        return back()->with('success', __('Meeting restored.'));
    }

    public function bulkDelete(BulkUuidsData $data, BulkDeleteMeetingsHandler $handler): RedirectResponse
    {
        $count = $handler->handle($data);

        return back()->with('success', __(':count meetings deleted.', ['count' => $count]));
    }

    public function bulkRestore(BulkUuidsData $data, BulkRestoreMeetingsHandler $handler): RedirectResponse
    {
        $count = $handler->handle($data);

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
}
