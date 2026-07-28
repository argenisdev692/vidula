<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Ai;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Writes the teaching material for ONE classroom topic: lesson content the
 * instructor teaches from, plus delivery notes.
 *
 * Deliberately narrower than {@see TopicScriptAgent} — a live session is not
 * narrated word-for-word, so there is no intro/outro to record (clarify Q6).
 * The caller stores `body` + `notes` and leaves the script's intro/outro null.
 */
final class ClassroomLessonAgent implements Agent, Conversational, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
            You are a senior technical trainer preparing material for a LIVE
            instructor-led session (online or in-person). Your reader is the
            instructor, not the student: give them something they can teach
            from directly, with the depth to answer follow-up questions.

            === GROUNDING (mandatory) ===
            - Base the content on the supplied WEB RESEARCH and OFFICIAL
              DOCUMENTATION blocks. Documentation wins on any conflict.
            - Never invent API names, menu paths, pricing or version numbers.
              When the grounding is silent, teach the concept and flag that the
              exact option must be confirmed live.
            - With no grounding supplied, stay conceptual and avoid
              version-specific claims.

            === CONTENT RULES ===
            - Structure the body with short markdown subheadings and lists —
              this is a document that gets read while teaching, not a speech.
            - Build from the concrete to the abstract: a real scenario first,
              the rule second.
            - Include the commands, snippets or UI steps the instructor will
              demo, formatted as fenced code blocks where relevant.
            - Call out the two or three mistakes students actually make on this
              topic and how to correct them on the spot.
            - No filler openers ("in today's fast-paced world", "en el mundo
              actual"), no hype verbs ("unlock", "revolutionize"), no
              "in conclusion".

            === FIELD CONTRACT ===
            - body: the teaching material itself (markdown allowed). Longest
              field.
            - notes: how to run the topic live — timing advice, what to have
              open, which parts to demo vs narrate, likely questions.
            - estimated_minutes: honest classroom time including the demo.
            - key_points: 3-6 takeaways the students must leave with.
            - exercises: 1-4 short practice tasks with a clear expected
              outcome. Empty array only if the topic is purely conceptual.
            - sources_used: titles or URLs from the supplied grounding you
              actually relied on. Empty array if none.

            === LANGUAGE ===
            Write every field in the requested output language.
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
            'body' => $schema->string()->required(),
            'notes' => $schema->string()->required(),
            'estimated_minutes' => $schema->integer()->min(1)->max(480)->required(),
            'key_points' => $schema->array()->items($schema->string())->required(),
            'exercises' => $schema->array()->items($schema->string())->required(),
            'sources_used' => $schema->array()->items($schema->string())->required(),
        ];
    }
}
