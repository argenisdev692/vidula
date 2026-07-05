<?php

declare(strict_types=1);

namespace Modules\Company\Application\Commands;

use App\Models\CompanyData;
use Modules\Company\Application\DTOs\UpdateCompanySignatureData;
use Modules\Company\Domain\Ports\CompanyRepositoryPort;
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

        /** @var string|null $previous */
        $previous = $company->signature_path;

        $path = $this->storage->putFile('branding', $data->signature, 'public');

        if ($previous !== null && $previous !== $path) {
            $this->storage->delete($previous);
        }

        return $this->companies->update($company, ['signature_path' => $path]);
    }
}
