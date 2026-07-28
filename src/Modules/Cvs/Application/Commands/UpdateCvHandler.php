<?php

declare(strict_types=1);

namespace Modules\Cvs\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Cvs\Application\DTOs\CvData;
use Modules\Cvs\Domain\Enums\CvFileType;
use Modules\Cvs\Domain\Ports\CvRepositoryPort;
use Modules\Cvs\Domain\Ports\CvTextExtractorPort;
use Modules\Cvs\Infrastructure\Persistence\Eloquent\Models\CvEloquentModel;
use Shared\Domain\Ports\StoragePort;

/**
 * Updates CV metadata and optionally replaces the stored file on R2.
 */
final readonly class UpdateCvHandler
{
    public function __construct(
        private CvRepositoryPort $cvs,
        private StoragePort $storage,
        private CvTextExtractorPort $extractor,
    ) {}

    public function handle(CvEloquentModel $cv, CvData $data): CvEloquentModel
    {
        $attributes = [
            'title' => $data->title
                |> trim(...)
                |> (fn (string $t): string => mb_substr($t, 0, 255)),
            'niche' => $data->niche,
            'is_primary' => $data->isPrimary,
        ];

        $previousPath = null;

        if ($data->file !== null) {
            $extension = strtolower($data->file->getClientOriginalExtension());
            $fileType = CvFileType::fromExtension($extension);
            $previousPath = $cv->file_path;
            $attributes['file_path'] = $this->storage->putFile('cvs', $data->file, 'private');
            $attributes['file_type'] = $fileType->value;
            $attributes['original_filename'] = $data->file->getClientOriginalName();
            $attributes['raw_text'] = $this->extractor->extract(null, $fileType, $data->file);
        }

        $updated = DB::transaction(function () use ($cv, $data, $attributes): CvEloquentModel {
            if ($data->isPrimary) {
                $this->cvs->clearPrimaryForUser((int) $cv->user_id, $cv->uuid);
            }

            return $this->cvs->update($cv, $attributes);
        });

        if (is_string($previousPath) && $previousPath !== '' && $previousPath !== $updated->file_path) {
            $this->storage->delete($previousPath);
        }

        return $updated;
    }
}
