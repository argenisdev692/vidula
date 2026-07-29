<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

final class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * Maps to the application's user schema (first_name / last_name /
     * terms_and_conditions — there is no `name` column) and assigns the default
     * USER role. Email stays unverified: Fortify's emailVerification feature
     * fires the Registered event, which calls User::sendEmailVerificationNotification()
     * — overridden to email a 6-digit activation code (spatie one-time-password,
     * valid 30 min) instead of a signed link. The user activates by entering the
     * code, which marks the email verified.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        $this->ensureRegistrationIsNotRateLimited();

        Validator::make($input, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
            'terms_and_conditions' => ['accepted'],
        ])->validate();

        $user = User::create([
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'] ?? null,
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'password_changed_at' => now(),
            'terms_and_conditions' => true,
        ]);

        $user->assignRole('USER');

        return $user;
    }

    private function ensureRegistrationIsNotRateLimited(): void
    {
        /** @var Request $request */
        $request = request();
        $key = 'register|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw new ThrottleRequestsException(
                RateLimiter::availableIn($key),
            );
        }

        RateLimiter::hit($key, 3600);
    }
}
