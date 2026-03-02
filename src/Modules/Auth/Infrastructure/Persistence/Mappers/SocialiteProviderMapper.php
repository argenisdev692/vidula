<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Mappers;

use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\SocialiteProviderEloquentModel;
use Modules\Auth\Domain\Entities\SocialiteProvider;

/**
 * SocialiteProviderMapper — Maps between Eloquent models and Domain entities using PHP 8.5 pipe operator.
 * 
 * Features:
 * - Pipe operator for clean data transformation
 * - Proper date conversion (Carbon → ISO8601)
 * - #[\NoDiscard] attribute
 */
final class SocialiteProviderMapper
{
    #[\NoDiscard]
    public static function toDomain(SocialiteProviderEloquentModel $eloquent): SocialiteProvider
    {
        return $eloquent
            |> self::extractBaseData(...)
            |> self::addTimestamps(...)
            |> self::buildEntity(...);
    }

    private static function extractBaseData(SocialiteProviderEloquentModel $model): array
    {
        return [
            'id' => $model->id,
            'userId' => $model->user_id,
            'provider' => $model->provider,
            'providerId' => $model->provider_id,
            'token' => $model->token,
            'refreshToken' => $model->refresh_token,
            'tokenExpiresAt' => $model->token_expires_at?->toIso8601String(),
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

    private static function buildEntity(array $data): SocialiteProvider
    {
        return new SocialiteProvider(...$data);
    }
}
