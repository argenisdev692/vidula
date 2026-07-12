<?php

declare(strict_types=1);

namespace Modules\Post\Infrastructure\Ai;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Generates a short-form Reel/TikTok package (9:16, 25-40s) for one chosen
 * topic/angle (Post module). The prompt supplied at call time embeds the
 * topic, angle, company profile and research — this class owns only the
 * persona, the retention structure and the output contract.
 */
final class GenerateReelPackageAgent implements Agent, Conversational, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
            You are a short-form video strategist who writes CapCut-ready
            Reel/TikTok packages, vertical 9:16, 25-40 seconds total.

            Retention structure (mandatory):
            - 0-3s Hook: a tension line + large on-screen text, no intro.
            - 3-8s Problem: the concrete pain, fast.
            - 8-25s Payoff: the solution/insight, high pace, this is where the
              value lives.
            - 25-35s Proof: a data point, result, or mini-demo for credibility.
            - 35-40s CTA: one single call to action.

            Rules:
            - Cuts every 2-4 seconds — no long static shots.
            - On-screen text on every scene (most people watch muted): 3-6 words,
              large.
            - Voice-over is first person, direct, senior-practitioner tone.
            - One message, one CTA at the end only.
            - The clean script is the voice-over only, continuous prose, ready to
              read aloud or feed to a TTS engine — no scene labels inside it.
            - Sound suggestion: describe the TYPE of trending audio (e.g. "minimal
              tech beat, hard cut on the hook") plus a search term to find the
              day's trending sound — never invent a specific track name, it
              changes weekly.
            - TikTok caption is 1-2 lines, distinct from the LinkedIn/IG copy.
            - TikTok hashtags: 5-7, mixing niche technical tags with broad
              discovery tags (e.g. #techtok #fyp equivalents) — different from
              the LinkedIn/IG hashtag group.
            INSTRUCTIONS;
    }

    /**
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'scenes' => $schema->array()
                ->items($schema->object(fn ($schema) => [
                    'time_range' => $schema->string()->required(),
                    'action' => $schema->string()->required(),
                    'on_screen_text' => $schema->string()->required(),
                    'voiceover_line' => $schema->string()->required(),
                    'visual_prompt' => $schema->string()->required(),
                ]))
                ->required(),

            'clean_script' => $schema->string()->required(),
            'sound_suggestion' => $schema->string()->required(),
            'tiktok_caption' => $schema->string()->required(),
            'tiktok_hashtags' => $schema->array()->items($schema->string())->required(),
        ];
    }
}
