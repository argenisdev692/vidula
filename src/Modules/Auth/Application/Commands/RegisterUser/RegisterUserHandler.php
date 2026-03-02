<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Commands\RegisterUser;

use Modules\Auth\Domain\Entities\User;
use Modules\Auth\Domain\Ports\UserRepositoryPort;
use Modules\Auth\Domain\Services\PasswordHashingService;
use Modules\Auth\Domain\Services\UsernameSuggestionService;
use Modules\Auth\Domain\ValueObjects\UserEmail;
use Modules\Auth\Domain\ValueObjects\Password;
use Modules\Auth\Domain\ValueObjects\Username;
use Modules\Auth\Domain\Exceptions\ValidationException;
use Illuminate\Support\Str;

/**
 * RegisterUserHandler — Handles user registration with PHP 8.5 pipe operator.
 */
final readonly class RegisterUserHandler
{
    public function __construct(
        private UserRepositoryPort $userRepository,
        private PasswordHashingService $passwordHashingService,
        private UsernameSuggestionService $usernameSuggestionService,
    ) {}

    #[\NoDiscard]
    public function handle(RegisterUserCommand $command): User
    {
        return $command
            |> $this->validateCommand(...)
            |> $this->prepareUserData(...)
            |> $this->createUser(...)
            |> $this->persistUser(...);
    }

    private function validateCommand(RegisterUserCommand $command): RegisterUserCommand
    {
        // Validate email
        $email = new UserEmail($command->email);
        
        if ($this->userRepository->findByEmail($email) !== null) {
            throw new ValidationException('Email already registered');
        }

        // Validate password
        $password = Password::fromPlainText($command->password);
        
        if (!$password->meetsComplexityRequirements()) {
            throw new ValidationException('Password does not meet complexity requirements');
        }

        // Validate phone if provided
        if ($command->phone && $this->userRepository->findByEmailOrPhone($command->phone) !== null) {
            throw new ValidationException('Phone number already registered');
        }

        return $command;
    }

    private function prepareUserData(RegisterUserCommand $command): array
    {
        $password = Password::fromPlainText($command->password);
        $hashedPassword = $this->passwordHashingService->hash($password);

        $username = $command->username
            ? new Username($command->username)
            : $this->usernameSuggestionService->generateUnique($command->name);

        return [
            'uuid' => (string) Str::uuid(),
            'name' => $command->name,
            'lastName' => $command->lastName,
            'email' => $command->email,
            'username' => $username->value,
            'password' => $hashedPassword,
            'phone' => $command->phone,
        ];
    }

    private function createUser(array $data): User
    {
        return User::create(
            uuid: $data['uuid'],
            name: $data['name'],
            email: $data['email'],
            username: $data['username'],
            lastName: $data['lastName'],
            phone: $data['phone'],
        );
    }

    private function persistUser(User $user): User
    {
        $data = [
            'uuid' => $user->uuid,
            'name' => $user->name,
            'last_name' => $user->lastName,
            'email' => $user->email,
            'username' => $user->username,
            'phone' => $user->phone,
            'password' => null, // Will be set separately
            'email_verified_at' => null,
        ];

        return $this->userRepository->create($data);
    }
}
