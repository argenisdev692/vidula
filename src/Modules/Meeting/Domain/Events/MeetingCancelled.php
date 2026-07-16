<?php

declare(strict_types=1);

namespace Modules\Meeting\Domain\Events;

final readonly class MeetingCancelled
{
    public function __construct(public string $uuid) {}
}
