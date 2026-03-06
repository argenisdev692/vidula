<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Commands\ChangePassword;

use Modules\Auth\Domain\Entities\User;
use Modules\Auth\Domain\Ports\UserRepositoryPort;
use Modules\Auth\Domain\Services\PasswordHashingService;
use Modules\Auth\Domain\ValueObjects\Password;
use Modules\Auth\Domain\Exceptions\UserNotFoundException;
use Modules\Auth\Domain\Exceptions\InvalidCredentialsException;
use Modules\Auth\Domain\Events\PasswordChanged;
use Shared\Infrastructure\Audit\AuditInterface;

/**
 * ChangePasswordHandler — Handles password changes with PHP 8.5 pipe operator.
 */
final readonly class ChangePasswordHandler
{
    public function __construct(
        private UserRepositoryPort $userRepository,
        private PasswordHashingService $passwordHashingService,
        private AuditInterface $audit,
    ) {
    }

    #[\NoDiscard]
    public function handle(ChangePasswordCommand $command): User
    {
        return $command
            |> $this->findUser(...)
            |> $this->verifyCurrentPassword(...)
            |> $this->hashNewPassword(...)
            |> $this->updatePassword(...)
            |> $this->emitEvent(...)
            |> $this->logAudit(...);
    }

    private function findUser(ChangePasswordCommand $command): array
    {
        $user = $this->userRepository->findById($command->userId);

        if ($user === null) {
            throw UserNotFoundException::withIdentifier((string) $command->userId);
        }

        return [
            'user' => $user,
            'command' => $command,
            'passwordHash' => $this->userRepository->getPasswordHashById($command->userId),
        ];
    }

    private function verifyCurrentPassword(array $data): array
    {
        $command = $data['command'];

        if ($data['passwordHash'] === null || !password_verify($command->currentPassword, $data['passwordHash'])) {
            throw new InvalidCredentialsException();
        }

        return $data;
    }

    private function hashNewPassword(array $data): array
    {
        $command = $data['command'];
        $newPassword = Password::fromPlainText($command->newPassword);
        $hashedPassword = $this->passwordHashingService->hash($newPassword);

        return [...$data, 'hashedPassword' => $hashedPassword];
    }

    private function updatePassword(array $data): array
    {
        $user = $data['user'];
        $hashedPassword = $data['hashedPassword'];

        $updatedUser = $this->userRepository->update($user, [
            'password' => $hashedPassword,
        ]);

        return [
            'user' => $updatedUser,
            'event' => new PasswordChanged(
                userId: $user->id,
                method: 'self_change',
                occurredAt: date('c'),
            ),
        ];
    }

    private function emitEvent(array $data): User
    {
        event($data['event']);

        return $data['user'];
    }

    private function logAudit(User $user): User
    {
        $this->audit->log(
            logName: 'auth.password_changed',
            description: "User changed password interactively",
            properties: ['uuid' => $user->uuid],
        );

        return $user;
    }
}
