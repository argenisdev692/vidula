<?php

declare(strict_types=1);

namespace Modules\Campaigns\Infrastructure\Http\Export;

use Modules\Campaigns\Infrastructure\Persistence\Eloquent\Models\CampaignEloquentModel;
use Modules\SocialMedia\Infrastructure\Http\Export\SocialMediaContentExportTransformer;

/**
 * Maps a {@see CampaignEloquentModel} row to export columns (BACKEND-PHP §8)
 * — mirrors {@see SocialMediaContentExportTransformer}.
 * Per-platform copy and generated assets are intentionally omitted from the
 * tabular/PDF report; it carries scoring + lifecycle metadata only.
 */
final readonly class CampaignExportTransformer
{
    /**
     * @return array<string, string>
     */
    #[\NoDiscard]
    public static function transformForTable(CampaignEloquentModel $campaign): array
    {
        return [
            'Topic' => $campaign->topic,
            'Funnel Stage' => strtoupper($campaign->funnel_stage->value),
            'Platform' => ucfirst($campaign->platform->value),
            'Status' => ucfirst(str_replace('_', ' ', $campaign->status->value)),
            'Success Probability' => $campaign->overall_score_avg !== null ? "{$campaign->overall_score_avg}% ({$campaign->success_probability_label})" : '—',
            'Scores Passed' => $campaign->all_scores_pass ? 'Yes' : 'No',
            'Created' => $campaign->created_at?->toDateTimeString() ?? '—',
            'Suspended' => $campaign->deleted_at !== null ? 'Yes' : 'No',
        ];
    }

    /**
     * @return array<string, string>
     */
    #[\NoDiscard]
    public static function transformForPdf(CampaignEloquentModel $campaign): array
    {
        return self::transformForTable($campaign);
    }
}
