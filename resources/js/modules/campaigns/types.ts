/**
 * Campaigns module — snake_case interfaces mirroring the backend
 * {@link \Modules\Campaigns\Infrastructure\Persistence\Eloquent\Models\CampaignEloquentModel}
 * projection. A campaign is always AI-born (no manual create form): `status`
 * folds the generation lifecycle (draft/generating/ready/needs_review/
 * published/scheduled) AND the soft-delete state (suspended) into one filter
 * axis — same shape as SocialMediaContentFilters/PostFilters.
 */

import type { PaginatedResponse } from '@/modules/company/types';

export type { PaginatedResponse };

export type AiProvider = 'openai' | 'anthropic' | 'gemini';
export type CampaignLanguage = 'es' | 'en';

/** Campaigns is a paid Meta Ads module — acquisition/retention goals only (no 'viral'/'community'). */
export type BusinessGoal = 'awareness' | 'engagement' | 'leads' | 'sales' | 'retention';
export type BrandVoice = 'professional' | 'conversational' | 'trendy' | 'inspirational' | 'humorous';

/** TOFU/MOFU/BOFU/LOYALTY — Loyalty is the 4th stage (retention/retargeting) Meta Ads budgets separately. */
export type FunnelStage = 'tofu' | 'mofu' | 'bofu' | 'loyalty';

/** Meta Ads surface. 'both' generates adapted copy for Facebook AND Instagram in one attempt. */
export type CampaignPlatform = 'facebook' | 'instagram' | 'both';

/** Meta Ads placement/format — 'lead_form' targets native Instant Forms (highest-intent lead-gen unit). */
export type AdFormat = 'feed' | 'story' | 'reel' | 'carousel' | 'lead_form';

/** Lifecycle values the reviewer may set from the Edit form (never 'generating' — job-owned). */
export type CampaignStatus = 'draft' | 'ready' | 'needs_review' | 'published' | 'scheduled';

/** Every value the backend `status` column can hold (adds the job-owned transient state). */
export type CampaignRowStatus = CampaignStatus | 'generating';

/** Every value the `status` filter/list column can show (adds the soft-delete state). */
export type CampaignListStatus = CampaignRowStatus | 'suspended';

export interface CampaignCreator {
    id: number;
    first_name: string | null;
    last_name: string | null;
}

/** A row in the campaigns DataTable (lean list projection — see EloquentCampaignRepository::paginate()). */
export interface Campaign {
    uuid: string;
    topic: string;
    status: CampaignRowStatus;
    business_goal: BusinessGoal;
    funnel_stage: FunnelStage;
    platform: CampaignPlatform;
    ad_format: AdFormat;
    language: CampaignLanguage;
    provider: AiProvider;
    overall_score_avg: number | null;
    success_probability_label: string | null;
    all_scores_pass: boolean;
    quality_warning: boolean;
    scheduled_at: string | null;
    published_at: string | null;
    creator?: CampaignCreator | null;
    created_at: string | null;
    deleted_at: string | null;
}

/** One of the five quality-loop scores (mirrors CampaignScoreResultData). */
export interface ScoreResult {
    value: number;
    threshold: number;
    passes: boolean;
    factors: Record<string, number>;
    explanation: string;
}

/** The five quality-loop scores for one generation attempt (mirrors CampaignScoreSetData). */
export interface ScoreSet {
    audience_fit_score: ScoreResult;
    virality_score: ScoreResult;
    roi_potential_score: ScoreResult;
    lead_quality_score: ScoreResult;
    trend_relevance_score: ScoreResult;
    all_scores_pass: boolean;
    overall_average: number;
}

/** One Meta surface's (Facebook or Instagram) adapted ad copy + cover image (mirrors PlatformCampaignContentData). */
export interface PlatformContent {
    platform: string;
    adapted_primary_text: string;
    character_count: number;
    headline: string;
    description: string | null;
    hashtags: string[];
    image_prompt: string;
    image_path: string | null;
    image_url: string | null;
}

export interface ResearchSource {
    source: string;
    relevance: string;
    key_insight: string;
    used_in: string[];
}

export interface AiDetectionRisk {
    value: number;
    label: string;
    explanation: string;
}

/** Full detail loaded on the Edit page — adds every AI-generated field. */
export interface CampaignDetail extends Campaign {
    niche: string | null;
    angle: string | null;
    hook: string | null;
    key_trend: string | null;
    audience: string | null;
    headline: string | null;
    primary_text: string | null;
    description: string | null;
    call_to_action: string | null;
    hashtags: string[] | null;
    lead_form_questions: string[] | null;
    targeting_suggestions: string[] | null;
    platforms: Record<string, PlatformContent> | null;
    cover_image_path: string | null;
    cover_image_url: string | null;
    cover_image_prompt: string | null;
    scores: ScoreSet | null;
    audience_fit_score: number | null;
    virality_score: number | null;
    roi_potential_score: number | null;
    lead_quality_score: number | null;
    trend_relevance_score: number | null;
    iterations_required: number | null;
    quality_warning_message: string | null;
    optimization_suggestions: string[] | null;
    research_sources: ResearchSource[] | null;
    tavily_data_used: string[] | null;
    ai_detection_risk: AiDetectionRisk | null;
}

/** Filters echoed back by the server (mirrors CampaignFilterData). */
export interface CampaignFilters {
    search: string | null;
    status: CampaignListStatus | null;
    date_from: string | null;
    date_to: string | null;
}

/** The full reactive query state driving the server-side DataTable. */
export interface CampaignQuery extends CampaignFilters {
    page: number;
    per_page: number;
}

/* ── AI wizard contracts (mirror CampaignTopicIdeaData / GenerateCampaignData) ── */

/** One of the exactly-10 candidate Meta Ads angles returned by Step 1 (suggest-topics). */
export interface CampaignTopicIdea {
    title: string;
    angle: string;
    hook: string;
    platform: string;
    estimated_virality: number;
    estimated_engagement: string;
    estimated_roi: number;
    estimated_lead_potential: number;
    difficulty: string;
    why_it_works: string;
    key_trend: string;
    suggested_format: string;
    content_type: string;
    funnel_stage: FunnelStage;
}

/** Step 2 payload — POST /campaigns/ai/generate-campaign. */
export interface GenerateCampaignPayload {
    topic: string;
    provider: AiProvider;
    language: CampaignLanguage;
    business_goal: BusinessGoal;
    brand_voice: BrandVoice;
    funnel_stage: FunnelStage;
    platform: CampaignPlatform;
    ad_format: AdFormat;
    angle?: string | null;
    hook?: string | null;
    key_trend?: string | null;
    niche?: string | null;
    audience?: string | null;
    generate_images: boolean;
}

/** Real-time tick broadcast on the user's private channel (`campaigns.ai.progress`). */
export interface CampaignAiProgressEvent {
    campaign_uuid: string;
    stage: string;
    message: string;
    progress: number;
    iteration: number;
}
