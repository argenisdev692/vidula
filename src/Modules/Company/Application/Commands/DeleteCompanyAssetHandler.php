<?php

declare(strict_types=1);

namespace Modules\Company\Application\Commands;

use App\Models\CompanyData;
use Illuminate\Support\Facades\DB;
use Modules\Company\Domain\Ports\CompanyRepositoryPort;
use Modules\Company\Domain\ValueObjects\CompanyAsset;
use Shared\Domain\Ports\StoragePort;

/**
 * Removes a single brand/signature asset from the company record: deletes the
 * R2 object and nulls the column. The record itself is never deleted — only its
 * assets. Persisting triggers the CompanyData `saved` hook (cache bust).
 */
final readonly class DeleteCompanyAssetHandler
{
    public function __construct(
        private StoragePort $storage,
        private CompanyRepositoryPort $companies,
    ) {}

    public function handle(string $asset): CompanyData
    {
        $column = (CompanyAsset::tryFrom($asset)
            ?? throw new \InvalidArgumentException("Unknown company asset [{$asset}]."))
            ->column();

        $company = $this->companies->getSingleton();

        /** @var string|null $previous */
        $previous = $company->{$column};

        $updated = DB::transaction(fn () => $this->companies->update($company, [$column => null]));

        // Delete the object only after the column is cleared and committed, so a
        // failed write never loses a still-referenced asset.
        if (is_string($previous) && $previous !== '') {
            $this->storage->delete($previous);
        }

        return $updated;
    }
}
