<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Infrastructure\Http\Controllers\InvoiceController;
use Modules\Invoices\Infrastructure\Http\Controllers\InvoiceExportController;
use Modules\Invoices\Infrastructure\Http\Controllers\InvoicePdfController;

/*
| Invoices module — web (session + Inertia).
|
| Static segments BEFORE `{uuid}` so bulk/export/pdf/next-number are never captured as UUIDs.
*/
Route::middleware(['web', 'auth', 'throttle:60,1'])->prefix('invoices')->name('invoices.')->group(function (): void {
    Route::get('/', [InvoiceController::class, 'index'])
        ->middleware('permission:VIEW_ANY_INVOICES')->name('index');

    Route::get('/next-number', [InvoiceController::class, 'nextNumber'])
        ->middleware('permission:CREATE_INVOICES')->name('next-number');

    Route::get('/export', InvoiceExportController::class)
        ->middleware(['permission:EXPORT_INVOICES', 'throttle:10,1'])->name('export');

    Route::post('/', [InvoiceController::class, 'store'])
        ->middleware('permission:CREATE_INVOICES')->name('store');

    Route::post('/bulk-delete', [InvoiceController::class, 'bulkDelete'])
        ->middleware('permission:BULK_DELETE_INVOICES')->name('bulk-delete');

    Route::post('/bulk-restore', [InvoiceController::class, 'bulkRestore'])
        ->middleware('permission:BULK_RESTORE_INVOICES')->name('bulk-restore');

    Route::get('/{uuid}/pdf', InvoicePdfController::class)
        ->middleware(['permission:EXPORT_INVOICES', 'throttle:10,1'])
        ->whereUuid('uuid')
        ->name('pdf');

    Route::get('/{uuid}', [InvoiceController::class, 'show'])
        ->middleware('permission:VIEW_INVOICES')->whereUuid('uuid')->name('show');

    Route::put('/{uuid}', [InvoiceController::class, 'update'])
        ->middleware('permission:UPDATE_INVOICES')->whereUuid('uuid')->name('update');

    Route::delete('/{uuid}', [InvoiceController::class, 'destroy'])
        ->middleware('permission:DELETE_INVOICES')->whereUuid('uuid')->name('destroy');

    Route::post('/{uuid}/restore', [InvoiceController::class, 'restore'])
        ->middleware('permission:RESTORE_INVOICES')->whereUuid('uuid')->name('restore');
});
