<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Commands\VerifyOtp;

use Modules\Auth\Domain\Entities\User;
use Modules\Auth\Domain\Events\UserLoggedIn;
use Modules\Auth\Domain\Exceptions\InvalidOtpException;
use Modules\Auth\Domain\Exceptions\UserNotFoundException;
use Modules\Auth\Domain\Ports\OtpServicePort;
use Modules\Auth\Domain\Ports\UserRepositoryPort;

/**
 * VerifyOtpHandler — Validates OTP, authenticates user, emits domain event.
 */
final readonly class VerifyOtpHandler
{
    public function __construct(
        private UserRepositoryPort $userRepository,
        private OtpServicePort $otpService,
    ) {
    }

    /**
     * @return array{user: User, event: UserLoggedIn}
     */
    public function handle(VerifyOtpCommand $command): array
    {
        $user = $this->userRepository->findByEmailOrPhone($command->identifier);

        if ($user === null) {
            throw UserNotFoundException::withIdentifier($command->identifier);
        }

        $valid = $this->otpService->verify($command->identifier, $command->code);

        if (!$valid) {
            throw new InvalidOtpException();
        }

        $this->otpService->invalidate($command->identifier);

        $user->logIn(
            provider: 'otp',
            ipAddress: $command->ipAddress,
            userAgent: $command->userAgent,
        );

        return ['user' => $user, 'event' => $user->pullDomainEvents()[0]];
    }
}
