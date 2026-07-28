<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Users\Application\DTOs\UserFilterData;
use Modules\Users\Infrastructure\Http\Export\UserExportTransformer;
use Shared\Domain\Ports\ExportPort;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the filtered user list as CSV / Excel / PDF. Thin: reuses the SAME
 * `scopeApplyFilters` as the list query (DRY) and the Shared {@see ExportPort}
 * mechanism — this module ships only the transformer, never a writer/PDF class.
 */
final readonly class UserExportController
{
    public function __construct(private ExportPort $export) {}

    public function __invoke(Request $request): StreamedResponse|Response
    {
        $format = (string) $request->string('format', 'csv');
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 422);

        $filters = UserFilterData::validateAndCreate($request);

        $rows = User::query()
            ->applyFilters($filters)
            ->orderBy($filters->resolvedSortField(), $filters->resolvedSortDirection())
            ->lazy();

        return match ($format) {
            'pdf' => $this->export->pdf(
                'users-export.pdf',
                'exports.pdf.users',
                [
                    'rows' => $rows->map(UserExportTransformer::transformForPdf(...)),
                    'generatedAt' => now()->format('F j, Y H:i'),
                ],
            ),
            default => $this->export->tabular(
                "users.{$format}",
                ['Name', 'Email', 'Username', 'Phone', 'Status', 'Created At'],
                $rows->map(UserExportTransformer::transformForExcel(...)),
            ),
        };
    }
}
