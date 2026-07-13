<?php

declare(strict_types=1);

namespace Modules\SocialMedia\Infrastructure\Http\Export;

use Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\SocialMediaContentEloquentModel;

/**
 * Maps a {@see SocialMediaContentEloquentModel} row to export columns
 * (BACKEND-PHP §8) — mirrors {@see \Modules\Post\Infrastructure\Http\Export\PostExportTransformer}.
 * Per-platform copy and generated assets are intentionally omitted from the
 * tabular/PDF report; it carries scoring + lifecycle metadata only.
 */
final readonly class SocialMediaContentExportTransformer
{
    /**
     * @return array<string, string>
     */
    #[\NoDiscard]
    public static function transformForTable(SocialMediaContentEloquentModel $content): array
    {
        return [
            'Topic' => $content->topic,
            'Funnel Stage' => strtoupper($content->funnel_stage->value),
            'Status' => ucfirst(str_replace('_', ' ', $content->status->value)),
            'Overall Score' => $content->overall_score_avg !== null ? (string) $content->overall_score_avg : '—',
            'Scores Passed' => $content->all_scores_pass ? 'Yes' : 'No',
            'Created' => $content->created_at?->toDateTimeString() ?? '—',
            'Suspended' => $content->deleted_at !== null ? 'Yes' : 'No',
        ];
    }

    /**
     * @return array<string, string>
     */
    #[\NoDiscard]
    public static function transformForPdf(SocialMediaContentEloquentModel $content): array
    {
        return self::transformForTable($content);
    }
}
