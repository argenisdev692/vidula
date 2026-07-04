<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * @group Authentication
 *
 * API "active sessions" = the caller's Sanctum personal access tokens. Lets a
 * mobile/external client list and revoke its device tokens (prompt §6).
 */
final readonly class TokenController
{
    /**
     * List the caller's active API tokens
     *
     * @authenticated
     *
     * @response 200 scenario="Success" {"data":[{"id":1,"name":"iphone-15","current":true,"last_used_at":null,"expires_at":"2026-06-23T10:00:00+00:00"}]}
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $current = $user->currentAccessToken();
        $currentId = $current instanceof PersonalAccessToken ? $current->getKey() : null;

        $tokens = $user->tokens()
            ->orderByDesc('last_used_at')
            ->get(['id', 'name', 'last_used_at', 'created_at', 'expires_at'])
            ->map(fn ($token): array => [
                'id' => $token->id,
                'name' => $token->name,
                'current' => $token->id === $currentId,
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'created_at' => $token->created_at?->toIso8601String(),
                'expires_at' => $token->expires_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $tokens]);
    }

    /**
     * Revoke a specific API token
     *
     * @authenticated
     *
     * @response 200 {"message":"Token revoked."}
     */
    public function destroy(Request $request, string $token): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->tokens()->where('id', $token)->delete();

        return response()->json(['message' => __('Token revoked.')]);
    }

    /**
     * Revoke all API tokens except the current one
     *
     * @authenticated
     *
     * @response 200 {"message":"Other tokens revoked."}
     */
    public function destroyOthers(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $current = $user->currentAccessToken();
        $currentId = $current instanceof PersonalAccessToken ? $current->getKey() : null;

        $user->tokens()
            ->when($currentId, fn ($query) => $query->where('id', '!=', $currentId))
            ->delete();

        return response()->json(['message' => __('Other tokens revoked.')]);
    }
}
