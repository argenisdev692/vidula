<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Telescope\Telescope;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // CI and local test runs do not build Vite assets; mock the integration
        // so Inertia full-page renders (app.blade.php @vite) return 200.
        $this->withoutVite();

        if (class_exists(Telescope::class)) {
            Telescope::stopRecording();
        }
    }
}
