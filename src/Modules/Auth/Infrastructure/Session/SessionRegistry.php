<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Session;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Active browser-session management over Laravel's `sessions` table
 * (prompt §6). Requires SESSION_DRIVER=database (the project's default); with a
 * Redis session driver the table is empty and listing returns nothing.
 */
final readonly class SessionRegistry
{
    /**
     * @return array<int, array{id: string, ip_address: ?string, user_agent: ?string, last_active_at: string, is_current: bool}>
     */
    public function listForUser(User $user, ?string $currentSessionId): array
    {
        return DB::table('sessions')
            ->where('user_id', $user->getKey())
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn (object $session): array => [
                'id' => (string) $session->id,
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'last_active_at' => CarbonImmutable::createFromTimestamp($session->last_activity)->toIso8601String(),
                'is_current' => (string) $session->id === $currentSessionId,
            ])
            ->all();
    }

    public function revoke(User $user, string $sessionId): void
    {
        DB::table('sessions')
            ->where('user_id', $user->getKey())
            ->where('id', $sessionId)
            ->delete();
    }

    public function revokeOthers(User $user, ?string $exceptSessionId): void
    {
        DB::table('sessions')
            ->where('user_id', $user->getKey())
            ->when($exceptSessionId, fn ($query) => $query->where('id', '!=', $exceptSessionId))
            ->delete();
    }

    public function revokeAll(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->getKey())->delete();
    }
}
