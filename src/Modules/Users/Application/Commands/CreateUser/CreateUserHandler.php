<?php

declare(strict_types=1);

namespace Modules\Users\Application\Commands\CreateUser;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Modules\Users\Domain\Entities\User;
use Modules\Users\Domain\Events\UserCreatedByAdmin;
use Modules\Users\Domain\Ports\UserRepositoryPort;
use Shared\Domain\Events\DomainEventPublisher;
use Shared\Infrastructure\Audit\AuditInterface;
use Illuminate\Support\Facades\Cache;

final readonly class CreateUserHandler
{
    public function __construct(
        private UserRepositoryPort $userRepository,
        private AuditInterface $audit,
    ) {
    }

    public function handle(CreateUserCommand $command): User
    {
        $dto = $command->dto;
        $uuid = Str::uuid()->toString();

        $namePart = Str::slug($dto->name, '');
        $lastNamePart = substr(Str::slug($dto->lastName, ''), 0, 1);
        $generatedUsername = strtolower($namePart . $lastNamePart . random_int(100, 999));

        // Only insert columns that exist in the users migration
        $user = $this->userRepository->create([
            'uuid' => $uuid,
            'name' => $dto->name,
            'last_name' => $dto->lastName,
            'email' => $dto->email,
            'password' => Hash::make(Str::password(8)),
            'username' => $dto->username ?? $generatedUsername,
            'phone' => $dto->phone,
            'address' => $dto->address,
            'city' => $dto->city,
            'state' => $dto->state,
            'country' => $dto->country,
            'zip_code' => $dto->zipCode,
        ]);

        if ($dto->role) {
            $this->userRepository->assignRole($uuid, $dto->role);
        }

        // Invalidate list cache
        $this->invalidateListCache();

        // Dispatch domain event
        DomainEventPublisher::instance()->publish(
            new UserCreatedByAdmin(
                aggregateId: $uuid,
                email: $dto->email,
                setupToken: '',
                occurredOn: now()->toDateTimeString()
            )
        );

        // Audit business action
        $this->audit->log(
            logName: 'users.created',
            description: "User created by admin: {$dto->email}",
            properties: ['uuid' => $uuid, 'email' => $dto->email, 'name' => $dto->name],
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
