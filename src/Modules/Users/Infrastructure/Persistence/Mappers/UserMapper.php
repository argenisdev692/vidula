<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Persistence\Mappers;

use Modules\Users\Domain\Entities\User;
use Modules\Users\Domain\Enums\UserStatus;
use Modules\Users\Domain\ValueObjects\UserId;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel;

/**
 * UserMapper — Translates between Eloquent model and Domain entity.
 */
final class UserMapper
{
    public static function toDomain(UserEloquentModel $model): User
    {
        return $model
            |> self::extractStatus(...)
            |> self::mapToEntity(...);
    }

    /**
     * Extract and determine user status
     */
    private static function extractStatus(UserEloquentModel $model): array
    {
        $status = $model->trashed()
            ? UserStatus::Deleted
            : UserStatus::from($model->status ?? 'active');

        return [
            'model' => $model,
            'status' => $status,
        ];
    }

    /**
     * Map model data to domain entity
     */
    private static function mapToEntity(array $data): User
    {
        ['model' => $model, 'status' => $status] = $data;

        return new User(
            id: new UserId($model->id),
            uuid: $model->uuid,
            name: $model->name,
            lastName: $model->last_name,
            email: $model->email,
            username: $model->username,
            phone: $model->phone,
            profilePhotoPath: $model->profile_photo_path,
            address: $model->address,
            city: $model->city,
            state: $model->state,
            country: $model->country,
            zipCode: $model->zip_code,
            status: $status,
            setupToken: $model->setup_token,
            setupTokenExpiresAt: $model->setup_token_expires_at?->toIso8601String(),
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
            deletedAt: $model->deleted_at?->toIso8601String(),
        );
    }
}
