<?php

declare(strict_types=1);

namespace Modules\Company\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Company\Application\Commands\DeleteCompanyAssetHandler;
use Modules\Company\Application\Commands\UpdateCompanyHandler;
use Modules\Company\Application\Commands\UpdateCompanyLogoHandler;
use Modules\Company\Application\Commands\UpdateCompanySignatureHandler;
use Modules\Company\Application\DTOs\UpdateCompanyData;
use Modules\Company\Application\DTOs\UpdateCompanyLogoData;
use Modules\Company\Application\DTOs\UpdateCompanySignatureData;
use Modules\Company\Application\Queries\GetCompanyHandler;
use Modules\Company\Application\Queries\ListCompanyHandler;

/**
 * Company settings management (singleton). The record is seeded, never created
 * or deleted through the UI — only its text fields and brand/signature assets
 * are mutated. Every route is authorized via `permission:*_COMPANY_DATA`
 * middleware (not roles) — see Routes. Controller stays thin: validate →
 * handler → response.
 */
final readonly class CompanyDataController
{
    public function index(Request $request, ListCompanyHandler $list): InertiaResponse|JsonResponse
    {
        $companies = $list->handle(min(max($request->integer('per_page', 15), 1), 100));

        return $request->expectsJson()
            ? response()->json($companies)
            : Inertia::render('company-data/Index', ['companies' => $companies]);
    }

    public function show(Request $request, string $uuid, GetCompanyHandler $get): InertiaResponse|JsonResponse
    {
        $company = $get->handle($uuid);

        return $request->expectsJson()
            ? response()->json(['data' => $company])
            : Inertia::render('company-data/Show', ['company' => $company]);
    }

    public function update(string $uuid, UpdateCompanyData $data, GetCompanyHandler $get, UpdateCompanyHandler $update): RedirectResponse
    {
        $update->handle($get->handle($uuid), $data);

        return back()->with('success', __('Company data updated.'));
    }

    public function updateLogo(UpdateCompanyLogoData $data, UpdateCompanyLogoHandler $handler): RedirectResponse
    {
        $handler->handle($data);

        return back()->with('success', __('Logo updated.'));
    }

    public function destroyLogo(string $type, DeleteCompanyAssetHandler $handler): RedirectResponse
    {
        $handler->handle($type);

        return back()->with('success', __('Logo removed.'));
    }

    public function updateSignature(UpdateCompanySignatureData $data, UpdateCompanySignatureHandler $handler): RedirectResponse
    {
        $handler->handle($data);

        return back()->with('success', __('Signature updated.'));
    }

    public function destroySignature(DeleteCompanyAssetHandler $handler): RedirectResponse
    {
        $handler->handle('signature');

        return back()->with('success', __('Signature removed.'));
    }
}
