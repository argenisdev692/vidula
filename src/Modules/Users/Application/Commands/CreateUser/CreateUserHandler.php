<?php

declare(strict_types=1);

namespace Modules\Users\Application\Commands\CreateUser;

use Illuminate\Support\Str;
use Modules\Users\Domain\Entities\User;
use Modules\Users\Domain\Enums\UserStatus;
use Modules\Users\Domain\Events\UserCreatedByAdmin;
use Modules\Users\Domain\Ports\UserRepositoryPort;
use Shared\Domain\Events\DomainEventPublisher;
use Illuminate\Support\Facades\Cache;

final readonly class CreateUserHandler
{
    public function __construct(
        private UserRepositoryPort $userRepository,
    ) {
    }

    public function handle(CreateUserCommand $command): User
    {
        $dto = $command->dto;
        $uuid = Str::uuid()->toString();
        $setupToken = Str::random(60);

        $user = $this->userRepository->create([
            'uuid' => $uuid,
            'name' => $dto->name,
            'last_name' => $dto->lastName,
            'email' => $dto->email,
            'username' => $dto->username,
            'phone' => $dto->phone,
            'address' => $dto->address,
            'city' => $dto->city,
            'state' => $dto->state,
            'country' => $dto->country,
            'zip_code' => $dto->zipCode,
            'status' => UserStatus::PendingSetup->value,
            'setup_token' => $setupToken,
            'setup_token_expires_at' => now()->addDays(7)->toDateTimeString(),
        ]);

        // Invalidate list cache
        $this->invalidateListCache();

        // Dispatch domain event
        DomainEventPublisher::instance()->publish(
            new UserCreatedByAdmin(
                aggregateId: $uuid,
                email: $dto->email,
                setupToken: $setupToken,
                occurredOn: now()->toDateTimeString()
            )
        );

        return $user;
    }

    private function invalidateListCache(): void
    {
        try {
            Cache::tags(['users_list'])->flush();
        } catch (\Exception $e) {
            // Tags not supported, cache will expire naturally
        }
    }
}
