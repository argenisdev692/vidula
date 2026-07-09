<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\ContactSupport\Application\Commands\BulkDeleteContactSupportsHandler;
use Modules\ContactSupport\Application\Commands\BulkRestoreContactSupportsHandler;
use Modules\ContactSupport\Application\Commands\CreateContactSupportHandler;
use Modules\ContactSupport\Application\Commands\DeleteContactSupportHandler;
use Modules\ContactSupport\Application\Commands\MarkContactSupportReadHandler;
use Modules\ContactSupport\Application\Commands\RestoreContactSupportHandler;
use Modules\ContactSupport\Application\Commands\UpdateContactSupportHandler;
use Modules\ContactSupport\Application\DTOs\ContactSupportData;
use Modules\ContactSupport\Application\DTOs\ContactSupportFilterData;
use Modules\ContactSupport\Application\Queries\GetContactSupportHandler;
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
        $contactSupports = $list->handle($filters, (int) $request->integer('per_page', 15));

        return $request->expectsJson()
            ? response()->json($contactSupports)
            : Inertia::render('contact-supports/Index', ['contactSupports' => $contactSupports, 'filters' => $filters]);
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
        $create->handle($data);

        return back()->with('success', __('Contact request created.'));
    }

    public function update(string $uuid, ContactSupportData $data, GetContactSupportHandler $get, UpdateContactSupportHandler $update): RedirectResponse
    {
        $update->handle($get->handle($uuid), $data);

        return back()->with('success', __('Contact request updated.'));
    }

    public function markRead(string $uuid, MarkContactSupportReadHandler $markRead): RedirectResponse
    {
        $markRead->handle($uuid);

        return back()->with('success', __('Contact request marked as read.'));
    }

    public function destroy(string $uuid, DeleteContactSupportHandler $delete): RedirectResponse
    {
        $delete->handle($uuid);

        return back()->with('success', __('Contact request suspended.'));
    }

    public function restore(string $uuid, RestoreContactSupportHandler $restore): RedirectResponse
    {
        $restore->handle($uuid);

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
