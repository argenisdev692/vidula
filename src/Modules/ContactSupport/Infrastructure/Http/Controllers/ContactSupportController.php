<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\ContactSupport\Application\Commands\BulkDeleteContactSupportsHandler;
use Modules\ContactSupport\Application\Commands\BulkRestoreContactSupportsHandler;
use Modules\ContactSupport\Application\Commands\CreateContactSupportHandler;
use Modules\ContactSupport\Application\Commands\DeleteContactSupportHandler;
use Modules\ContactSupport\Application\Commands\MarkAllContactSupportsReadHandler;
use Modules\ContactSupport\Application\Commands\MarkContactSupportReadHandler;
use Modules\ContactSupport\Application\Commands\RestoreContactSupportHandler;
use Modules\ContactSupport\Application\Commands\UpdateContactSupportHandler;
use Modules\ContactSupport\Application\DTOs\ContactSupportData;
use Modules\ContactSupport\Application\DTOs\ContactSupportFilterData;
use Modules\ContactSupport\Application\Queries\GetContactSupportHandler;
use Modules\ContactSupport\Application\Queries\GetContactSupportNotificationsHandler;
use Modules\ContactSupport\Application\Queries\ListContactSupportsHandler;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * Contact-support inbox management. Every route is authorized via
 * `permission:*_CONTACT_SUPPORTS` middleware (not roles) — see Routes. Controller
 * stays thin: validate → handler → response, branching Inertia vs JSON.
 */
final readonly class ContactSupportController
{
    public function index(Request $request, ListContactSupportsHandler $list): InertiaResponse|JsonResponse
    {
        $filters = ContactSupportFilterData::validateAndCreate($request);
        $contactSupports = $list->handle($filters, min(max($request->integer('per_page', 15), 1), 100));

        return $request->expectsJson()
            ? response()->json($contactSupports)
            : Inertia::render('contact-supports/Index', ['contactSupports' => $contactSupports, 'filters' => $filters]);
    }

    /**
     * Navbar notification-bell feed: unread count + recent submissions.
     */
    public function notifications(GetContactSupportNotificationsHandler $get): JsonResponse
    {
        $feed = $get->handle();

        return response()->json([
            'unread_count' => $feed['unread_count'],
            'items' => $feed['items']->map(fn ($contactSupport) => [
                'uuid' => $contactSupport->uuid,
                'title' => $contactSupport->subject,
                'message' => Str::limit($contactSupport->message, 80),
                'time' => $contactSupport->created_at?->diffForHumans(),
                'unread' => ! $contactSupport->readed,
                'href' => route('contact-supports.show', $contactSupport->uuid),
            ])->values(),
        ]);
    }

    public function show(string $uuid, GetContactSupportHandler $get): InertiaResponse|JsonResponse
    {
        $contactSupport = $get->handle($uuid);

        return request()->expectsJson()
            ? response()->json(['data' => $contactSupport])
            : Inertia::render('contact-supports/Show', ['contactSupport' => $contactSupport]);
    }

    public function store(ContactSupportData $data, CreateContactSupportHandler $create): RedirectResponse
    {
        (void) $create->handle($data);

        return back()->with('success', __('Contact request created.'));
    }

    public function update(string $uuid, ContactSupportData $data, GetContactSupportHandler $get, UpdateContactSupportHandler $update): RedirectResponse
    {
        (void) $update->handle($get->handle($uuid), $data);

        return back()->with('success', __('Contact request updated.'));
    }

    public function markRead(string $uuid, MarkContactSupportReadHandler $markRead): RedirectResponse|JsonResponse
    {
        (void) $markRead->handle($uuid);

        return request()->expectsJson()
            ? response()->json(['success' => true])
            : back()->with('success', __('Contact request marked as read.'));
    }

    /**
     * Navbar notification-bell "mark all as read" action.
     */
    public function markAllRead(MarkAllContactSupportsReadHandler $markAll): RedirectResponse|JsonResponse
    {
        $count = $markAll->handle();

        return request()->expectsJson()
            ? response()->json(['updated' => $count])
            : back()->with('success', __(':count contact requests marked as read.', ['count' => $count]));
    }

    public function destroy(string $uuid, DeleteContactSupportHandler $delete): RedirectResponse
    {
        (void) $delete->handle($uuid);

        return back()->with('success', __('Contact request suspended.'));
    }

    public function restore(string $uuid, RestoreContactSupportHandler $restore): RedirectResponse
    {
        (void) $restore->handle($uuid);

        return back()->with('success', __('Contact request restored.'));
    }

    public function bulkDelete(BulkUuidsData $data, BulkDeleteContactSupportsHandler $handler): RedirectResponse
    {
        $count = $handler->handle($data);

        return back()->with('success', __(':count contact requests suspended.', ['count' => $count]));
    }

    public function bulkRestore(BulkUuidsData $data, BulkRestoreContactSupportsHandler $handler): RedirectResponse
    {
        $count = $handler->handle($data);

        return back()->with('success', __(':count contact requests restored.', ['count' => $count]));
    }
}
