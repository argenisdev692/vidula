<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\VideoExport;

use Illuminate\Support\Facades\Http;
use Modules\VideoExport\Infrastructure\Pipeline\HttpAudioDenoiseAdapter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class HttpAudioDenoiseAdapterTest extends TestCase
{
    #[Test]
    public function it_requires_https_endpoint(): void
    {
        $http = new HttpAudioDenoiseAdapter('http://insecure.example/denoise');
        $https = new HttpAudioDenoiseAdapter('https://cleaner.example/v1/denoise');

        $this->assertFalse($http->isConfigured());
        $this->assertTrue($https->isConfigured());
    }

    #[Test]
    public function it_posts_audio_and_writes_response_body(): void
    {
        Http::fake([
            'https://cleaner.example/*' => Http::response('CLEAN-BYTES', 200),
        ]);

        $input = tempnam(sys_get_temp_dir(), 've-in-');
        $output = tempnam(sys_get_temp_dir(), 've-out-');
        $this->assertNotFalse($input);
        $this->assertNotFalse($output);
        file_put_contents($input, 'RAW-WAV');

        try {
            $adapter = new HttpAudioDenoiseAdapter(
                endpointUrl: 'https://cleaner.example/v1/denoise',
                token: 'secret',
            );
            $adapter->enhance($input, $output);

            $this->assertSame('CLEAN-BYTES', file_get_contents($output));
            Http::assertSent(function ($request): bool {
                return $request->url() === 'https://cleaner.example/v1/denoise'
                    && $request->hasHeader('Authorization', 'Bearer secret');
            });
        } finally {
            @unlink($input);
            @unlink($output);
        }
    }
}
