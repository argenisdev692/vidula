<?php

declare(strict_types=1);

namespace Modules\Meeting\Domain\ValueObjects;

/**
 * The three eligible attendee sources for a Meeting (spec.md clarify.md Q4).
 * Case values double as the `Relation::morphMap()` keys registered in
 * `MeetingServiceProvider::boot()` — never a raw FQCN in `attendable_type`.
 */
enum AttendeeType: string
{
    case User = 'user';

    case Lead = 'lead';

    case Contact = 'contact';
}
