<?php
declare(strict_types=1);
namespace Modules\Clients\Infrastructure\Persistence\Mappers;

use Modules\Clients\Domain\Entities\Client;
use Modules\Clients\Domain\ValueObjects\ClientId;
use Modules\Clients\Domain\ValueObjects\UserId;
use Modules\Clients\Domain\ValueObjects\SocialLinks;
use Modules\Clients\Domain\ValueObjects\Coordinates;
use Modules\Clients\Domain\Enums\CompanyStatus;
use Modules\Clients\Infrastructure\Persistence\Eloquent\Models\ClientEloquentModel;

final class ClientMapper
{
    public static function toDomain(ClientEloquentModel $model): Client
    {
        return $model
            |> self::extractValueObjects(...)
            |> self::mapToEntity(...);
    }

    /**
     * Extract and create value objects from model
     */
    private static function extractValueObjects(ClientEloquentModel $model): array
    {
        return [
            'model' => $model,
            'socialLinks' => new SocialLinks(
                facebook: $model->facebook_link,
                instagram: $model->instagram_link,
                linkedin: $model->linkedin_link,
                twitter: $model->twitter_link,
                website: $model->website
            ),
            'coordinates' => new Coordinates(
                latitude: (float) $model->latitude,
                longitude: (float) $model->longitude
            ),
        ];
    }

    /**
     * Map model data and value objects to domain entity
     */
    private static function mapToEntity(array $data): Client
    {
        ['model' => $model, 'socialLinks' => $socialLinks, 'coordinates' => $coordinates] = $data;

        return new Client(
            id: new ClientId($model->uuid),
            userId: new UserId($model->user?->uuid ?? ''),
            companyName: $model->company ?? '',
            email: $model->email,
            phone: $model->phone,
            address: $model->address,
            nif: $model->nif,
            socialLinks: $socialLinks,
            coordinates: $coordinates,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
            deletedAt: $model->deleted_at?->toIso8601String()
        );
    }
}
