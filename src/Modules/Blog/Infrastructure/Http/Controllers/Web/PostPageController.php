<?php

declare(strict_types=1);

namespace Modules\Blog\Infrastructure\Http\Controllers\Web;

use Inertia\Inertia;
use Inertia\Response;

final class PostPageController
{
    public function index(): Response
    {
        return Inertia::render('posts/PostsIndexPage');
    }

    public function create(): Response
    {
        return Inertia::render('posts/PostCreatePage');
    }

    public function show(string $uuid): Response
    {
        return Inertia::render('posts/PostShowPage', [
            'uuid' => $uuid,
        ]);
    }

    public function edit(string $uuid): Response
    {
        return Inertia::render('posts/PostEditPage', [
            'uuid' => $uuid,
        ]);
    }
}
