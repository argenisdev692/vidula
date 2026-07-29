<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Modules\Auth\Infrastructure\Http\Support\SafeIntended;
use Tests\TestCase;

final class SafeIntendedRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        config()->set('security.two_factor.mandatory', false);
    }

    public function test_fortify_home_is_the_dashboard(): void
    {
        $this->assertSame('/dashboard', config('fortify.home'));
        $this->assertSame('/dashboard', config('fortify.redirects.login'));
    }

    public function test_safe_intended_rejects_external_hosts(): void
    {
        $this->assertNull(SafeIntended::normalize('https://gmail.com'));
        $this->assertNull(SafeIntended::normalize('//gmail.com/phish'));
        $this->assertNull(SafeIntended::normalize('http://accounts.google.com'));
        $this->assertSame('/dashboard', SafeIntended::normalize('/dashboard'));
    }

    public function test_login_response_ignores_off_site_intended_url(): void
    {
        $user = User::factory()->create();
        $user->assignRole('USER');

        $this->actingAs($user);
        session(['url.intended' => 'https://gmail.com']);

        $response = app(LoginResponseContract::class)->toResponse(request());

        $this->assertTrue($response->isRedirect());
        $location = (string) $response->headers->get('Location');
        $this->assertTrue(
            str_ends_with($location, '/dashboard'),
            "Expected redirect to /dashboard, got [{$location}]",
        );
        $this->assertNull(session('url.intended'));
    }

    public function test_confirm_password_page_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/user/confirm-password')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/ConfirmPassword'));
    }
}
