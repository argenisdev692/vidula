/**
 * Products module — snake_case interfaces mirroring ProductEloquentModel
 * list/detail projections (+ owner, client and 1:1 detail relations).
 */

import type { PaginatedResponse } from '@/modules/company/types';

export type { PaginatedResponse };

/** Lean owner projection loaded alongside each row. */
export interface ProductOwner {
    id: number;
    first_name: string | null;
    last_name: string | null;
}

/** Lean billing-party projection loaded alongside each row. */
export interface ProductClient {
    uuid: string;
    client_name: string;
}

/** Catalog kind — drives which 1:1 detail and which generation shape apply. */
export type ProductType = 'classroom' | 'video_tutorial' | 'video_pill';

/** Catalog lifecycle (distinct from soft-delete Active/Suspended). */
export type ProductLifecycleStatus = 'draft' | 'published' | 'archived';

/** Soft-delete list filter. */
export type ProductSoftStatus = 'active' | 'suspended';

export type ProductModality = 'online' | 'presential' | 'hybrid';

/** Lean client option for the create/edit dialog. */
export interface ProductClientOption {
    uuid: string;
    client_name: string;
}

/** Generation status chip / progress poll payload (no seed markdown). */
export type GenerationStatusValue =
    | 'pending'
    | 'parsing'
    | 'generating'
    | 'verifying'
    | 'rendering'
    | 'packaging'
    | 'completed'
    | 'failed';

export interface ProductGenerationStatus {
    uuid: string;
    status: GenerationStatusValue | string;
    mode: string;
    progress: number;
    sessions_count: number;
    topics_count: number;
    scripts_count: number;
    error: string | null;
    started_at: string | null;
    completed_at: string | null;
    has_package: boolean;
}

/** Slim topic row on Show (script body loaded on demand). */
export interface ProductTopicSummary {
    uuid: string;
    title: string;
    sort_order: number;
    status: string | null;
    estimated_minutes: number | null;
}

export interface ProductSessionSummary {
    session_number: number;
    title: string;
    topics: ProductTopicSummary[];
}

/** Real-time tick from `products.generation.progress`. */
export interface ProductGenerationProgressEvent {
    product_uuid: string;
    generation_uuid: string;
    stage: string;
    message: string;
    progress: number;
}

/** Thin classroom detail, present only on `classroom` products. */
export interface ClassroomDetail {
    uuid: string;
    max_students: number | null;
    meet_url: string | null;
    objectives: string | null;
    requirements: string | null;
}

/** Thin video detail, present only on `video_tutorial` / `video_pill`. */
export interface VideoCourseDetail {
    uuid: string;
    platform: string | null;
    playlist_url: string | null;
    total_videos: number;
    total_duration_minutes: number | null;
    target_audience: string | null;
}

/** A row in the products DataTable. */
export interface Product {
    uuid: string;
    type: ProductType;
    title: string;
    slug: string;
    description: string | null;
    price: string | number;
    currency: string;
    status: ProductLifecycleStatus;
    thumbnail: string | null;
    level: string;
    language: string;
    start_date: string | null;
    end_date: string | null;
    total_hours: string | number | null;
    total_sessions: number | null;
    modality: ProductModality | null;
    notes: string | null;
    user_id: number;
    user?: ProductOwner | null;
    client?: ProductClient | null;
    classroom?: ClassroomDetail | null;
    video_course?: VideoCourseDetail | null;
    sessions_count?: number;
    materials_count?: number;
    content_generations_count?: number;
    created_at: string | null;
    updated_at?: string | null;
    deleted_at: string | null;
}

/** Filters echoed back by the server (mirrors ProductFilterData). */
export interface ProductFilters {
    search: string | null;
    status: ProductSoftStatus | null;
    product_status: ProductLifecycleStatus | null;
    type: ProductType | null;
    client_uuid: string | null;
    date_from: string | null;
    date_to: string | null;
}

/** Reactive query state driving the server-side DataTable. */
export interface ProductQuery extends ProductFilters {
    page: number;
    per_page: number;
}
