<?php

declare(strict_types=1);

namespace Modules\Products\Domain\Exceptions;

use DomainException;

/**
 * The operator's seed markdown could not become a content tree. Codes are
 * stable so the HTTP layer can translate them into 422 validation messages
 * without string-matching.
 */
final class SeedOutlineException extends DomainException
{
    private function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }

    public static function empty(): self
    {
        return new self('EMPTY_MARKDOWN', 'The seed markdown is empty.');
    }

    public static function unparseable(): self
    {
        return new self(
            'UNPARSEABLE_MARKDOWN',
            'No sessions or topics could be read from the seed markdown. Expected "### Sesión N | title" with "- **Tema N:** title" bullets, or "### BLOQUE N – title" with a video table.',
        );
    }

    public static function tooManySessions(int $limit): self
    {
        return new self('TOO_MANY_SESSIONS', "The seed markdown declares more than {$limit} sessions.");
    }

    public static function tooManyTopics(int $limit): self
    {
        return new self('TOO_MANY_TOPICS', "The seed markdown declares more than {$limit} topics.");
    }
}
