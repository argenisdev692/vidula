<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Products\Infrastructure\Http\Controllers\ProductController;
use Modules\Products\Infrastructure\Http\Controllers\ProductExportController;
use Modules\Products\Infrastructure\Http\Controllers\ProductGenerationController;
use Modules\Products\Infrastructure\Http\Controllers\ProductMaterialController;
use Modules\Products\Infrastructure\Http\Controllers\ProductPackageController;
use Modules\Products\Infrastructure\Http\Controllers\ProductScriptController;

/*
|| Products module — web (session + Inertia).
||
|| Static segments BEFORE `{uuid}` so bulk/export are never captured as UUIDs.
|| Generation is throttled harder than plain CRUD: each run fans out into paid
|| AI / research calls, so an unthrottled endpoint is a cost-abuse vector.
*/
Route::middleware(['web', 'auth', 'throttle:60,1'])->prefix('products')->name('products.')->group(function (): void {
    Route::get('/', [ProductController::class, 'index'])
        ->middleware('permission:VIEW_ANY_PRODUCTS')->name('index');

    Route::post('/', [ProductController::class, 'store'])
        ->middleware('permission:CREATE_PRODUCTS')->name('store');

    Route::post('/bulk-delete', [ProductController::class, 'bulkDelete'])
        ->middleware('permission:BULK_DELETE_PRODUCTS')->name('bulk-delete');

    Route::post('/bulk-restore', [ProductController::class, 'bulkRestore'])
        ->middleware('permission:BULK_RESTORE_PRODUCTS')->name('bulk-restore');

    Route::get('/export', ProductExportController::class)
        ->middleware(['permission:EXPORT_PRODUCTS', 'throttle:10,1'])->name('export');

    Route::get('/{uuid}', [ProductController::class, 'show'])
        ->middleware('permission:VIEW_PRODUCTS')->whereUuid('uuid')->name('show');

    Route::put('/{uuid}', [ProductController::class, 'update'])
        ->middleware('permission:UPDATE_PRODUCTS')->whereUuid('uuid')->name('update');

    Route::delete('/{uuid}', [ProductController::class, 'destroy'])
        ->middleware('permission:DELETE_PRODUCTS')->whereUuid('uuid')->name('destroy');

    Route::post('/{uuid}/restore', [ProductController::class, 'restore'])
        ->middleware('permission:RESTORE_PRODUCTS')->whereUuid('uuid')->name('restore');

    /* Content generation (US-2 / US-8). */
    Route::post('/{uuid}/generate-content', [ProductGenerationController::class, 'store'])
        ->middleware(['permission:GENERATE_PRODUCTS', 'throttle:5,1'])->whereUuid('uuid')->name('generate-content');

    Route::get('/{uuid}/generations/{generationUuid}', [ProductGenerationController::class, 'show'])
        ->middleware('permission:VIEW_PRODUCTS')
        ->whereUuid('uuid')->whereUuid('generationUuid')->name('generations.show');

    /* Scripts (US-6). */
    Route::get('/{uuid}/topics/{topicUuid}/script', [ProductScriptController::class, 'show'])
        ->middleware('permission:VIEW_PRODUCTS')
        ->whereUuid('uuid')->whereUuid('topicUuid')->name('scripts.show');

    Route::put('/{uuid}/topics/{topicUuid}/script', [ProductScriptController::class, 'update'])
        ->middleware('permission:UPDATE_PRODUCTS')
        ->whereUuid('uuid')->whereUuid('topicUuid')->name('scripts.update');

    /* Materials (US-4). */
    Route::get('/{uuid}/materials', [ProductMaterialController::class, 'index'])
        ->middleware('permission:VIEW_PRODUCTS')->whereUuid('uuid')->name('materials.index');

    Route::get('/{uuid}/materials/{materialUuid}/download', [ProductMaterialController::class, 'download'])
        ->middleware(['permission:DOWNLOAD_PRODUCTS', 'throttle:30,1'])
        ->whereUuid('uuid')->whereUuid('materialUuid')->name('materials.download');

    Route::post('/{uuid}/materials/{materialUuid}/replace', [ProductMaterialController::class, 'replace'])
        ->middleware('permission:UPDATE_PRODUCTS')
        ->whereUuid('uuid')->whereUuid('materialUuid')->name('materials.replace');

    /* ZIP package (US-5). */
    Route::post('/{uuid}/package', [ProductPackageController::class, 'store'])
        ->middleware(['permission:GENERATE_PRODUCTS', 'throttle:5,1'])->whereUuid('uuid')->name('package.store');

    Route::get('/{uuid}/package/download', [ProductPackageController::class, 'download'])
        ->middleware(['permission:DOWNLOAD_PRODUCTS', 'throttle:10,1'])->whereUuid('uuid')->name('package.download');
});
