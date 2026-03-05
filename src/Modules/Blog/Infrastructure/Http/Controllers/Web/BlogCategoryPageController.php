<?php

declare(strict_types=1);

namespace Modules\Blog\Infrastructure\Http\Controllers\Web;

use Inertia\Inertia;
use Inertia\Response;

/**
 * BlogCategoryPageController — Inertia page controllers for blog categories.
 */
final class BlogCategoryPageController
{
    public function index(): Response
    {
        return Inertia::render('blog-categories/BlogCategoriesIndexPage');
    }

    public function create(): Response
    {
        return Inertia::render('blog-categories/BlogCategoryCreatePage');
    }

    public function show(string $uuid): Response
    {
        return Inertia::render('blog-categories/BlogCategoryShowPage', [
            'uuid' => $uuid,
        ]);
    }

    public function edit(string $uuid): Response
    {
        return Inertia::render('blog-categories/BlogCategoryEditPage', [
            'uuid' => $uuid,
        ]);
    }
}
