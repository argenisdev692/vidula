<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Infrastructure\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\ContactSupport\Application\DTOs\ContactSupportFilterData;
use Modules\ContactSupport\Infrastructure\Http\Export\ContactSupportExportTransformer;
use Modules\ContactSupport\Infrastructure\Persistence\Eloquent\Models\ContactSupportEloquentModel;
use Shared\Domain\Ports\ExportPort;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the filtered contact-support inbox as CSV / Excel / PDF. Thin: reuses
 * the SAME {@see ContactSupportFilterData} + `scopeApplyFilters()` as the list
 * query (DRY) and the Shared {@see ExportPort} mechanism — this module ships only
 * the transformer. The `suspended` status maps to `onlyTrashed()`, mirroring the
 * repository's `paginate()`.
 */
final readonly class ContactSupportExportController
{
    public function __construct(private ExportPort $export) {}

    public function __invoke(Request $request): StreamedResponse|Response
    {
        $format = (string) $request->string('format', 'csv');
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 422);

        $filters = ContactSupportFilterData::validateAndCreate($request);

        $rows = ContactSupportEloquentModel::query()
            ->when($filters->status === 'suspended', fn ($q) => $q->onlyTrashed())
            ->applyFilters($filters)
            ->orderByDesc('created_at')
            ->lazy();

        return match ($format) {
            'pdf' => $this->export->pdf(
                'contact-supports.pdf',
                'exports.pdf.contact-supports',
                [
                    'rows' => $rows->map(ContactSupportExportTransformer::transformForPdf(...)),
                    'generatedAt' => now()->format('F j, Y H:i'),
                ],
            ),
            default => $this->export->tabular(
                "contact-supports.{$format}",
                ['Name', 'Email', 'Phone', 'Subject', 'Read', 'SMS Consent', 'Created', 'Status'],
                $rows->map(ContactSupportExportTransformer::transformForTable(...)),
            ),
        };
    }
}
