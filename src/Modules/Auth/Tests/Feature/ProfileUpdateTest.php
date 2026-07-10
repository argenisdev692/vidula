<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Self-service profile editing via Fortify's PUT /user/profile-information,
 * extended (App\Actions\Fortify\UpdateUserProfileInformation) to cover username
 * and the personal/address fields. Email + username uniqueness ignore the
 * current user. Validation errors land in the `updateProfileInformation` bag.
 */
final class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'username' => 'adalovelace',
            'email' => 'ada@example.com',
            'phone' => '+14155552671',
            'date_of_birth' => '1990-05-10',
            'gender' => 'female',
            'address' => '10 Analytical St',
            'address_2' => 'Suite 1',
            'city' => 'London',
            'state' => 'England',
            'zip_code' => 'EC1A',
            'country' => 'UK',
            'country_code' => 'GB',
            'latitude' => 51.5074,
            'longitude' => -0.1278,
        ], $overrides);
    }

    public function test_profile_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk();
    }

    public function test_authenticated_user_can_update_their_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'ada@example.com',
            'first_name' => 'Augusta',
        ]);

        $this->actingAs($user)
            ->put('/user/profile-information', $this->payload())
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame('Ada', $user->first_name);
        $this->assertSame('adalovelace', $user->username);
        $this->assertSame('female', $user->gender);
        $this->assertSame('London', $user->city);
        $this->assertSame('GB', $user->country_code);
        $this->assertSame('+14155552671', $user->phone);
        $this->assertSame(51.5074, $user->latitude);
        $this->assertSame(-0.1278, $user->longitude);
    }

    public function test_phone_must_be_a_valid_international_number(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com']);

        $this->actingAs($user)
            ->put('/user/profile-information', $this->payload(['phone' => '555 000 0000']))
            ->assertSessionHasErrors(['phone'], null, 'updateProfileInformation');
    }

    public function test_email_must_be_unique_across_users(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['email' => 'ada@example.com']);

        $this->actingAs($user)
            ->put('/user/profile-information', $this->payload(['email' => 'taken@example.com']))
            ->assertSessionHasErrors(['email'], null, 'updateProfileInformation');

        $this->assertSame('ada@example.com', $user->refresh()->email);
    }

    public function test_username_must_be_unique_across_users(): void
    {
        User::factory()->create(['username' => 'takenuser']);
        $user = User::factory()->create(['username' => 'adalovelace']);

        $this->actingAs($user)
            ->put('/user/profile-information', $this->payload(['username' => 'takenuser']))
            ->assertSessionHasErrors(['username'], null, 'updateProfileInformation');

        $this->assertSame('adalovelace', $user->refresh()->username);
    }

    public function test_username_must_be_at_least_8_characters(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/user/profile-information', $this->payload(['username' => 'ada12']))
            ->assertSessionHasErrors(['username'], null, 'updateProfileInformation');
    }

    public function test_username_cannot_exceed_15_characters(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/user/profile-information', $this->payload(['username' => 'adalovelace12345678']))
            ->assertSessionHasErrors(['username'], null, 'updateProfileInformation');
    }

    public function test_username_rejects_non_alphanumeric_characters(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/user/profile-information', $this->payload(['username' => 'ada_lovelace']))
            ->assertSessionHasErrors(['username'], null, 'updateProfileInformation');
    }

    public function test_user_can_keep_their_own_email_and_username(): void
    {
        $user = User::factory()->create([
            'email' => 'ada@example.com',
            'username' => 'adalovelace',
        ]);

        $this->actingAs($user)
            ->put('/user/profile-information', $this->payload([
                'email' => 'ada@example.com',
                'username' => 'adalovelace',
            ]))
            ->assertSessionHasNoErrors();
    }

    public function test_invalid_gender_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/user/profile-information', $this->payload(['gender' => 'unknown']))
            ->assertSessionHasErrors(['gender'], null, 'updateProfileInformation');
    }

    public function test_guests_cannot_update_a_profile(): void
    {
        $this->put('/user/profile-information', $this->payload())
            ->assertRedirect('/login');
    }
}
