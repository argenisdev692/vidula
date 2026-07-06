<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

final class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Genders accepted by the self-service profile form.
     *
     * @var list<string>
     */
    private const GENDERS = ['male', 'female', 'other', 'prefer_not_to_say'];

    /**
     * Validate and update the given user's profile information.
     *
     * Maps to the application's user schema (first_name / last_name — there is
     * no `name` column). Email and username uniqueness ignore the current user
     * so a save that leaves them unchanged is not rejected. Changing the email
     * clears verification and re-triggers the OTP verification flow.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        $validated = Validator::make($input, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:15', Rule::unique('users')->ignore($user->id)],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            // E.164 from the PhoneField (common/form). `INTERNATIONAL` accepts any
            // valid international number regardless of country (libphonenumber).
            'phone' => ['nullable', 'string', 'max:20', 'phone:INTERNATIONAL'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(self::GENDERS)],
            'address' => ['nullable', 'string', 'max:255'],
            'address_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ])->validateWithBag('updateProfileInformation');

        $profileFields = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? null,
            'username' => $validated['username'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'address' => $validated['address'] ?? null,
            'address_2' => $validated['address_2'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'zip_code' => $validated['zip_code'] ?? null,
            'country' => $validated['country'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
        ];

        if ($validated['email'] !== $user->email && $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $profileFields, $validated['email']);

            return;
        }

        $user->forceFill([...$profileFields, 'email' => $validated['email']])->save();
    }

    /**
     * Update the profile of a verified user who is changing their email address:
     * the new address is stored unverified and a fresh verification code is sent.
     *
     * @param  array<string, mixed>  $profileFields
     */
    protected function updateVerifiedUser(User $user, array $profileFields, string $email): void
    {
        $user->forceFill([
            ...$profileFields,
            'email' => $email,
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}
