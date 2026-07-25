<?php

declare(strict_types=1);

namespace Modules\VideoExport\Infrastructure\Pipeline;

use Illuminate\Support\Facades\Http;
use Modules\VideoExport\Domain\ValueObjects\WordTimestamp;
use RuntimeException;

/** OpenAI Whisper word-level transcription. */
final readonly class OpenAiWhisperTranscriber
{
    public function isConfigured(): bool
    {
        return filled(config('ai.providers.openai.key') ?? env('OPENAI_API_KEY'));
    }

    /**
     * @return list<WordTimestamp>
     */
    public function transcribeWords(string $audioPath, string $language): array
    {
        $apiKey = (string) (config('ai.providers.openai.key') ?? env('OPENAI_API_KEY'));
        if ($apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEY is required for AI cleaning.');
        }

        $max = (int) config('video-export.whisper.max_file_bytes', 25165824);
        $bytes = filesize($audioPath) ?: 0;
        if ($bytes > $max) {
            throw new RuntimeException('Audio for transcription exceeds the Whisper size limit.');
        }

        $model = (string) config('video-export.whisper.model', 'whisper-1');
        $response = Http::withToken($apiKey)
            ->timeout(120)
            ->attach('file', file_get_contents($audioPath) ?: '', basename($audioPath))
            ->post('https://api.openai.com/v1/audio/transcriptions', [
                'model' => $model,
                'language' => $language,
                'response_format' => 'verbose_json',
                'timestamp_granularities[]' => 'word',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Whisper transcription failed.');
        }

        /** @var list<array{word?: string, start?: float|int, end?: float|int}> $words */
        $words = $response->json('words') ?? [];
        $out = [];
        foreach ($words as $word) {
            $text = (string) ($word['word'] ?? '');
            if ($text === '') {
                continue;
            }
            $out[] = new WordTimestamp(
                text: $text,
                start: (float) ($word['start'] ?? 0),
                end: (float) ($word['end'] ?? 0),
            );
        }

        return $out;
    }
}
