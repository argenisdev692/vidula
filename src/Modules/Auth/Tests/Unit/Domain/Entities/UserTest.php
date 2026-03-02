<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit\Domain\Entities;

use Modules\Auth\Domain\Entities\User;
use Modules\Auth\Domain\Events\UserCreated;
use Modules\Auth\Domain\Events\UserUpdated;
use Modules\Auth\Domain\Events\UserEmailChanged;
use PHPUnit\Framework\TestCase;

/**
 * UserTest — Tests for User entity with PHP 8.5 clone with.
 */
final class UserTest extends TestCase
{
    public function test_creates_user_with_factory_method(): void
    {
        $user = User::create(
            uuid: 'test-uuid',
            name: 'John',
            email: 'john@example.com',
            username: 'john_doe',
        );
        
        $this->assertEquals('test-uuid', $user->uuid);
        $this->assertEquals('John', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals('john_doe', $user->username);
    }

    public function test_create_emits_user_created_event(): void
    {
        $user = User::create(
            uuid: 'test-uuid',
            name: 'John',
            email: 'john@example.com',
        );
        
        $events = $user->pullDomainEvents();
        
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserCreated::class, $events[0]);
    }

    public function test_updates_profile_with_clone_with(): void
    {
        $user = new User(
            id: 1,
            uuid: 'test-uuid',
            name: 'John',
            email: 'john@example.com',
        );
        
        $updatedUser = $user->updateProfile(
            name: 'Jane',
            lastName: 'Doe',
            phone: '+1234567890',
        );
        
        // Original user unchanged (immutable)
        $this->assertEquals('John', $user->name);
        $this->assertNull($user->lastName);
        
        // Updated user has new values
        $this->assertEquals('Jane', $updatedUser->name);
        $this->assertEquals('Doe', $updatedUser->lastName);
        $this->assertEquals('+1234567890', $updatedUser->phone);
    }

    public function test_update_profile_emits_user_updated_event(): void
    {
        $user = new User(
            id: 1,
            uuid: 'test-uuid',
            name: 'John',
        );
        
        $updatedUser = $user->updateProfile(name: 'Jane');
        $events = $updatedUser->pullDomainEvents();
        
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserUpdated::class, $events[0]);
    }

    public function test_changes_email_with_clone_with(): void
    {
        $user = new User(
            id: 1,
            uuid: 'test-uuid',
            name: 'John',
            email: 'john@example.com',
            isEmailVerified: true,
        );
        
        $updatedUser = $user->changeEmail('newemail@example.com');
        
        // Original user unchanged
        $this->assertEquals('john@example.com', $user->email);
        $this->assertTrue($user->isEmailVerified);
        
        // Updated user has new email and unverified status
        $this->assertEquals('newemail@example.com', $updatedUser->email);
        $this->assertFalse($updatedUser->isEmailVerified);
    }

    public function test_change_email_emits_user_email_changed_event(): void
    {
        $user = new User(
            id: 1,
            uuid: 'test-uuid',
            name: 'John',
            email: 'john@example.com',
        );
        
        $updatedUser = $user->changeEmail('newemail@example.com');
        $events = $updatedUser->pullDomainEvents();
        
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserEmailChanged::class, $events[0]);
    }

    public function test_verifies_email_with_clone_with(): void
    {
        $user = new User(
            id: 1,
            uuid: 'test-uuid',
            name: 'John',
            email: 'john@example.com',
            isEmailVerified: false,
        );
        
        $verifiedUser = $user->verifyEmail();
        
        // Original user unchanged
        $this->assertFalse($user->isEmailVerified);
        
        // Verified user has verified status
        $this->assertTrue($verifiedUser->isEmailVerified);
    }

    public function test_updates_avatar_with_clone_with(): void
    {
        $user = new User(
            id: 1,
            uuid: 'test-uuid',
            name: 'John',
        );
        
        $updatedUser = $user->updateAvatar('/path/to/avatar.jpg');
        
        // Original user unchanged
        $this->assertNull($user->profilePhotoPath);
        
        // Updated user has avatar
        $this->assertEquals('/path/to/avatar.jpg', $updatedUser->profilePhotoPath);
    }

    public function test_removes_avatar_with_clone_with(): void
    {
        $user = new User(
            id: 1,
            uuid: 'test-uuid',
            name: 'John',
            profilePhotoPath: '/path/to/avatar.jpg',
        );
        
        $updatedUser = $user->removeAvatar();
        
        // Original user unchanged
        $this->assertEquals('/path/to/avatar.jpg', $user->profilePhotoPath);
        
        // Updated user has no avatar
        $this->assertNull($updatedUser->profilePhotoPath);
    }
}
