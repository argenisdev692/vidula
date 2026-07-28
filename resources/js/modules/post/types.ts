/**
 * Post module — snake_case interfaces mirroring the backend
 * {@link \Modules\Post\Infrastructure\Persistence\Eloquent\Models\PostEloquentModel}
 * projection. A list row is ACTIVE when `deleted_at === null`; `status` folds the
 * content lifecycle (draft/published/scheduled) AND the soft-delete state
 * (suspended) into one filter axis, matching the single status <select>.
 */

import type { PaginatedResponse } from '@/modules/company/types';

export type { PaginatedResponse };

export interface PostAuthor {
    id: number;
    first_name: string | null;
    last_name: string | null;
}

export interface PostCategory {
    uuid: string;
    blog_category_name: string | null;
}

export type PostStatus = 'draft' | 'published' | 'scheduled';
export type PostListStatus = PostStatus | 'suspended';
export type AiProvider = 'openai' | 'anthropic' | 'gemini';

/** A row in the posts DataTable (lean list projection). */
export interface Post {
    uuid: string;
    post_title: string;
    post_title_slug: string;
    post_excerpt: string | null;
    cover_image_url: string | null;
    category?: PostCategory | null;
    user?: PostAuthor | null;
    post_status: PostStatus;
    scheduled_at: string | null;
    published_at: string | null;
    is_ai_generated: boolean;
    seo_score: number | null;
    eeat_score: number | null;
    human_writing_index: number | null;
    created_at: string | null;
    deleted_at: string | null;
}

/** Full detail loaded on the Edit page — adds content + meta + AI fields. */
export interface PostDetail extends Post {
    post_content: string;
    meta_title: string | null;
    meta_description: string | null;
    meta_keywords: string | null;
    ai_provider: AiProvider | null;
    ai_detection_risk: number | null;
}

/** Filters echoed back by the server (mirrors PostFilterData). */
export interface PostFilters {
    search: string | null;
    status: PostListStatus | null;
    date_from: string | null;
    date_to: string | null;
    category_uuid: string | null;
    sort_field?: string | null;
    sort_order?: number | null;
}

/** The full reactive query state driving the server-side DataTable. */
export interface PostQuery extends PostFilters {
    sort_field: string;
    sort_order: 1 | -1;
    page: number;
    per_page: number;
}

export interface CategoryOption {
    value: string;
    label: string;
}

/* ── AI assist contracts (mirror PostTopicIdeaData / GeneratedPostContentData) ── */

export interface PostTopicIdea {
    title: string;
    angle: string;
    hook: string;
    estimated_virality: number;
    estimated_roi: number;
    eeat_potential: number;
    why_it_works: string;
    key_trend: string;
}

export interface PostImagePrompts {
    background: string;
    content: string;
}

export interface GeneratedPostContent {
    title: string;
    content: string;
    excerpt: string;
    meta_title: string;
    meta_description: string;
    meta_keywords: string;
    cover_image_path: string | null;
    cover_image_url: string | null;
    image_prompts: PostImagePrompts;
    provider: AiProvider;
    seo_score: number;
    eeat_score: number;
    virality_score: number;
    roi_score: number;
    human_writing_index: number;
    ai_detection_risk: number;
    all_scores_pass: boolean;
    iterations_required: number;
    quality_warning: boolean;
    quality_warning_message: string | null;
    optimization_suggestions: string[];
    seo_analysis: { primary_keyword: string; lsi_keywords: string[] };
}

/** LinkedIn + Instagram/Facebook copy for the chosen topic/angle. */
export interface SocialCopy {
    linkedin_post: string;
    social_caption: string;
    hashtags: string[];
}

/** One row of the Reel/TikTok timeline table. */
export interface ReelScene {
    time_range: string;
    action: string;
    on_screen_text: string;
    voiceover_line: string;
    visual_prompt: string;
}

/** Full Reel/TikTok package, including the AI voiceover when synthesis succeeded. */
export interface ReelPackage {
    scenes: ReelScene[];
    clean_script: string;
    sound_suggestion: string;
    tiktok_caption: string;
    tiktok_hashtags: string[];
    voiceover_audio_url: string | null;
}

/** One of the 4 independent AI-assist generation flows in the panel. */
export type PostAiProgressFlow = 'topics' | 'content' | 'social' | 'reel';

/** Real-time tick broadcast on the user's private channel (`post.ai.progress`). */
export interface PostAiProgressEvent {
    flow: PostAiProgressFlow;
    stage: string;
    message: string;
    progress: number;
}
