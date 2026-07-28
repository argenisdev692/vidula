<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Application\Commands;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\AiResumeStudio\Domain\Enums\OutreachStatus;
use Modules\AiResumeStudio\Domain\Ports\OutreachDraftRepositoryPort;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\OutreachDraftEloquentModel;

final readonly class MarkOutreachSentHandler
{
    public function __construct(private OutreachDraftRepositoryPort $drafts) {}

    public function handle(string $uuid, int $userId): OutreachDraftEloquentModel
    {
        $draft = $this->drafts->findByUuid($uuid)
          ?? throw (new ModelNotFoundException)->setModel(OutreachDraftEloquentModel::class, [$uuid]);

        if ((int) $draft->user_id !== $userId) {
            throw (new ModelNotFoundException)->setModel(OutreachDraftEloquentModel::class, [$uuid]);
        }

        return $this->drafts->update($draft, [
            'status' => OutreachStatus::SentManually->value,
        ]);
    }
}
