<?php

declare(strict_types=1);

namespace Modules\Company\Application\Commands;

use App\Models\CompanyData;
use Modules\Company\Application\DTOs\UpdateCompanyLogoData;
use Shared\Domain\Ports\StoragePort;

/**
 * Replaces one brand logo variant on the single company record. The PNG is
 * stored on R2 with public visibility and the previous object is deleted so the
 * bucket never accumulates orphaned logos. Persisting the column triggers the
 * CompanyData `saved` hook, which busts the CompanyProfile branding cache.
 */
final readonly class UpdateCompanyLogoHandler
{
    /** @var array<string, string> */
    private const array COLUMN_MAP = [
        'logo' => 'logo_path',
        'logo_white' => 'logo_white_path',
        'mark' => 'mark_path',
    ];

    public function __construct(private StoragePort $storage) {}

    public function handle(UpdateCompanyLogoData $data): CompanyData
    {
        $company = CompanyData::query()->orderBy('id')->firstOrFail();
        $column = self::COLUMN_MAP[$data->type];

        /** @var string|null $previous */
        $previous = $company->{$column};

        $path = $this->storage->putFile('branding', $data->logo, 'public');

        if ($previous !== null && $previous !== $path) {
            $this->storage->delete($previous);
        }

        $company->forceFill([$column => $path])->save();

        return $company;
    }
}
