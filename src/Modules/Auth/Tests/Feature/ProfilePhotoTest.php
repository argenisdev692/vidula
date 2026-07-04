<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_upload_a_profile_photo(): void
    {
        Storage::fake('r2');
        $user = User::factory()->create(['profile_photo_path' => null]);

        $this->actingAs($user)
            ->post('/profile/photo', ['photo' => UploadedFile::fake()->image('avatar.jpg', 200, 200)])
            ->assertSessionHas('status', 'profile-photo-updated');

        $user->refresh();

        $this->assertNotNull($user->profile_photo_path);
        $this->assertStringStartsWith("profile-photos/{$user->uuid}/", $user->profile_photo_path);
        Storage::disk('r2')->assertExists($user->profile_photo_path);
    }

    public function test_uploading_a_new_photo_deletes_the_previous_one(): void
    {
        Storage::fake('r2');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/photo', ['photo' => UploadedFile::fake()->image('first.jpg')]);
        $first = $user->refresh()->profile_photo_path;

        $this->actingAs($user)->post('/profile/photo', ['photo' => UploadedFile::fake()->image('second.jpg')]);
        $second = $user->refresh()->profile_photo_path;

        $this->assertNotSame($first, $second);
        Storage::disk('r2')->assertMissing($first);
        Storage::disk('r2')->assertExists($second);
    }

    public function test_photo_upload_rejects_non_image_files(): void
    {
        Storage::fake('r2');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/profile/photo', ['photo' => UploadedFile::fake()->create('malware.pdf', 100, 'application/pdf')])
            ->assertSessionHasErrors('photo');

        $this->assertNull($user->refresh()->profile_photo_path);
    }

    public function test_photo_upload_rejects_oversized_images(): void
    {
        Storage::fake('r2');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/profile/photo', ['photo' => UploadedFile::fake()->image('huge.jpg')->size(6000)])
            ->assertSessionHasErrors('photo');

        $this->assertNull($user->refresh()->profile_photo_path);
    }

    public function test_authenticated_user_can_delete_their_profile_photo(): void
    {
        Storage::fake('r2');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/photo', ['photo' => UploadedFile::fake()->image('a.jpg')]);
        $path = $user->refresh()->profile_photo_path;

        $this->actingAs($user)
            ->delete('/profile/photo')
            ->assertSessionHas('status', 'profile-photo-deleted');

        $this->assertNull($user->refresh()->profile_photo_path);
        Storage::disk('r2')->assertMissing($path);
    }

    public function test_guests_cannot_manage_a_profile_photo(): void
    {
        Storage::fake('r2');

        $this->post('/profile/photo', ['photo' => UploadedFile::fake()->image('a.jpg')])
            ->assertRedirect('/login');

        $this->delete('/profile/photo')->assertRedirect('/login');
    }
}
