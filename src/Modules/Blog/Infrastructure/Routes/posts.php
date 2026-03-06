<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Blog\Infrastructure\Http\Controllers\Api\AdminPostController;
use Modules\Blog\Infrastructure\Http\Controllers\Api\PostExportController;
use Modules\Blog\Infrastructure\Http\Controllers\Web\PostPageController;

Route::get('/', [PostPageController::class, 'index'])->name('posts.index');
Route::get('/create', [PostPageController::class, 'create'])->name('posts.create');
Route::get('/{uuid}', [PostPageController::class, 'show'])->name('posts.show')->whereUuid('uuid');
Route::get('/{uuid}/edit', [PostPageController::class, 'edit'])->name('posts.edit')->whereUuid('uuid');

Route::prefix('data')->group(function (): void {
    Route::middleware(['role:SUPER_ADMIN'])->prefix('admin')->group(function (): void {
        Route::get('/export', PostExportController::class)->name('posts.data.export');
        Route::get('/', [AdminPostController::class, 'index'])->name('posts.data.index');
        Route::post('/', [AdminPostController::class, 'store'])->name('posts.data.store');
        Route::get('/{uuid}', [AdminPostController::class, 'show'])->name('posts.data.show')->whereUuid('uuid');
        Route::put('/{uuid}', [AdminPostController::class, 'update'])->name('posts.data.update')->whereUuid('uuid');
        Route::delete('/{uuid}', [AdminPostController::class, 'destroy'])->name('posts.data.destroy')->whereUuid('uuid');
        Route::patch('/{uuid}/restore', [AdminPostController::class, 'restore'])->name('posts.data.restore')->whereUuid('uuid');
        Route::post('/bulk-delete', [AdminPostController::class, 'bulkDelete'])->name('posts.data.bulk-delete');
    });
});
