<?php

declare(strict_types=1);

namespace Modules\Company\Application\Commands;

use App\Models\CompanyData;
use Illuminate\Support\Facades\DB;
use Modules\Company\Application\DTOs\UpdateCompanySignatureData;
use Modules\Company\Domain\Ports\CompanyRepositoryPort;
use Modules\Company\Domain\ValueObjects\CompanyAsset;
use Shared\Domain\Ports\StoragePort;

/**
 * Replaces the company signature on the single company record. The file is
 * stored on R2 with public visibility and the previous object is deleted so the
 * bucket never accumulates orphaned signatures. Persisting the column triggers
 * the CompanyData `saved` hook, which busts the CompanyProfile cache.
 */
final readonly class UpdateCompanySignatureHandler
{
    public function __construct(
        private StoragePort $storage,
        private CompanyRepositoryPort $companies,
    ) {}

    public function handle(UpdateCompanySignatureData $data): CompanyData
    {
        $company = $this->companies->getSingleton();
        $column = CompanyAsset::Signature->column();

        /** @var string|null $previous */
        $previous = $company->{$column};

        $path = $this->storage->putFile('branding', $data->signature, 'public');

        $updated = DB::transaction(fn () => $this->companies->update($company, [$column => $path]));

        // Delete the superseded object only after the write commits, so a failed
        // update never destroys the still-referenced previous signature.
        if ($previous !== null && $previous !== $path) {
            $this->storage->delete($previous);
        }

        return $updated;
    }
}
