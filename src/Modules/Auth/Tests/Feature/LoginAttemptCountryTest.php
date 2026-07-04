<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class LoginAttemptCountryTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_records_the_cdn_edge_country(): void
    {
        $user = User::factory()->create([
            'email' => 'jane@example.com',
            'password' => Hash::make('Sup3rS3cret!2026'),
        ]);

        $this->withHeaders(['CF-IPCountry' => 'ES'])
            ->post('/login', [
                'email' => 'jane@example.com',
                'password' => 'Sup3rS3cret!2026',
            ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('login_attempts', [
            'email' => 'jane@example.com',
            'successful' => true,
            'country' => 'ES',
        ]);
    }

    public function test_failed_login_records_the_country_and_leaves_it_null_without_the_header(): void
    {
        User::factory()->create([
            'email' => 'jane@example.com',
            'password' => Hash::make('Sup3rS3cret!2026'),
        ]);

        $this->post('/login', [
            'email' => 'jane@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertDatabaseHas('login_attempts', [
            'email' => 'jane@example.com',
            'successful' => false,
            'country' => null,
        ]);
    }

    public function test_placeholder_country_codes_are_ignored(): void
    {
        User::factory()->create([
            'email' => 'jane@example.com',
            'password' => Hash::make('Sup3rS3cret!2026'),
        ]);

        // Cloudflare returns "XX" when the country cannot be determined.
        $this->withHeaders(['CF-IPCountry' => 'XX'])
            ->post('/login', [
                'email' => 'jane@example.com',
                'password' => 'Sup3rS3cret!2026',
            ])->assertRedirect();

        $this->assertDatabaseHas('login_attempts', [
            'email' => 'jane@example.com',
            'successful' => true,
            'country' => null,
        ]);
    }
}
