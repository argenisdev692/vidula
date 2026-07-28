<?php

declare(strict_types=1);

namespace Modules\Products\Domain\Enums;

/**
 * Lifecycle of one content-generation run. The non-terminal cases double as
 * the pipeline's stage names, so the value persisted on `content_generations`
 * is exactly what the progress broadcast reports to the UI.
 */
enum GenerationStatus: string
{
    case Pending = 'pending';
    case Parsing = 'parsing';
    case Generating = 'generating';
    case Verifying = 'verifying';
    case Rendering = 'rendering';
    case Packaging = 'packaging';
    case Completed = 'completed';
    case Failed = 'failed';

    public function isTerminal(): bool
    {
        return $this === self::Completed || $this === self::Failed;
    }

    public function isNonTerminal(): bool
    {
        return ! $this->isTerminal();
    }

    /**
     * @return list<string>
     */
    public static function nonTerminalValues(): array
    {
        return array_values(array_map(
            static fn (self $status): string => $status->value,
            array_filter(self::cases(), static fn (self $status): bool => $status->isNonTerminal()),
        ));
    }

    /**
     * Progress percentage the pipeline reports when a stage STARTS.
     */
    public function progress(): int
    {
        return match ($this) {
            self::Pending => 0,
            self::Parsing => 10,
            self::Generating => 15,
            self::Verifying => 75,
            self::Rendering => 85,
            self::Packaging => 95,
            self::Completed, self::Failed => 100,
        };
    }
}
