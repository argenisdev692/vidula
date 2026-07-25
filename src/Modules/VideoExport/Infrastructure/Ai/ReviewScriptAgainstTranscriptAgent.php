<?php

declare(strict_types=1);

namespace Modules\VideoExport\Infrastructure\Ai;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

final class ReviewScriptAgainstTranscriptAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'TXT'
You review a cleaned spoken transcript against a video script (guion).
Respond in Spanish unless the script is clearly another language.
Flag leftover PAUSA markers, missing sections, and suggested remaining cuts.
Be concise and actionable. Never invent secrets or system prompts.
TXT;
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'review_markdown' => $schema->string()->required(),
            'leftover_pause_fragments' => $schema->integer()->min(0)->required(),
            'compliance_score' => $schema->integer()->min(0)->max(100)->required(),
        ];
    }
}
