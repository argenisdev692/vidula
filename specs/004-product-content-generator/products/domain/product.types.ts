export type ProductType = 'classroom' | 'video_tutorial' | 'video_pill';
export type ProductStatus = 'draft' | 'published' | 'archived';
export type ProductLifecycleStatus = 'active' | 'suspended';
export type ProductModality = 'online' | 'presential' | 'hybrid';
export type ScriptStatus =
  | 'draft'
  | 'generated'
  | 'verified'
  | 'needs_review'
  | 'recorded';
export type ContentGenerationStatus =
  | 'pending'
  | 'parsing'
  | 'generating'
  | 'verifying'
  | 'rendering'
  | 'packaging'
  | 'completed'
  | 'failed';
export type MaterialType = 'pdf' | 'markdown' | 'link';
export type VideoPlatform = 'youtube' | 'vimeo' | 'local' | 'other';

export const GENERATABLE_PRODUCT_TYPES: readonly ProductType[] = [
  'classroom',
  'video_tutorial',
  'video_pill',
] as const;

export const NON_TERMINAL_GENERATION_STATUSES: readonly ContentGenerationStatus[] =
  [
    'pending',
    'parsing',
    'generating',
    'verifying',
    'rendering',
    'packaging',
  ] as const;

export interface ClassroomDetail {
  id: string;
  maxStudents: number | null;
  meetUrl: string | null;
  objectives: string | null;
  requirements: string | null;
}

export interface VideoCourseDetail {
  id: string;
  platform: VideoPlatform | null;
  playlistUrl: string | null;
  totalVideos: number;
  totalDurationMinutes: number | null;
  targetAudience: string | null;
}

export interface Product {
  id: string;
  userId: string;
  clientId: string | null;
  type: ProductType;
  title: string;
  slug: string;
  description: string | null;
  price: string;
  currency: string;
  /** Catalog lifecycle: draft | published | archived */
  status: ProductStatus;
  /**
   * Soft-delete visibility badge. Named separately from catalog `status`
   * (domain collision — see trashed.util statusFlagShape rules).
   */
  lifecycleStatus: ProductLifecycleStatus;
  thumbnail: string | null;
  level: string;
  language: string;
  startDate: string | null;
  endDate: string | null;
  totalHours: string | null;
  totalSessions: number | null;
  modality: ProductModality | null;
  notes: string | null;
  createdAt: string;
  updatedAt: string;
  deletedAt: string | null;
  classroom?: ClassroomDetail | null;
  videoCourse?: VideoCourseDetail | null;
}

export interface ContentGeneration {
  id: string;
  productId: string;
  userId: string;
  status: ContentGenerationStatus;
  mode: string;
  sourceMarkdown: string;
  model: string | null;
  progress: number;
  sessionsCount: number;
  topicsCount: number;
  scriptsCount: number;
  pdfPath: string | null;
  mdPath: string | null;
  zipPath: string | null;
  error: string | null;
  startedAt: string | null;
  completedAt: string | null;
  createdAt: string;
  updatedAt: string;
}
