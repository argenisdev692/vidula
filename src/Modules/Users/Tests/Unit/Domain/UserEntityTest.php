<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Unit\Domain;

use Modules\Users\Domain\Entities\User;
use Modules\Users\Domain\Enums\UserStatus;
use Modules\Users\Domain\ValueObjects\UserId;
use PHPUnit\Framework\TestCase;

/**
 * UserEntityTest — Domain invariants for the User aggregate root.
 */
final class UserEntityTest extends TestCase
{
    private function createUser(UserStatus $status = UserStatus::Active): User
    {
        return new User(
            id: new UserId(1),
            uuid: 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            name: 'John',
            lastName: 'Doe',
            email: 'john@example.com',
            username: 'johndoe',
            status: $status,
            createdAt: '2026-01-01T00:00:00+00:00',
            updatedAt: '2026-01-01T00:00:00+00:00',
        );
    }

    public function test_full_name_returns_trimmed_concatenation(): void
    {
        $user = $this->createUser();
        $this->assertSame('John Doe', $user->fullName());
    }

    public function test_full_name_trims_when_last_name_is_null(): void
    {
        $user = new User(
            id: new UserId(1),
            uuid: 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            name: 'Jane',
        );
        $this->assertSame('Jane', $user->fullName());
    }

    public function test_soft_delete_sets_deleted_status_and_deleted_at(): void
    {
        $user = $this->createUser();
        $deleted = $user->softDelete();

        $this->assertSame(UserStatus::Deleted, $deleted->status);
        $this->assertNotNull($deleted->deletedAt);
        // Original user is NOT modified (immutability via clone)
        $this->assertSame(UserStatus::Active, $user->status);
    }

    public function test_suspend_sets_suspended_status(): void
    {
        $user = $this->createUser();
        $suspended = $user->suspend();

        $this->assertSame(UserStatus::Suspended, $suspended->status);
        $this->assertSame(UserStatus::Active, $user->status);
    }

    public function test_activate_sets_active_status_and_clears_deleted_at(): void
    {
        $user = $this->createUser(UserStatus::Suspended);
        $activated = $user->activate();

        $this->assertSame(UserStatus::Active, $activated->status);
        $this->assertNull($activated->deletedAt);
    }

    public function test_ban_sets_banned_status(): void
    {
        $user = $this->createUser();
        $banned = $user->ban();

        $this->assertSame(UserStatus::Banned, $banned->status);
    }

    public function test_clone_preserves_immutability(): void
    {
        $user = $this->createUser();
        $suspended = $user->suspend();

        // Verify they are different objects
        $this->assertNotSame($user, $suspended);
        // Verify original is unchanged
        $this->assertSame(UserStatus::Active, $user->status);
    }
}
