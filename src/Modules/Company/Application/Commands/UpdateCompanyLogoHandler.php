<?php

declare(strict_types=1);

namespace Modules\Company\Application\Commands;

use App\Models\CompanyData;
use Illuminate\Support\Facades\DB;
use Modules\Company\Application\DTOs\UpdateCompanyLogoData;
use Modules\Company\Domain\Ports\CompanyRepositoryPort;
use Modules\Company\Domain\ValueObjects\CompanyAsset;
use Shared\Domain\Ports\StoragePort;

/**
 * Replaces one brand logo variant on the single company record. The PNG is
 * stored on R2 with public visibility and the previous object is deleted so the
 * bucket never accumulates orphaned logos. Persisting the column triggers the
 * CompanyData `saved` hook, which busts the CompanyProfile branding cache.
 */
final readonly class UpdateCompanyLogoHandler
{
    public function __construct(
        private StoragePort $storage,
        private CompanyRepositoryPort $companies,
    ) {}

    public function handle(UpdateCompanyLogoData $data): CompanyData
    {
        $company = $this->companies->getSingleton();
        $column = CompanyAsset::from($data->type)->column();

        /** @var string|null $previous */
        $previous = $company->{$column};

        $path = $this->storage->putFile('branding', $data->logo, 'public');

        $updated = DB::transaction(fn () => $this->companies->update($company, [$column => $path]));

        // Delete the superseded object only after the write commits, so a failed
        // update never destroys the still-referenced previous logo.
        if ($previous !== null && $previous !== $path) {
            $this->storage->delete($previous);
        }

        return $updated;
    }
}
