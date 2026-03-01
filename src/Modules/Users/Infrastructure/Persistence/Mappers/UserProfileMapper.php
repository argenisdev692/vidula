<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Persistence\Mappers;

use Modules\Users\Domain\Entities\UserProfile;
use Modules\Users\Domain\Enums\ProfileVisibility;
use Modules\Users\Domain\ValueObjects\Avatar;
use Modules\Users\Domain\ValueObjects\Bio;
use Modules\Users\Domain\ValueObjects\SocialLinks;
use Modules\Users\Domain\ValueObjects\UserId;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserProfileEloquentModel;

/**
 * UserProfileMapper
 */
final class UserProfileMapper
{
    public static function toDomain(UserProfileEloquentModel $model): UserProfile
    {
        return new UserProfile(
            userId: new UserId($model->user_id),
            bio: new Bio($model->bio),
            avatar: new Avatar($model->user?->profile_photo_path),
            socialLinks: new SocialLinks(
                twitter: $model->social_links['twitter'] ?? null,
                linkedin: $model->social_links['linkedin'] ?? null,
                github: $model->social_links['github'] ?? null,
                website: $model->social_links['website'] ?? null
            ),
            visibility: ProfileVisibility::from($model->visibility ?? 'public')
        );
    }
}
