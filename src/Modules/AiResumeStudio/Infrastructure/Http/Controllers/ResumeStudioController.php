<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\AiResumeStudio\Application\Commands\BulkDeleteJobMatchesHandler;
use Modules\AiResumeStudio\Application\Commands\BulkRestoreJobMatchesHandler;
use Modules\AiResumeStudio\Application\Commands\CreateJobSearchConfigHandler;
use Modules\AiResumeStudio\Application\Commands\MarkOutreachSentHandler;
use Modules\AiResumeStudio\Application\Commands\StartStudioRunHandler;
use Modules\AiResumeStudio\Application\Commands\UpdateJobMatchHandler;
use Modules\AiResumeStudio\Application\DTOs\JobSearchConfigData;
use Modules\AiResumeStudio\Application\DTOs\StartStudioRunData;
use Modules\AiResumeStudio\Application\DTOs\StudioFilterData;
use Modules\AiResumeStudio\Application\DTOs\UpdateJobMatchData;
use Modules\AiResumeStudio\Application\Queries\GetStudioRunHandler;
use Modules\AiResumeStudio\Application\Queries\ListStudioRunsHandler;
use Modules\AiResumeStudio\Domain\Ports\GithubPortfolioPort;
use Shared\Application\DTOs\BulkUuidsData;

final readonly class ResumeStudioController
{
    public function index(Request $request, ListStudioRunsHandler $list): InertiaResponse|JsonResponse
    {
        $filters = StudioFilterData::validateAndCreate($request);
        $payload = $list->handle(
            $filters,
            (int) $request->user()->id,
            min(max($request->integer('per_page', 15), 1), 100),
        );

        return match ($request->expectsJson()) {
            true => response()->json($payload),
            false => Inertia::render('resume-studio/Index', [...$payload, 'filters' => $filters]),
        };
    }

    public function show(string $uuid, GetStudioRunHandler $get): InertiaResponse|JsonResponse
    {
        $run = $get->handle($uuid);

        return match (request()->expectsJson()) {
            true => response()->json(['data' => $run]),
            false => Inertia::render('resume-studio/Show', ['run' => $run]),
        };
    }

    public function startRun(Request $request, StartStudioRunData $data, StartStudioRunHandler $handler): RedirectResponse|JsonResponse
    {
        $run = $handler->handle($data, (int) $request->user()->id);

        return match ($request->expectsJson()) {
            true => response()->json(['data' => $run], 202),
            false => redirect()
                ->route('resume-studio.runs.show', ['uuid' => $run->uuid])
                ->with('success', __('Studio run queued.')),
        };
    }

    public function storeConfig(Request $request, JobSearchConfigData $data, CreateJobSearchConfigHandler $handler): RedirectResponse|JsonResponse
    {
        $config = $handler->handle($data, (int) $request->user()->id);

        return match ($request->expectsJson()) {
            true => response()->json(['data' => $config], 201),
            false => back()->with('success', __('Job search config saved.')),
        };
    }

    public function listGithubRepos(Request $request, GithubPortfolioPort $github): JsonResponse
    {
        $validated = $request->validate([
            'github_username' => ['required', 'string', 'max:255'],
            'selected_repos' => ['nullable', 'array', 'max:20'],
            'selected_repos.*' => ['string', 'max:255'],
        ]);

        $repos = $github->listRepos(
            (string) $validated['github_username'],
            $validated['selected_repos'] ?? [],
        );

        return response()->json(['data' => $repos]);
    }

    public function updateMatch(string $uuid, UpdateJobMatchData $data, UpdateJobMatchHandler $handler): RedirectResponse|JsonResponse
    {
        $match = $handler->handle($uuid, $data);

        return match (request()->expectsJson()) {
            true => response()->json(['data' => $match]),
            false => back()->with('success', __('Job match updated.')),
        };
    }

    public function markDraftSent(string $uuid, MarkOutreachSentHandler $handler): RedirectResponse|JsonResponse
    {
        $draft = $handler->handle($uuid);

        return match (request()->expectsJson()) {
            true => response()->json(['data' => $draft]),
            false => back()->with('success', __('Draft marked as sent manually.')),
        };
    }

    public function bulkDeleteMatches(BulkUuidsData $data, BulkDeleteJobMatchesHandler $handler): RedirectResponse
    {
        $count = $handler->handle($data);

        return back()->with('success', __(':count job matches suspended.', ['count' => $count]));
    }

    public function bulkRestoreMatches(BulkUuidsData $data, BulkRestoreJobMatchesHandler $handler): RedirectResponse
    {
        $count = $handler->handle($data);

        return back()->with('success', __(':count job matches restored.', ['count' => $count]));
    }
}
