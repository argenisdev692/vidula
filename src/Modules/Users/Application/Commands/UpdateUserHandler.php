<?php

declare(strict_types=1);

namespace Modules\Users\Application\Commands;

use App\Models\User;
use Modules\Users\Application\DTOs\UpdateUserData;
use Modules\Users\Domain\Events\UserForcedPasswordChange;
use Modules\Users\Domain\Ports\UserRepositoryPort;

final readonly class UpdateUserHandler
{
    public function __construct(private UserRepositoryPort $users) {}

    public function handle(User $user, UpdateUserData $data): User
    {
        $updated = $this->users->update($user, [
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'email' => $data->email,
            'username' => $data->username,
            'phone' => $data->phone,
            'date_of_birth' => $data->dateOfBirth,
            'gender' => $data->gender,
            'address' => $data->address,
            'address_2' => $data->address2,
            'zip_code' => $data->zipCode,
            'city' => $data->city,
            'state' => $data->state,
            'country' => $data->country,
            'country_code' => $data->countryCode,
            'latitude' => $data->latitude,
            'longitude' => $data->longitude,
            'must_change_password' => $data->forcePasswordChange,
        ]);

        if ($data->forcePasswordChange) {
            event(new UserForcedPasswordChange($updated->uuid));
        }

        return $updated;
    }
}
