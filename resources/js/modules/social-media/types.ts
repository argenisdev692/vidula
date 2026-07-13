/**
 * Social Media module — snake_case interfaces mirroring the backend
 * {@link \Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\SocialMediaContentEloquentModel}
 * projection. Content is always AI-born (no manual create form): `status`
 * folds the generation lifecycle (draft/generating/ready/needs_review/
 * published/scheduled) AND the soft-delete state (suspended) into one filter
 * axis, matching the single status <select> — same shape as PostFilters.
 */

import type { PaginatedResponse } from '@/modules/company/types';

export type { PaginatedResponse };

export type AiProvider = 'openai' | 'anthropic' | 'gemini';
export type ContentLanguage = 'es' | 'en';
export type BusinessGoal = 'awareness' | 'engagement' | 'viral' | 'leads' | 'sales' | 'community';
export type BrandVoice = 'professional' | 'conversational' | 'trendy' | 'inspirational' | 'humorous';
export type FunnelStage = 'tofu' | 'mofu' | 'bofu';

/** Lifecycle values the reviewer may set from the Edit form (never 'generating' — job-owned). */
export type SocialMediaContentStatus = 'draft' | 'ready' | 'needs_review' | 'published' | 'scheduled';

/** Every value the backend `status` column can hold (adds the job-owned transient state). */
export type SocialMediaContentRowStatus = SocialMediaContentStatus | 'generating';

/** Every value the `status` filter/list column can show (adds the soft-delete state). */
export type SocialMediaContentListStatus = SocialMediaContentRowStatus | 'suspended';

export interface SocialMediaContentCreator {
    id: number;
    first_name: string | null;
    last_name: string | null;
}

/** A row in the social media DataTable (lean list projection — see EloquentSocialMediaContentRepository::paginate()). */
export interface SocialMediaContent {
    uuid: string;
    topic: string;
    status: SocialMediaContentRowStatus;
    business_goal: BusinessGoal;
    funnel_stage: FunnelStage;
    language: ContentLanguage;
    provider: AiProvider;
    overall_score_avg: number | null;
    all_scores_pass: boolean;
    quality_warning: boolean;
    scheduled_at: string | null;
    published_at: string | null;
    creator?: SocialMediaContentCreator | null;
    created_at: string | null;
    deleted_at: string | null;
}

/** One of the five quality-loop scores (mirrors ScoreResultData). */
export interface ScoreResult {
    value: number;
    threshold: number;
    passes: boolean;
    factors: Record<string, number>;
    explanation: string;
}

/** The five quality-loop scores for one generation attempt (mirrors ScoreSetData). */
export interface ScoreSet {
    human_writing_index: ScoreResult;
    virality_score: ScoreResult;
    engagement_score: ScoreResult;
    roi_score: ScoreResult;
    trend_alignment: ScoreResult;
    all_scores_pass: boolean;
    overall_average: number;
}

/** One platform's adapted copy + assets (mirrors PlatformContentData). */
export interface PlatformContent {
    platform: string;
    adapted_content: string;
    character_count: number;
    hashtags: string[];
    image_prompt: string;
    is_thread: boolean;
    thread_tweets: string[];
    video_script: string | null;
    image_path: string | null;
    image_url: string | null;
    voiceover_audio_path: string | null;
    voiceover_audio_url: string | null;
}

export interface EeatAnalysis {
    experience_signals: string[];
    expertise_signals: string[];
    authoritativeness_signals: string[];
    trustworthiness_signals: string[];
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
export interface SocialMediaContentDetail extends SocialMediaContent {
    niche: string | null;
    angle: string | null;
    hook: string | null;
    key_trend: string | null;
    audience: string | null;
    headline: string | null;
    body: string | null;
    call_to_action: string | null;
    hashtags: string[] | null;
    platforms: Record<string, PlatformContent> | null;
    cover_image_path: string | null;
    cover_image_url: string | null;
    cover_image_prompt: string | null;
    scores: ScoreSet | null;
    human_writing_index: number | null;
    virality_score: number | null;
    engagement_score: number | null;
    roi_score: number | null;
    trend_alignment: number | null;
    iterations_required: number | null;
    quality_warning_message: string | null;
    eeat_analysis: EeatAnalysis | null;
    optimization_suggestions: string[] | null;
    research_sources: ResearchSource[] | null;
    tavily_data_used: string[] | null;
    ai_detection_risk: AiDetectionRisk | null;
}

/** Filters echoed back by the server (mirrors SocialMediaContentFilterData). */
export interface SocialMediaContentFilters {
    search: string | null;
    status: SocialMediaContentListStatus | null;
    date_from: string | null;
    date_to: string | null;
}

/** The full reactive query state driving the server-side DataTable. */
export interface SocialMediaContentQuery extends SocialMediaContentFilters {
    page: number;
    per_page: number;
}

/* ── AI wizard contracts (mirror SocialMediaTopicIdeaData / GenerateSocialMediaContentData) ── */

/** One of the exactly-10 candidate topics returned by Step 1 (suggest-topics). */
export interface SocialMediaTopicIdea {
    title: string;
    angle: string;
    hook: string;
    platform: string;
    estimated_virality: number;
    estimated_engagement: string;
    estimated_roi: number;
    difficulty: string;
    why_it_works: string;
    key_trend: string;
    suggested_format: string;
    content_type: string;
    funnel_stage: FunnelStage;
}

/** Step 2 payload — POST /social-media/ai/generate-content. */
export interface GenerateSocialMediaContentPayload {
    topic: string;
    provider: AiProvider;
    language: ContentLanguage;
    business_goal: BusinessGoal;
    brand_voice: BrandVoice;
    funnel_stage: FunnelStage;
    angle?: string | null;
    hook?: string | null;
    key_trend?: string | null;
    niche?: string | null;
    audience?: string | null;
    generate_images: boolean;
    generate_voiceover: boolean;
}

/** Real-time tick broadcast on the user's private channel (`social-media.ai.progress`). */
export interface SocialMediaAiProgressEvent {
    content_uuid: string;
    stage: string;
    message: string;
    progress: number;
    iteration: number;
}
