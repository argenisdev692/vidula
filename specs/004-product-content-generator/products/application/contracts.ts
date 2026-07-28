/**
 * Frontend-safe contracts — ZERO NestJS / Prisma imports.
 * Domain read-models and shared unions only.
 */
export type {
  Product,
  ProductType,
  ProductStatus,
  ProductLifecycleStatus,
  ProductModality,
  ContentGeneration,
  ContentGenerationStatus,
  VideoPlatform,
  ClassroomDetail,
  VideoCourseDetail,
} from '../domain/product.types';

export type GenerateContentResponse = {
  generationId: string;
  status: string;
};

export type BulkCountResponse = {
  count: number;
};
