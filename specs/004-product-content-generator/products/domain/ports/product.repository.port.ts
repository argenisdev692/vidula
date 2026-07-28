import type { TrashedMode } from '../../../../shared/crud/trashed.util';
import type { DateRange } from '../../../../shared/crud/date-range.util';
import type {
  ContentGeneration,
  ContentGenerationStatus,
  Product,
  ProductModality,
  ProductStatus,
  ProductType,
  VideoPlatform,
} from '../product.types';

export const PRODUCT_REPOSITORY = Symbol('PRODUCT_REPOSITORY');

export interface CreateProductData {
  userId: string;
  clientId?: string | null;
  type: ProductType;
  title: string;
  slug: string;
  description?: string | null;
  price: string;
  currency: string;
  status: ProductStatus;
  thumbnail?: string | null;
  level: string;
  language: string;
  startDate?: string | null;
  endDate?: string | null;
  totalHours?: string | null;
  totalSessions?: number | null;
  modality?: ProductModality | null;
  notes?: string | null;
  classroom?: {
    maxStudents?: number | null;
    meetUrl?: string | null;
    objectives?: string | null;
    requirements?: string | null;
  } | null;
  videoCourse?: {
    platform?: VideoPlatform | null;
    playlistUrl?: string | null;
    totalVideos?: number;
    totalDurationMinutes?: number | null;
    targetAudience?: string | null;
  } | null;
}

export interface UpdateProductData {
  clientId?: string | null;
  title?: string;
  slug?: string;
  description?: string | null;
  price?: string;
  currency?: string;
  status?: ProductStatus;
  thumbnail?: string | null;
  level?: string;
  language?: string;
  startDate?: string | null;
  endDate?: string | null;
  totalHours?: string | null;
  totalSessions?: number | null;
  modality?: ProductModality | null;
  notes?: string | null;
  classroom?: {
    maxStudents?: number | null;
    meetUrl?: string | null;
    objectives?: string | null;
    requirements?: string | null;
  } | null;
  videoCourse?: {
    platform?: VideoPlatform | null;
    playlistUrl?: string | null;
    totalVideos?: number;
    totalDurationMinutes?: number | null;
    targetAudience?: string | null;
  } | null;
}

export interface ListProductsOptions {
  search?: string;
  range?: DateRange;
  type?: ProductType;
  status?: ProductStatus;
  clientId?: string;
}

export interface CreateContentGenerationData {
  productId: string;
  userId: string;
  sourceMarkdown: string;
  mode: string;
  model?: string | null;
}

export interface UpdateContentGenerationData {
  status?: ContentGenerationStatus;
  progress?: number;
  sessionsCount?: number;
  topicsCount?: number;
  scriptsCount?: number;
  error?: string | null;
  startedAt?: Date | null;
  completedAt?: Date | null;
  model?: string | null;
}

export interface SeedTopicInput {
  title: string;
  sortOrder: number;
  description?: string | null;
}

export interface SeedSessionInput {
  sessionNumber: number;
  title: string;
  topics: SeedTopicInput[];
}

export interface IProductRepository {
  findAll(
    userId: string,
    limit: number,
    skip: number,
    trashed: TrashedMode,
    options?: ListProductsOptions,
  ): Promise<{ data: Product[]; total: number }>;

  findAllForExport(
    userId: string,
    trashed: TrashedMode,
    options?: ListProductsOptions,
  ): Promise<Product[]>;

  findById(
    id: string,
    options?: { withTrashed?: boolean; includeDetails?: boolean },
  ): Promise<Product | null>;

  findIdBySlug(slug: string): Promise<string | null>;

  create(data: CreateProductData): Promise<Product>;

  update(id: string, data: UpdateProductData): Promise<Product>;

  softDelete(id: string): Promise<void>;

  restore(id: string): Promise<Product>;

  bulkDelete(userId: string, ids: string[]): Promise<{ count: number }>;

  bulkRestore(userId: string, ids: string[]): Promise<{ count: number }>;

  findInFlightGeneration(
    productId: string,
  ): Promise<ContentGeneration | null>;

  createGeneration(data: CreateContentGenerationData): Promise<ContentGeneration>;

  findGenerationById(id: string): Promise<ContentGeneration | null>;

  updateGeneration(
    id: string,
    data: UpdateContentGenerationData,
  ): Promise<ContentGeneration>;

  /**
   * Replace mode: soft-delete existing sessions for the product, then create
   * the new session/topic/script tree from the seed outline.
   */
  replaceContentTree(
    productId: string,
    sessions: SeedSessionInput[],
  ): Promise<{ sessionsCount: number; topicsCount: number; scriptsCount: number }>;
}
