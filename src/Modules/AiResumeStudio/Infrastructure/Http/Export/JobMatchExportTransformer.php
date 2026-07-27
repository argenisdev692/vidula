<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Http\Export;

use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\JobMatchEloquentModel;

final readonly class JobMatchExportTransformer
{
    /**
     * @return array<string, string>
     */
    #[\NoDiscard]
    public static function transformForTable(JobMatchEloquentModel $match): array
    {
        return $match
          |> self::extractBaseData(...)
          |> self::sanitizeOutput(...);
    }

    /**
     * @return array<string, string>
     */
    #[\NoDiscard]
    public static function transformForPdf(JobMatchEloquentModel $match): array
    {
        return self::transformForTable($match);
    }

    /**
     * @return array<string, string>
     */
    private static function extractBaseData(JobMatchEloquentModel $match): array
    {
        return [
            'Job Title' => $match->job_title,
            'Company' => $match->company_name ?? '—',
            'Score' => (string) $match->match_score,
            'Application' => $match->application_status->value,
            'Status' => $match->deleted_at !== null ? 'Suspended' : 'Active',
            'Source' => $match->source->value,
            'URL' => $match->job_url,
            'Owner' => self::ownerLabel($match),
            'First Seen' => $match->first_seen_at?->toDateTimeString() ?? '—',
        ];
    }

    /**
     * @param  array<string, string>  $data
     * @return array<string, string>
     */
    private static function sanitizeOutput(array $data): array
    {
        return array_map(
            static fn (string $value): string => $value === '' ? '—' : $value,
            $data,
        );
    }

    private static function ownerLabel(JobMatchEloquentModel $match): string
    {
        $name = trim(sprintf('%s %s', $match->user?->first_name ?? '', $match->user?->last_name ?? ''));

        return $name !== '' ? $name : 'System';
    }
}
