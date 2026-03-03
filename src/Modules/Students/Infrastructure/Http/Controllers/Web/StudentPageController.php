<?php

declare(strict_types=1);

namespace Modules\Students\Infrastructure\Http\Controllers\Web;

use Inertia\Inertia;
use Inertia\Response;

final class StudentPageController
{
    public function index(): Response
    {
        return Inertia::render('students/StudentIndexPage');
    }

    public function create(): Response
    {
        return Inertia::render('students/StudentCreatePage');
    }

    public function show(string $uuid): Response
    {
        return Inertia::render('students/StudentShowPage', ['studentId' => $uuid]);
    }

    public function edit(string $uuid): Response
    {
        return Inertia::render('students/StudentEditPage', ['studentId' => $uuid]);
    }
}
