<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Enums;

/**
 * ProfileVisibility — Domain State
 *
 * Controls who can see the user's profile.
 */
enum ProfileVisibility: string
{
    case Public = 'public';
    case Private = 'private';
    case FriendsOnly = 'friends_only';
}
