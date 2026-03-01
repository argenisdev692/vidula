<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Persistence\Repositories;

use Modules\Users\Domain\Entities\UserProfile;
use Modules\Users\Domain\Ports\UserProfileRepositoryPort;
use Modules\Users\Domain\ValueObjects\UserId;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserProfileEloquentModel;
use Modules\Users\Infrastructure\Persistence\Mappers\UserProfileMapper;

/**
 * EloquentUserProfileRepository
 */
final class EloquentUserProfileRepository implements UserProfileRepositoryPort
{
    public function findByUserId(UserId $userId): ?UserProfile
    {
        $model = UserProfileEloquentModel::query()
            ->where('user_id', $userId->value)
            ->first();

        return $model ? UserProfileMapper::toDomain($model) : null;
    }

    public function save(UserProfile $profile): void
    {
        UserProfileEloquentModel::query()->updateOrCreate(
            ['user_id' => $profile->userId->value],
            [
                'bio' => $profile->bio->content,
                'visibility' => $profile->visibility->value,
                'social_links' => $profile->socialLinks->toArray(),
            ]
        );
    }

    public function delete(UserId $userId): void
    {
        UserProfileEloquentModel::query()->where('user_id', $userId->value)->delete();
    }
}
