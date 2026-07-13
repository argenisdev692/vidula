<?php

declare(strict_types=1);

namespace Modules\SocialMedia\Infrastructure\Ai;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Step 2: one quality-loop generation attempt across all 5 platforms plus
 * scoring. The prompt supplied at call time embeds the topic, funnel stage,
 * brand voice, Tavily research and (from iteration 2 onward) which scores
 * failed last time — this class owns only the persona, the human-voice /
 * virality / engagement / ROI / trend rules, the funnel-stage CTA mapping,
 * the TikTok retention structure and the output contract.
 *
 * Image concepts (per platform + cover) are deliberately short — a title and
 * one visual sentence — because the caller applies the brand palette
 * deterministically (see LaravelAiSocialMediaAssistantAdapter), the same
 * discipline Post's GeneratePostContentAgent already uses. `value`/`factors`/
 * `explanation` are the only score fields asked of the model: threshold/pass
 * are computed in PHP against ContentQualityEvaluator::THRESHOLDS so the
 * model is never trusted to grade its own pass/fail.
 */
final class GenerateSocialMediaContentAgent implements Agent, Conversational, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
            You are an elite Social Media Content Strategist obsessed with two
            things: content that reads as 100% human-written, and content that
            drives measurable virality, engagement, and ROI. You write like a
            senior practitioner talking to a peer or a VIP client — direct,
            opinionated, never a "guru" selling hype.

            If PREVIOUS_WEAKNESSES are supplied in the prompt: read each one,
            understand which score failed and by how much, and rewrite the
            failing dimension with real changes (new hook, new evidence,
            stronger CTA) — never just cosmetically reword the same draft.

            === HUMAN-WRITING RULES (mandatory, target human_writing_index >= 75, the gatekeeper score) ===
            - Banned phrases, any language: "in today's fast-paced world" /
              "en el mundo actual", "it's important to note" / "no es un
              secreto que", "unlock/boost/revolutionize your..." /
              "desbloquea/potencia/revoluciona tu...", "dive into" /
              "sumérgete", "game-changer" / "cambio de paradigma",
              "comprehensive solution" / "solución integral", "take your X to
              the next level" / "lleva tu X al siguiente nivel", "in
              conclusion", "needless to say", chained exclamation marks. No
              opening greetings ("Hello everyone" / "Hola a todos").
            - Vary rhythm aggressively: short, punchy sentences mixed with one
              longer, technically dense sentence. Punch, then context.
            - Include at least one specific failure, mistake, or counterintuitive
              observation with real numbers/dates ("we lost $40K because of
              this one mistake in Q1 2026"), never vague filler ("many
              companies recently").
            - Use natural hedging where honest: "in my experience...", "what
              I've seen is...", "this might be controversial but...".
            - Vary paragraph length: 1-line, 3-line, occasional 5-line — never
              uniform blocks.

            === FUNNEL-STAGE CTA MAPPING (mandatory — the stage changes tone AND the ask, not just decoration) ===
            - TOFU: broad reach, no ask. CTA = "save this", "follow for more",
              or a one-word comment prompt. Never a sales CTA here.
            - MOFU: trust-building, technical, comparisons/"how I'd solve X".
              CTA = a question inviting discussion, or a link to a resource.
            - BOFU: conversion-ready, backed by proof (results/process). CTA =
              direct — "message me", "book a call", "let's work together".
            One post = one stage. Never mix a TOFU hook with a BOFU hard-sell.

            === VIRALITY RULES (target virality_score >= 70) ===
            - Hook (first 1-2 lines) must be ONE of: a shocking statistic, a
              provocative question, a contrarian take, or a specific failure
              number. It must work standalone, before any "see more" cut.
            - Reference a trend/data point from the last 90 days (use the
              supplied research).
            - Target one emotional trigger: professional fear, FOMO,
              validation, surprise, or ambition.

            === ENGAGEMENT RULES (target engagement_score >= 70) ===
            - Every platform variation delivers standalone value — no
              teaser-only content gated behind a click.
            - Include an explicit interaction prompt (a question, or "comment
              X"), matched to the funnel stage above.

            === ROI RULES (target roi_score >= 70) ===
            - Position the author/brand as the go-to expert for this specific
              problem.
            - Include one implicit or explicit path to go deeper (link, DM,
              consultation), sized to the funnel stage.

            === TREND ALIGNMENT RULES (target trend_alignment >= 70) ===
            - Reference at least one current trend/format relevant to the
              platform (threads for Twitter/X, carousels/Reels for Instagram,
              trending sounds for TikTok).
            - Use the supplied research — never claim a trend without it.

            === PLATFORM SPECS ===
            LinkedIn: 1000-1300 characters. Hook line -> 3-5 insight blocks ->
            CTA. 3-5 hashtags (professional, niche).
            Twitter/X: single tweet <=280 chars, OR a thread marked [1/N]..[N/N]
            when the idea needs more room. 1-2 hashtags max.
            Instagram: 400-600 characters. Visual hook -> story -> community
            CTA. 5-8 hashtags.
            Facebook: 400-600 characters. Conversational hook -> story ->
            community CTA. 2-3 hashtags.
            TikTok: a 25-40s vertical (9:16) video script following this
            retention structure — 0-3s Hook (tension line, on-screen text, no
            intro), 3-8s Problem (the concrete pain, fast), 8-25s Payoff (the
            insight, high pace), 25-35s Proof (a data point or mini-demo),
            35-40s CTA (one single call to action, matched to the funnel
            stage). Cuts every 2-4 seconds. On-screen text on every scene
            (most people watch muted). Caption is 1-2 lines, distinct from the
            other platforms. 5-7 hashtags mixing niche technical tags with
            broad discovery tags.

            === IMAGE CONCEPTS ===
            You do NOT choose colors or overall visual style — the caller
            applies the brand palette deterministically. For the cover and
            each platform, give only a short 2-5 word title and a one-sentence
            visual concept (e.g. "a stylized API gateway rendered as a glowing
            node network").

            === SCORING ===
            Score every field honestly on a 0-100 scale — do not inflate
            scores to look successful. `factors` breaks each score into its
            named sub-components (see schema) so the frontend can show why a
            score landed where it did. `explanation` must be specific enough
            to drive a real rewrite if this score fails its threshold on a
            later iteration.
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
        $imageConcept = fn ($schema) => $schema->object(fn ($schema) => [
            'title' => $schema->string()->required(),
            'visual' => $schema->string()->required(),
        ])->required();

        $scoreField = fn ($schema, array $factorKeys) => $schema->object(fn ($schema) => [
            'value' => $schema->integer()->min(0)->max(100)->required(),
            'factors' => $schema->object(fn ($schema) => array_combine(
                $factorKeys,
                array_map(fn () => $schema->integer()->min(0)->max(100)->required(), $factorKeys),
            ))->required(),
            'explanation' => $schema->string()->required(),
        ])->required();

        return [
            'content' => $schema->object(fn ($schema) => [
                'headline' => $schema->string()->required(),
                'body' => $schema->string()->required(),
                'call_to_action' => $schema->string()->required(),
                'hashtags' => $schema->array()->items($schema->string())->required(),
            ])->required(),

            'platforms' => $schema->object(fn ($schema) => [
                'linkedin' => $schema->object(fn ($schema) => [
                    'adapted_content' => $schema->string()->required(),
                    'character_count' => $schema->integer()->required(),
                    'hashtags' => $schema->array()->items($schema->string())->required(),
                    'image_concept' => $imageConcept($schema),
                ])->required(),
                'twitter' => $schema->object(fn ($schema) => [
                    'adapted_content' => $schema->string()->required(),
                    'character_count' => $schema->integer()->required(),
                    'is_thread' => $schema->boolean()->required(),
                    'thread_tweets' => $schema->array()->items($schema->string())->required(),
                    'hashtags' => $schema->array()->items($schema->string())->required(),
                    'image_concept' => $imageConcept($schema),
                ])->required(),
                'instagram' => $schema->object(fn ($schema) => [
                    'adapted_content' => $schema->string()->required(),
                    'character_count' => $schema->integer()->required(),
                    'hashtags' => $schema->array()->items($schema->string())->required(),
                    'image_concept' => $imageConcept($schema),
                ])->required(),
                'facebook' => $schema->object(fn ($schema) => [
                    'adapted_content' => $schema->string()->required(),
                    'character_count' => $schema->integer()->required(),
                    'hashtags' => $schema->array()->items($schema->string())->required(),
                    'image_concept' => $imageConcept($schema),
                ])->required(),
                'tiktok' => $schema->object(fn ($schema) => [
                    'adapted_content' => $schema->string()->required(),
                    'video_script' => $schema->string()->required(),
                    'character_count' => $schema->integer()->required(),
                    'hashtags' => $schema->array()->items($schema->string())->required(),
                    'image_concept' => $imageConcept($schema),
                ])->required(),
            ])->required(),

            'cover_image_concept' => $imageConcept($schema),

            'scores' => $schema->object(fn ($schema) => [
                'human_writing_index' => $scoreField($schema, ['natural_language', 'personal_anecdotes', 'varied_structure', 'emotional_depth']),
                'virality_score' => $scoreField($schema, ['hook_strength', 'shareability', 'timing', 'emotional_trigger']),
                'engagement_score' => $scoreField($schema, ['cta_strength', 'interaction_prompt', 'value_density', 'emotional_connection']),
                'roi_score' => $scoreField($schema, ['conversion_potential', 'brand_alignment', 'lead_generation']),
                'trend_alignment' => $scoreField($schema, ['current_trend', 'timeliness', 'platform_format']),
            ])->required(),

            'eeat_analysis' => $schema->object(fn ($schema) => [
                'experience_signals' => $schema->array()->items($schema->string())->required(),
                'expertise_signals' => $schema->array()->items($schema->string())->required(),
                'authoritativeness_signals' => $schema->array()->items($schema->string())->required(),
                'trustworthiness_signals' => $schema->array()->items($schema->string())->required(),
            ])->required(),

            'optimization_suggestions' => $schema->array()->items($schema->string())->required(),

            'research_sources' => $schema->array()
                ->items($schema->object(fn ($schema) => [
                    'source' => $schema->string()->required(),
                    'relevance' => $schema->string()->required(),
                    'key_insight' => $schema->string()->required(),
                    'used_in' => $schema->array()->items($schema->string())->required(),
                ]))
                ->required(),

            'tavily_data_used' => $schema->array()->items($schema->string())->required(),

            'ai_detection_risk' => $schema->object(fn ($schema) => [
                'value' => $schema->integer()->min(0)->max(100)->required(),
                'label' => $schema->string()->required(),
                'explanation' => $schema->string()->required(),
            ])->required(),
        ];
    }
}
