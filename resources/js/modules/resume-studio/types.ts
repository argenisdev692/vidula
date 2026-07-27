/**
 * AiResumeStudio module — snake_case interfaces mirroring backend Eloquent projections.
 */

import type { PaginatedResponse } from '@/modules/company/types';

export type { PaginatedResponse };

export type StudioMode = 'career' | 'other';

export type StudioRunStatus = 'pending' | 'running' | 'completed' | 'failed';

export type StudioRunStep =
    | 'queued'
    | 'enriching'
    | 'refining'
    | 'searching'
    | 'scoring'
    | 'drafting'
    | 'completed'
    | 'failed';

export type StudioSoftStatus = 'active' | 'suspended';

export type AiProvider = 'openai' | 'anthropic' | 'gemini';

export type LocationScope =
    | 'worldwide'
    | 'remote'
    | 'schengen'
    | 'united_states'
    | 'united_kingdom'
    | 'latin_america'
    | 'europe'
    | 'north_america'
    | 'south_america'
    | 'africa'
    | 'asia'
    | 'oceania'
    | 'portugal'
    | 'spain'
    | 'germany'
    | 'france'
    | 'netherlands'
    | 'ireland'
    | 'canada'
    | 'mexico'
    | 'brazil'
    | 'argentina'
    | 'colombia'
    | 'chile';

export type SearchLanguage = 'es' | 'en' | 'both';

/** Language of the refined CV Markdown / PDF export. */
export type ResumeLanguage = 'en' | 'es' | 'pt-PT';

export type ApplicationStatus = 'new' | 'saved' | 'applied' | 'skipped' | 'dismissed';

export type JobMatchSource = 'tavily' | 'firecrawl';

export type OutreachKind = 'cover' | 'digest';

export type OutreachStatus = 'draft' | 'edited' | 'sent_manually' | 'sent_automated' | 'discarded';

export type JobSearchConfigStatus = 'active' | 'paused';

/** Lean CV option for the start-studio dialog (GET /resume-studio `cvs` prop). */
export interface CvOption {
    uuid: string;
    title: string;
    niche: 'fullstack' | 'other';
    is_primary: boolean;
}

/** Nested CV projection on a studio run row or detail. */
export interface StudioRunCv {
    uuid: string;
    title: string;
    niche: 'fullstack' | 'other';
    raw_text?: string | null;
}

export interface JobSearchConfig {
    uuid: string;
    keywords: string | null;
    location_scope?: LocationScope | null;
    search_language?: SearchLanguage | null;
    resume_language?: ResumeLanguage | null;
    targeting_prompt?: string | null;
    schedule_enabled?: boolean;
    deep_extract_enabled?: boolean;
    auto_send_enabled?: boolean;
    provider?: AiProvider | null;
    mode?: StudioMode;
    status?: JobSearchConfigStatus;
    created_at?: string | null;
    deleted_at?: string | null;
}

export interface RefinedCvFeedback {
    strengths?: string[];
    improvements?: string[];
    keyword_gaps?: string[];
    weak_lines?: string[];
}

export interface RefinedCv {
    uuid: string;
    studio_run_id?: number;
    ats_score: number | null;
    target_job_title: string | null;
    resume_language?: ResumeLanguage | null;
    refined_md: string | null;
    feedback: RefinedCvFeedback | null;
    version: number;
    provider?: AiProvider | null;
    created_at: string | null;
}

export interface JobMatch {
    uuid: string;
    studio_run_id?: number;
    job_title: string | null;
    company_name: string | null;
    job_url: string | null;
    match_score: number | null;
    match_reasoning?: string | null;
    application_status: ApplicationStatus;
    source?: JobMatchSource | null;
    created_at: string | null;
    deleted_at: string | null;
}

export interface OutreachDraft {
    uuid: string;
    studio_run_id?: number;
    kind: OutreachKind;
    subject: string | null;
    body: string | null;
    language?: string | null;
    status: OutreachStatus;
    created_at: string | null;
}

export interface StudioRun {
    uuid: string;
    mode: StudioMode;
    step: StudioRunStep;
    status: StudioRunStatus;
    error_summary: string | null;
    started_at: string | null;
    finished_at: string | null;
    created_at: string | null;
    deleted_at: string | null;
    cv?: StudioRunCv | null;
    job_search_config?: JobSearchConfig | null;
    refined_cvs?: RefinedCv[];
    job_matches?: JobMatch[];
    outreach_drafts?: OutreachDraft[];
}

/** Filters echoed back by the server (mirrors StudioFilterData). */
export interface StudioFilters {
    search: string | null;
    status: StudioSoftStatus | null;
    mode: StudioMode | null;
    date_from: string | null;
    date_to: string | null;
    run_uuid?: string | null;
}

/** Reactive query state driving the server-side DataTable. */
export interface StudioQuery extends StudioFilters {
    page: number;
    per_page: number;
}

export interface GithubRepo {
    name: string;
    description: string | null;
    url: string;
    stars: number;
    language: string | null;
}
