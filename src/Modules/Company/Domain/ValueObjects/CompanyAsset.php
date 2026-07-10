<?php

declare(strict_types=1);

namespace Modules\Company\Domain\ValueObjects;

/**
 * The mutable brand/signature assets on the single company record. Single source
 * of truth for the asset → database column mapping, shared by the upload and
 * delete handlers so the association is never duplicated or drifts. The backing
 * value is the public asset key accepted by the routes/DTOs (`logo`,
 * `logo_white`, `mark`, `signature`).
 */
enum CompanyAsset: string
{
    case Logo = 'logo';
    case LogoWhite = 'logo_white';
    case Mark = 'mark';
    case Signature = 'signature';

    /**
     * The `company_data` column that stores this asset's path.
     */
    public function column(): string
    {
        return match ($this) {
            self::Logo => 'logo_path',
            self::LogoWhite => 'logo_white_path',
            self::Mark => 'mark_path',
            self::Signature => 'signature_path',
        };
    }
}
