# Campaigns AI Module — Live Spec (Meta Ads)

> **Source of truth for the running product.** The live `Modules\Campaigns` module generates **paid Meta Ads (Facebook + Instagram)** packages — not a standalone video-production ZIP/ElevenLabs pipeline. Organic CapCut reels live in **Social Media** (`Modules\SocialMedia`).

---

## What this module does

| Concern | Campaigns (paid Meta) | Social Media (organic) |
|---|---|---|
| Goal | Qualified leads / ROAS | Virality + engagement + ROI |
| Surfaces | Facebook + Instagram ad copy | LinkedIn, X, IG, FB, TikTok |
| Video | CapCut pack when `ad_format` = `reel` or `story` | CapCut pack on TikTok + IG Reels |
| Duration | Stage-aware **15–30s** | Stage-aware **15–30s** |
| Creative | **UGC-native** (mandatory) | **UGC-native** (mandatory) |
| Funnel | TOFU · MOFU · BOFU · LOYALTY | TOFU · MOFU · BOFU |
| Languages | `es` · `en` · `pt-PT` (Portugal, not BR) | Same |
| Geo | Optional city/state/country/location (overrides company profile) | Company profile context |

---

## Flow (2 steps + quality loop)

```
PASO 1: niche + audience + geo? + Tavily → 10 Meta Ads angles
        each classified TOFU/MOFU/BOFU/LOYALTY (+ estimated virality/ROI/leads)
                              ↓ user picks 1
PASO 2: quality loop ≤ 5 iterations
        Tavily (geo-aware) → GenerateCampaignAgent → PHP score gates
        scores: audience_fit≥75 · virality≥70 · roi_potential≥70 ·
                lead_quality≥70 · trend_relevance≥70
                              ↓
        persist ready | needs_review (+ quality_warning if best-of-5)
```

- Job: `GenerateCampaignJob` (Horizon/redis)
- Progress: Reverb → `CampaignAiGenerationProgress`
- Cache: module adapter only (15 min TTL; iteration 1 only)

---

## Funnel + CTA rules (ROI)

Budget framing for a healthy Meta account (2026): ~60–70% TOFU / 20–30% MOFU / 5–10% BOFU / 5–10% Loyalty.

| Stage | Tone | Meta CTA examples | Video length |
|---|---|---|---|
| TOFU | Educate, no hard sell | LEARN_MORE, SIGN_UP | **15s** |
| MOFU | Trust, proof, comparison | GET_QUOTE, DOWNLOAD, SUBSCRIBE | **21–30s** |
| BOFU | Urgency + proof | CONTACT_US, APPLY_NOW, CALL_NOW (+ lead_form when goal=leads) | **15–20s** |
| LOYALTY | Community / referral | SEND_MESSAGE, SUBSCRIBE | **15–25s** |

One campaign = one stage. Never mix a TOFU hook with a BOFU hard-sell CTA.

---

## Scores (PHP thresholds — model does not self-pass)

| Score | Threshold | Gatekeeper |
|---|---|---|
| audience_fit_score (includes `geographic_relevance`) | ≥ 75 | ✅ |
| virality_score | ≥ 70 | |
| roi_potential_score | ≥ 70 | |
| lead_quality_score | ≥ 70 | |
| trend_relevance_score | ≥ 70 | |

Overall average → `success_probability_label` (`very_high` / `high` / `medium` / `low`).

---

## Video package (`reel` / `story` only)

Nested under each platform variant as `video_package`:

- `scenes[]`: time_range, action, on_screen_text, voiceover_line, visual_prompt
- `clean_script`, `sound_suggestion`
- `target_duration_seconds` (15–30, stage-aware)
- `creative_style`: always `ugc_native`

For `feed` / `carousel` / `lead_form`: `video_package` is null.

---

## Geo / local market

1. `CompanyProfile::data()` exposes `city`, `state`, `country` (+ `address`).
2. Wizard may override via `city` / `state` / `country` / `location` on suggest + generate.
3. Tavily queries and prompts include the resolved geo label.
4. `audience_fit_score.factors.geographic_relevance` must be scored honestly.

---

## Key code

| Layer | Path |
|---|---|
| Suggest agent | `Infrastructure/Ai/SuggestCampaignTopicsAgent.php` |
| Generate agent | `Infrastructure/Ai/GenerateCampaignAgent.php` |
| Adapter | `Infrastructure/Ai/LaravelAiCampaignAssistantAdapter.php` |
| Quality loop | `Infrastructure/Queue/GenerateCampaignJob.php` |
| Thresholds | `Domain/Services/CampaignQualityEvaluator.php` |
| Wizard UI | `resources/js/pages/campaigns/Create.vue` |
| Review UI | `resources/js/pages/campaigns/components/CampaignForm.vue` |

---

## Related docs

- Organic short-form + SAAEEF / CapCut narrative: `prompt-contenido-social-argenis-TOFU-BOFU-MOFU.md` (Social Media inspiration; durations updated to stage-aware 15–30s in code).
- Prototype JSX (`campaigns-creator-v2.jsx`) is a **design sketch** of an older video-ZIP concept — **not** the live Campaigns API shape.
