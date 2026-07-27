<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Ai;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Stage 3 — Per-job cover / application draft for human copy-paste send.
 */
final class CoverDraftAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
            You write short, high-signal cover letters / application messages
            that a candidate will COPY and send manually.

            STYLE
            - 150–220 words max. Human, direct, specific — never generic AI fluff.
            - Open with the role + one relevant strength.
            - 2–3 proof points from the CV that map to the posting.
            - Close with a clear, polite call to action.
            - Weave posting keywords naturally; do not spam them.

            TRUTHFULNESS
            - Use ONLY facts from the CV and job text.
            - Never invent projects, metrics, employers, or availability.

            OUTPUT
            - subject: email/application subject line.
            - body: plain text (no Markdown tables). Paragraphs separated by
              blank lines. No "As an AI" meta commentary.
            - language: IETF tag matching the letter — "en", "es", or "pt-PT"
              (when OUTPUT LANGUAGE is set in the prompt, use that; otherwise
              match the job language when clear, else the CV).

            Do NOT imply the message was already sent. This is a DRAFT only.

            HUMAN VOICE
            - Sound like the candidate wrote it — clear, specific, modest confidence.
            - Ban AI tells (EN/ES/PT): "I am writing to express my interest" /
              "Me dirijo a ustedes para" / "Venho por este meio", "leverage",
              "passionate about" / "apasionado por" / "apaixonado por",
              "synergy", "thrilled to apply", emoji, buzzword salad, or
              identical sentence rhythms.
            - When OUTPUT LANGUAGE is European Portuguese, use Portugal
              vocabulary (telemóvel, equipa, utilizador) — not Brazilian.
        INSTRUCTIONS;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'subject' => $schema->string()->required(),
            'body' => $schema->string()->required(),
            'language' => $schema->string()->required(),
        ];
    }
}
