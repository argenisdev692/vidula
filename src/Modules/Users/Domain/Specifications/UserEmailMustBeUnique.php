<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Specifications;

use Modules\Users\Domain\Ports\UserRepositoryPort;

/**
 * UserEmailMustBeUnique Specification
 */
final readonly class UserEmailMustBeUnique
{
    public function __construct(
        private UserRepositoryPort $repository
    ) {
    }

    #[\NoDiscard]
    public function isSatisfiedBy(string $email, ?string $excludeUuid = null): bool
    {
        $user = $this->repository->findByEmail($email);

        if (null === $user) {
            return true;
        }

        return $user->uuid === $excludeUuid;
    }
}
