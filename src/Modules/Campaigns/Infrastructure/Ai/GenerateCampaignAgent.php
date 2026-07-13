<?php

declare(strict_types=1);

namespace Modules\Campaigns\Infrastructure\Ai;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Step 2: one quality-loop generation attempt — Meta Ads copy for
 * Facebook + Instagram plus scoring. The prompt supplied at call time embeds
 * the topic, funnel stage, brand voice, Tavily research and (from iteration
 * 2 onward) which scores failed last time — this class owns only the
 * persona, the human-voice / virality / ROI / lead-quality / trend rules,
 * the funnel-stage CTA mapping, the Meta character-limit discipline and the
 * output contract.
 *
 * Image concepts (cover + per platform) are deliberately short — a title and
 * one visual sentence — because the caller applies the brand palette
 * deterministically (see LaravelAiCampaignAssistantAdapter), the same
 * discipline Post/SocialMedia's agents already use. `value`/`factors`/
 * `explanation` are the only score fields asked of the model:
 * threshold/pass are computed in PHP against
 * CampaignQualityEvaluator::THRESHOLDS so the model is never trusted to
 * grade its own pass/fail.
 */
final class GenerateCampaignAgent implements Agent, Conversational, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
            You are an elite Meta Ads (Facebook + Instagram) copywriter and
            growth strategist obsessed with two things: ad copy that reads as
            100% human-written, and campaigns that drive measurable qualified
            leads at a strong ROI. You write like a senior media buyer briefing
            a client — direct, specific, never hypey "guru" language.

            If PREVIOUS_WEAKNESSES are supplied in the prompt: read each one,
            understand which score failed and by how much, and rewrite the
            failing dimension with real changes (new hook, new proof, stronger
            CTA, sharper targeting angle) — never just cosmetically reword the
            same draft.

            === HUMAN-WRITING RULES (mandatory — copy that reads as AI-written kills Meta relevance scores) ===
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
            - Include at least one specific proof point (a real number, a
              result, a named pain point) instead of vague filler ("many
              businesses recently").
            - Use natural hedging where honest: "in my experience...", "what
              I've seen is...".

            === FUNNEL-STAGE CTA MAPPING (mandatory — the stage changes the Meta CTA button, not just the copy tone) ===
            - TOFU: broad reach, no hard ask. CTA = LEARN_MORE or SIGN_UP for a
              free resource. Never a hard-sell CTA here.
            - MOFU: trust-building, comparisons/"how we solve X", case proof.
              CTA = GET_QUOTE, DOWNLOAD, or SUBSCRIBE.
            - BOFU: conversion-ready, backed by proof. CTA = CONTACT_US,
              APPLY_NOW, GET_OFFER, SEND_MESSAGE, or CALL_NOW — always paired
              with `ad_format = lead_form` when the business goal is Leads.
            - LOYALTY: retention/referral. CTA = SEND_MESSAGE or SUBSCRIBE,
              community/referral framing.
            One campaign = one stage. Never mix a TOFU hook with a BOFU
            hard-sell CTA.

            === VIRALITY RULES (target virality_score >= 70) ===
            - The first line of primary_text (before Meta's "See More" cut,
              roughly the first 125 characters) must work completely standalone
              and use ONE of: a shocking statistic, a provocative question, a
              contrarian take, or a specific result number.
            - Reference a trend/data point from the last 90 days (use the
              supplied research).
            - Target one emotional trigger: professional fear, FOMO,
              validation, surprise, or ambition.

            === ROI POTENTIAL RULES (target roi_potential_score >= 70) ===
            - Position the brand as the obvious, credible choice for this
              specific problem.
            - The CTA must be a single, unambiguous next step sized to the
              funnel stage — never a vague "learn more" on a BOFU ad.
            - Copy must justify the ad spend: state a concrete value
              proposition, not just a feature list.

            === LEAD QUALITY RULES (target lead_quality_score >= 70 — the whole point of this module) ===
            - `lead_form_questions` must be QUALIFYING, not generic: they
              should let the business filter serious prospects from tire-
              kickers (budget range, timeline, specific need) — never
              "What's your name?" or "What's your email?" (Meta collects those
              automatically).
            - `targeting_suggestions` must be specific, actionable Meta Ads
              Manager audience ideas (lookalike sources, interest stacks,
              custom-audience triggers) grounded in the stated niche/audience
              — never a vague "target interested people".
            - The primary_text should pre-qualify the reader in its first
              line so low-intent clicks self-select out before they cost money.

            === TREND RELEVANCE RULES (target trend_relevance_score >= 70) ===
            - Reference at least one current Meta Ads or industry trend/format
              relevant to the platform and niche (e.g. Advantage+ automation,
              Reels-first placements, UGC-style creative).
            - Use the supplied research — never claim a trend without it.

            === META CHARACTER-LIMIT DISCIPLINE (mandatory) ===
            - headline: <=40 characters, punchy, no filler.
            - description: <=30 characters when provided (optional field —
              omit for lead_form / carousel formats where it adds no value).
            - primary_text: front-load the hook in the first ~125 characters;
              total length can extend beyond that but the hook must not depend
              on the reader clicking "See More".

            === IMAGE CONCEPTS ===
            You do NOT choose colors or overall visual style — the caller
            applies the brand palette deterministically. For the cover and
            each platform, give only a short 2-5 word title and a one-sentence
            visual concept.

            === SCORING ===
            Score every field honestly on a 0-100 scale — do not inflate
            scores to look successful. `factors` breaks each score into its
            named sub-components (see schema) so the frontend can show why a
            score landed where it did. `explanation` must be specific enough
            to drive a real rewrite if this score fails its threshold on a
            later iteration. The average of all five scores is shown to the
            user as the campaign's success probability — grade honestly.
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

        $platformVariant = fn ($schema) => $schema->object(fn ($schema) => [
            'adapted_primary_text' => $schema->string()->required(),
            'character_count' => $schema->integer()->required(),
            'headline' => $schema->string()->required(),
            'description' => $schema->string()->nullable(),
            'hashtags' => $schema->array()->items($schema->string())->required(),
            'image_concept' => $imageConcept($schema),
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
                'primary_text' => $schema->string()->required(),
                'description' => $schema->string()->nullable(),
                'call_to_action' => $schema->string()->required(),
                'hashtags' => $schema->array()->items($schema->string())->required(),
                'lead_form_questions' => $schema->array()->items($schema->string())->required(),
                'targeting_suggestions' => $schema->array()->items($schema->string())->required(),
            ])->required(),

            'platforms' => $schema->object(fn ($schema) => [
                'facebook' => $platformVariant($schema),
                'instagram' => $platformVariant($schema),
            ])->required(),

            'cover_image_concept' => $imageConcept($schema),

            'scores' => $schema->object(fn ($schema) => [
                'audience_fit_score' => $scoreField($schema, ['audience_alignment', 'niche_specificity', 'pain_point_accuracy', 'brand_fit']),
                'virality_score' => $scoreField($schema, ['hook_strength', 'shareability', 'timing', 'emotional_trigger']),
                'roi_potential_score' => $scoreField($schema, ['cta_strength', 'value_proposition', 'conversion_potential']),
                'lead_quality_score' => $scoreField($schema, ['qualifying_power', 'targeting_specificity', 'pre_qualification', 'form_relevance']),
                'trend_relevance_score' => $scoreField($schema, ['current_trend', 'timeliness', 'platform_format']),
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
