<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ContactSupport\Application\DTOs\ContactSupportFilterData;
use Modules\ContactSupport\Application\Queries\GetContactSupportHandler;
use Modules\ContactSupport\Application\Queries\ListContactSupportsHandler;

/**
 * @group Contact Support
 *
 * API endpoints for contact-support lookup. Secondary documentation surface for
 * Sanctum-authenticated clients. The primary UI remains Inertia/web.
 */
final readonly class ContactSupportApiController
{
    /**
     * List contact requests.
     *
     * Returns a paginated list of contact-support submissions.
     *
     * @authenticated
     *
     * @queryParam per_page int Page size. Example: 15
     * @queryParam read string Filter by read state (read|unread). Example: unread
     *
     * @response 403 {"message":"This action is unauthorized."}
     */
    public function index(Request $request, ListContactSupportsHandler $list): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('VIEW_ANY_CONTACT_SUPPORTS'), 403);

        $filters = ContactSupportFilterData::validateAndCreate($request);

        return response()->json($list->handle($filters, (int) $request->integer('per_page', 15)));
    }

    /**
     * Show a contact request.
     *
     * Returns a single contact-support submission by UUID.
     *
     * @authenticated
     *
     * @urlParam uuid string required The contact-support UUID. Example: 00000000-0000-0000-0000-000000000000
     *
     * @response 403 {"message":"This action is unauthorized."}
     * @response 404 {"message":"Not found."}
     */
    public function show(Request $request, string $uuid, GetContactSupportHandler $get): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('VIEW_CONTACT_SUPPORTS'), 403);

        return response()->json(['data' => $get->handle($uuid)]);
    }
}
