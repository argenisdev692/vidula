<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Mappers;

use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel;
use Modules\Auth\Domain\Entities\User;

/**
 * UserMapper — Maps between Eloquent models and Domain entities using PHP 8.5 pipe operator.
 * 
 * Features:
 * - Pipe operator for clean data transformation
 * - Proper date conversion (Carbon → ISO8601)
 * - #[\NoDiscard] attribute
 */
final class UserMapper
{
    #[\NoDiscard]
    public static function toDomain(UserEloquentModel $eloquent): User
    {
        return $eloquent
            |> self::extractBaseData(...)
            |> self::addTimestamps(...)
            |> self::buildEntity(...);
    }

    private static function extractBaseData(UserEloquentModel $model): array
    {
        return [
            'id' => $model->id,
            'uuid' => $model->uuid,
            'name' => $model->name,
            'lastName' => $model->last_name,
            'email' => $model->email,
            'username' => $model->username,
            'profilePhotoPath' => $model->profile_photo_path,
            'phone' => $model->phone,
            'isEmailVerified' => $model->email_verified_at !== null,
            'model' => $model, // Pass model for next step
        ];
    }

    private static function addTimestamps(array $data): array
    {
        $model = $data['model'];
        unset($data['model']);

        return [
            ...$data,
            'createdAt' => $model->created_at?->toIso8601String() ?? '',
            'updatedAt' => $model->updated_at?->toIso8601String() ?? '',
            'deletedAt' => $model->deleted_at?->toIso8601String(),
        ];
    }

    private static function buildEntity(array $data): User
    {
        return new User(...$data);
    }
}
