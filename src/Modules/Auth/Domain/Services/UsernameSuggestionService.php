<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Services;

use Modules\Auth\Domain\ValueObjects\Username;
use Modules\Auth\Domain\Ports\UserRepositoryPort;

/**
 * UsernameSuggestionService — Domain service for username generation and suggestions.
 * 
 * Features:
 * - Generate unique usernames
 * - Suggest alternatives
 * - Check availability
 */
final readonly class UsernameSuggestionService
{
    public function __construct(
        private UserRepositoryPort $userRepository,
    ) {}

    #[\NoDiscard]
    public function generateUnique(string $baseName): Username
    {
        $username = Username::generate($baseName);
        $counter = 1;

        while ($this->isUsernameTaken($username)) {
            $username = Username::generate($baseName, $counter);
            $counter++;
            
            if ($counter > 100) {
                throw new \RuntimeException('Could not generate unique username after 100 attempts');
            }
        }

        return $username;
    }

    #[\NoDiscard]
    public function suggestAlternatives(string $baseName, int $count = 5): array
    {
        $suggestions = [];
        $counter = 0;

        while (count($suggestions) < $count && $counter < 100) {
            $username = Username::generate($baseName, $counter > 0 ? $counter : 0);
            
            if (!$this->isUsernameTaken($username)) {
                $suggestions[] = $username;
            }
            
            $counter++;
        }

        return $suggestions;
    }

    #[\NoDiscard]
    public function isAvailable(Username $username): bool
    {
        return !$this->isUsernameTaken($username);
    }

    private function isUsernameTaken(Username $username): bool
    {
        return $this->userRepository->findByEmailOrPhone($username->value) !== null;
    }
}
