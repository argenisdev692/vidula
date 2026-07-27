<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Modules\AiResumeStudio\Domain\Ports\RefinedCvRepositoryPort;
use Modules\AiResumeStudio\Infrastructure\Export\RefinedCvMarkdownRenderer;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\RefinedCvEloquentModel;
use Shared\Domain\Ports\ExportPort;

/**
 * Portrait ATS resume PDF from refined Markdown — human document layout
 * (no corporate export chrome).
 */
final readonly class RefinedCvExportController
{
    public function __construct(
        private RefinedCvRepositoryPort $refinedCvs,
        private RefinedCvMarkdownRenderer $renderer,
        private ExportPort $export,
    ) {}

    public function __invoke(Request $request, string $uuid): Response
    {
        $refined = $this->refinedCvs->findByUuid($uuid)
            ?? throw (new ModelNotFoundException)->setModel(RefinedCvEloquentModel::class, [$uuid]);

        $user = $request->user();
        abort_unless(
            $user !== null && (
                (int) $refined->user_id === (int) $user->id
                || $user->can('EXPORT_RESUME_STUDIOS')
            ),
            403,
        );

        $markdown = (string) $refined->refined_md;
        $slugBase = $refined->target_job_title ?: 'resume';
        $filename = Str::slug($slugBase).'-v'.$refined->version.'.pdf';
        $htmlLang = (string) ($refined->resume_language ?: 'en');

        return $this->export->pdf(
            $filename,
            'exports.pdf.refined-cv',
            [
                'docTitle' => $refined->target_job_title ?: 'Resume',
                'htmlLang' => $htmlLang,
                'heading' => null,
                'contactLine' => null,
                'targetJobTitle' => null,
                'bodyHtml' => $this->renderer->toHtml($markdown),
                'generatedAt' => now()->format('F j, Y H:i'),
            ],
            'a4',
            'portrait',
        );
    }
}
