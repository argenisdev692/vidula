<?php

declare(strict_types=1);

namespace Modules\Company\Application\Commands;

use App\Models\CompanyData;
use Modules\Company\Domain\Ports\CompanyRepositoryPort;
use Shared\Domain\Ports\StoragePort;

/**
 * Removes a single brand/signature asset from the company record: deletes the
 * R2 object and nulls the column. The record itself is never deleted — only its
 * assets. Persisting triggers the CompanyData `saved` hook (cache bust).
 */
final readonly class DeleteCompanyAssetHandler
{
    /** @var array<string, string> */
    private const array COLUMN_MAP = [
        'logo' => 'logo_path',
        'logo_white' => 'logo_white_path',
        'mark' => 'mark_path',
        'signature' => 'signature_path',
    ];

    public function __construct(
        private StoragePort $storage,
        private CompanyRepositoryPort $companies,
    ) {}

    public function handle(string $asset): CompanyData
    {
        $column = self::COLUMN_MAP[$asset]
            ?? throw new \InvalidArgumentException("Unknown company asset [{$asset}].");

        $company = $this->companies->getSingleton();

        /** @var string|null $previous */
        $previous = $company->{$column};

        if (is_string($previous) && $previous !== '') {
            $this->storage->delete($previous);
        }

        return $this->companies->update($company, [$column => null]);
    }
}
