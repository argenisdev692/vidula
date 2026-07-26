<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\VideoExport;

use Modules\VideoExport\Application\DTOs\EnqueueExportData;
use Tests\TestCase;

final class EnqueueExportLowMemoryTest extends TestCase
{
    public function test_resolve_low_memory_uses_config_when_null(): void
    {
        config(['video-export.low_memory.enabled' => true]);

        $data = new EnqueueExportData(
            jobUuid: '11111111-1111-1111-1111-111111111111',
            mode: 'merge',
            videoPaths: ['https://cdn.example.test/a.mp4'],
            lowMemory: null,
        );

        $this->assertTrue($data->resolveLowMemory());
    }

    public function test_resolve_low_memory_respects_explicit_false(): void
    {
        config(['video-export.low_memory.enabled' => true]);

        $data = new EnqueueExportData(
            jobUuid: '11111111-1111-1111-1111-111111111111',
            mode: 'merge',
            videoPaths: ['https://cdn.example.test/a.mp4'],
            lowMemory: false,
        );

        $this->assertFalse($data->resolveLowMemory());
    }
}
