<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Infrastructure\Http\Export;

use Modules\ActivityLog\Infrastructure\Http\Presenters\ActivityLogPresenter;
use Shared\Domain\Ports\ExportPort;
use Spatie\Activitylog\Models\Activity;

/**
 * Maps an {@see Activity} row to export columns. Reuses {@see ActivityLogPresenter}
 * for the actor/subject labels so CSV, Excel and PDF stay consistent with the
 * on-screen list. The module ships only this transformer — the writer/streamer
 * lives behind the Shared {@see ExportPort} (BACKEND-PHP §8).
 */
final readonly class ActivityLogExportTransformer
{
    /**
     * @return array<string, string>
     */
    #[\NoDiscard]
    public static function transformForTable(Activity $activity): array
    {
        return [
            'When' => $activity->created_at?->toDateTimeString() ?? '—',
            'Actor' => ActivityLogPresenter::causerLabel($activity) ?? '—',
            'Event' => $activity->event ?? '—',
            'Description' => $activity->description,
            'Subject' => ActivityLogPresenter::shortType($activity->subject_type) ?? '—',
            'Log' => $activity->log_name ?? '—',
        ];
    }

    /**
     * @return array<string, string>
     */
    #[\NoDiscard]
    public static function transformForPdf(Activity $activity): array
    {
        return self::transformForTable($activity);
    }
}
