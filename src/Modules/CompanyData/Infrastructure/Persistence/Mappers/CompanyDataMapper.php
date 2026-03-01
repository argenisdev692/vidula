<?php

declare(strict_types=1);

namespace Modules\CompanyData\Infrastructure\Persistence\Mappers;

use Modules\CompanyData\Domain\Entities\CompanyData;
use Modules\CompanyData\Domain\Enums\CompanyStatus;
use Modules\CompanyData\Domain\ValueObjects\CompanyDataId;
use Modules\CompanyData\Domain\ValueObjects\Coordinates;
use Modules\CompanyData\Domain\ValueObjects\SocialLinks;
use Modules\CompanyData\Domain\ValueObjects\UserId;
use Modules\CompanyData\Infrastructure\Persistence\Eloquent\Models\CompanyDataEloquentModel;

/**
 * CompanyDataMapper
 */
final class CompanyDataMapper
{
    public static function toDomain(CompanyDataEloquentModel $model): CompanyData
    {
        return new CompanyData(
            id: new CompanyDataId($model->uuid),
            userId: new UserId($model->user?->uuid ?? ''),
            companyName: $model->company_name,
            name: $model->name,
            email: $model->email,
            phone: $model->phone,
            address: $model->address,
            socialLinks: new SocialLinks(
                facebook: $model->facebook_link,
                instagram: $model->instagram_link,
                linkedin: $model->linkedin_link,
                twitter: $model->twitter_link,
                website: $model->website
            ),
            coordinates: new Coordinates(
                latitude: (float) $model->latitude,
                longitude: (float) $model->longitude
            ),
            signaturePath: $model->signature_path,
            status: CompanyStatus::from($model->status ?? 'active'),
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
            deletedAt: $model->deleted_at?->toIso8601String()
        );
    }
}
