<?php

declare(strict_types=1);

namespace Modules\Products\Application\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Products\Application\DTOs\ReplaceMaterialData;
use Modules\Products\Domain\Enums\MaterialType;
use Modules\Products\Domain\Ports\ProductMaterialRepositoryPort;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductMaterialEloquentModel;
use Shared\Domain\Ports\AuditPort;
use Shared\Domain\Ports\StoragePort;

/**
 * Supersedes the stored file behind a Markdown/PDF material (spec US-4).
 *
 * Link materials have no stored object, so they are rejected rather than
 * silently turned into files. The previous object is deleted only after the
 * new one is written, so a failed upload never leaves the row pointing at
 * nothing.
 */
final readonly class ReplaceMaterialHandler
{
    /** @var list<string> */
    private const array ALLOWED_EXTENSIONS = ['md', 'markdown', 'pdf'];

    public function __construct(
        private ProductMaterialRepositoryPort $materials,
        private StoragePort $storage,
        private AuditPort $audit,
    ) {}

    #[\NoDiscard]
    public function handle(
        ProductMaterialEloquentModel $material,
        ReplaceMaterialData $data,
        ?object $causer = null,
    ): ProductMaterialEloquentModel {
        $this->guardStoredFileMaterial($material);

        $extension = strtolower($data->file->getClientOriginalExtension());

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'file' => [__('Only Markdown and PDF replacements are accepted.')],
            ]);
        }

        $previousPath = $material->path;
        $path = $this->storage->putFile("products/materials/{$material->uuid}", $data->file);
        $disk = (string) config('filesystems.cloud', 'r2');

        $updated = DB::transaction(fn (): ProductMaterialEloquentModel => $this->materials->update($material, [
            'title' => $data->title ?? $material->title,
            'type' => ($extension === 'pdf' ? MaterialType::Pdf : MaterialType::Markdown)->value,
            'storage_disk' => $disk,
            'path' => $path,
            'original_name' => $data->file->getClientOriginalName(),
            // Sniffed MIME — never trust the client-supplied Content-Type header.
            'mime_type' => $data->file->getMimeType() ?: $data->file->getClientMimeType(),
            'size_bytes' => $data->file->getSize(),
        ]));

        if ($previousPath !== null && $previousPath !== $path) {
            $this->storage->delete($previousPath);
        }

        $this->audit->log(
            event: 'products.material.replaced',
            subject: $updated,
            properties: [
                'material_uuid' => $updated->uuid,
                'extension' => $extension,
                'size_bytes' => $data->file->getSize(),
            ],
            causer: $causer,
            logName: 'products',
        );

        return $updated;
    }

    private function guardStoredFileMaterial(ProductMaterialEloquentModel $material): void
    {
        $type = $material->type instanceof MaterialType
            ? $material->type
            : MaterialType::from((string) $material->type);

        if (! $type->isStoredFile()) {
            throw ValidationException::withMessages([
                'file' => [__('Link materials cannot be replaced with a file.')],
            ]);
        }
    }
}
