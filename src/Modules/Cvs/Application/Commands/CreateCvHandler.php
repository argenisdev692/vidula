<?php

declare(strict_types=1);

namespace Modules\Cvs\Application\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Cvs\Application\DTOs\CvData;
use Modules\Cvs\Domain\Enums\CvFileType;
use Modules\Cvs\Domain\Ports\CvRepositoryPort;
use Modules\Cvs\Infrastructure\Persistence\Eloquent\Models\CvEloquentModel;
use Modules\Cvs\Infrastructure\Services\CvTextExtractor;
use Shared\Domain\Ports\StoragePort;

/**
 * Persists a new CV. File is uploaded to private R2 before the DB transaction.
 */
final readonly class CreateCvHandler
{
    public function __construct(
        private CvRepositoryPort $cvs,
        private StoragePort $storage,
        private CvTextExtractor $extractor,
    ) {}

    #[\NoDiscard]
    public function handle(CvData $data, int $userId): CvEloquentModel
    {
        if ($data->file === null) {
            throw ValidationException::withMessages([
                'file' => __('A CV file (PDF or Markdown) is required.'),
            ]);
        }

        $file = $data->file;
        $extension = strtolower($file->getClientOriginalExtension());
        $fileType = CvFileType::fromExtension($extension);
        $originalFilename = $file->getClientOriginalName();
        $rawText = $this->extractor->extract(null, $fileType, $file);
        $filePath = $this->storage->putFile('cvs', $file, 'private');

        return DB::transaction(function () use ($data, $userId, $filePath, $fileType, $rawText, $originalFilename): CvEloquentModel {
            if ($data->isPrimary) {
                $this->cvs->clearPrimaryForUser($userId);
            }

            return $this->cvs->create([
                'title' => $data->title
                    |> trim(...)
                    |> (fn (string $t): string => mb_substr($t, 0, 255)),
                'niche' => $data->niche,
                'is_primary' => $data->isPrimary,
                'file_path' => $filePath,
                'file_type' => $fileType->value,
                'original_filename' => $originalFilename,
                'raw_text' => $rawText,
                'user_id' => $userId,
            ]);
        });
    }
}
