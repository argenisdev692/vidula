<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Http\Controllers\Web;

use Inertia\Inertia;
use Inertia\Response;

final class ProductPageController
{
    public function index(): Response
    {
        return Inertia::render('products/ProductIndexPage');
    }

    public function create(): Response
    {
        return Inertia::render('products/ProductCreatePage');
    }

    public function show(string $uuid): Response
    {
        return Inertia::render('products/ProductShowPage', ['productId' => $uuid]);
    }

    public function edit(string $uuid): Response
    {
        return Inertia::render('products/ProductEditPage', ['productId' => $uuid]);
    }
}
