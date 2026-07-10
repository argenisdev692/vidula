<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ContactSupport\Application\DTOs\ContactSupportFilterData;
use Modules\ContactSupport\Application\Queries\GetContactSupportHandler;
use Modules\ContactSupport\Application\Queries\ListContactSupportsHandler;

/**
 * API endpoints for contact-support lookup. Secondary Sanctum-authenticated
 * surface; the primary UI remains Inertia/web. Documented by Scramble via return
 * types + `auth:sanctum` detection — no manual annotations.
 */
final readonly class ContactSupportApiController
{
    /**
     * List contact requests.
     *
     * Returns a paginated list of contact-support submissions. `per_page` is
     * capped at 100 to bound resource consumption (OWASP API4).
     */
    public function index(Request $request, ListContactSupportsHandler $list): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('VIEW_ANY_CONTACT_SUPPORTS'), 403);

        $filters = ContactSupportFilterData::validateAndCreate($request);

        return response()->json($list->handle($filters, min(max($request->integer('per_page', 15), 1), 100)));
    }

    /**
     * Show a contact request.
     *
     * Returns a single contact-support submission by UUID.
     */
    public function show(Request $request, string $uuid, GetContactSupportHandler $get): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('VIEW_CONTACT_SUPPORTS'), 403);

        return response()->json(['data' => $get->handle($uuid)]);
    }
}
