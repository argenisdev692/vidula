import type {
  Product as PrismaProduct,
  Classroom as PrismaClassroom,
  VideoCourse as PrismaVideoCourse,
  ContentGeneration as PrismaContentGeneration,
  ProductType as PrismaProductType,
  ProductStatus as PrismaProductStatus,
  ProductModality as PrismaProductModality,
  VideoPlatform as PrismaVideoPlatform,
  ContentGenerationStatus as PrismaGenerationStatus,
  Prisma,
} from '../../../../../generated/prisma/client';
import { entityStatus } from '../../../../../shared/crud/trashed.util';
import type {
  ContentGeneration,
  Product,
  ProductModality,
  ProductStatus,
  ProductType,
  VideoPlatform,
  ContentGenerationStatus,
} from '../../../domain/product.types';

type ProductWithDetails = PrismaProduct & {
  classroom?: PrismaClassroom | null;
  videoCourse?: PrismaVideoCourse | null;
};

/** Scalar + detail columns for HTTP list/export (Rule 3 — explicit select). */
export const PRODUCT_HTTP_SELECT = {
  id: true,
  userId: true,
  clientId: true,
  type: true,
  title: true,
  slug: true,
  description: true,
  price: true,
  currency: true,
  status: true,
  thumbnail: true,
  level: true,
  language: true,
  startDate: true,
  endDate: true,
  totalHours: true,
  totalSessions: true,
  modality: true,
  notes: true,
  createdAt: true,
  updatedAt: true,
  deletedAt: true,
  classroom: true,
  videoCourse: true,
} as const satisfies Prisma.ProductSelect;

function decimalToString(value: { toString(): string } | null | undefined): string | null {
  if (value == null) return null;
  return value.toString();
}

function dateOnlyToString(value: Date | null | undefined): string | null {
  if (!value) return null;
  return value.toISOString().slice(0, 10);
}

export function mapProductType(t: PrismaProductType): ProductType {
  return t as ProductType;
}

export function mapProductStatus(s: PrismaProductStatus): ProductStatus {
  return s as ProductStatus;
}

export function mapModality(
  m: PrismaProductModality | null,
): ProductModality | null {
  return m as ProductModality | null;
}

export function mapVideoPlatform(
  p: PrismaVideoPlatform | null,
): VideoPlatform | null {
  return p as VideoPlatform | null;
}

export function mapGenerationStatus(
  s: PrismaGenerationStatus,
): ContentGenerationStatus {
  return s as ContentGenerationStatus;
}

export function mapProduct(row: ProductWithDetails): Product {
  return {
    id: row.id,
    userId: row.userId,
    clientId: row.clientId,
    type: mapProductType(row.type),
    title: row.title,
    slug: row.slug,
    description: row.description,
    price: decimalToString(row.price) ?? '0',
    currency: row.currency,
    status: mapProductStatus(row.status),
    lifecycleStatus: entityStatus(row.deletedAt),
    thumbnail: row.thumbnail,
    level: row.level,
    language: row.language,
    startDate: dateOnlyToString(row.startDate),
    endDate: dateOnlyToString(row.endDate),
    totalHours: decimalToString(row.totalHours),
    totalSessions: row.totalSessions,
    modality: mapModality(row.modality),
    notes: row.notes,
    createdAt: row.createdAt.toISOString(),
    updatedAt: row.updatedAt.toISOString(),
    deletedAt: row.deletedAt?.toISOString() ?? null,
    classroom: row.classroom
      ? {
          id: row.classroom.id,
          maxStudents: row.classroom.maxStudents,
          meetUrl: row.classroom.meetUrl,
          objectives: row.classroom.objectives,
          requirements: row.classroom.requirements,
        }
      : row.classroom === null
        ? null
        : undefined,
    videoCourse: row.videoCourse
      ? {
          id: row.videoCourse.id,
          platform: mapVideoPlatform(row.videoCourse.platform),
          playlistUrl: row.videoCourse.playlistUrl,
          totalVideos: row.videoCourse.totalVideos,
          totalDurationMinutes: row.videoCourse.totalDurationMinutes,
          targetAudience: row.videoCourse.targetAudience,
        }
      : row.videoCourse === null
        ? null
        : undefined,
  };
}

export function mapContentGeneration(
  row: PrismaContentGeneration,
): ContentGeneration {
  return {
    id: row.id,
    productId: row.productId,
    userId: row.userId,
    status: mapGenerationStatus(row.status),
    mode: row.mode,
    sourceMarkdown: row.sourceMarkdown,
    model: row.model,
    progress: row.progress,
    sessionsCount: row.sessionsCount,
    topicsCount: row.topicsCount,
    scriptsCount: row.scriptsCount,
    pdfPath: row.pdfPath,
    mdPath: row.mdPath,
    zipPath: row.zipPath,
    error: row.error,
    startedAt: row.startedAt?.toISOString() ?? null,
    completedAt: row.completedAt?.toISOString() ?? null,
    createdAt: row.createdAt.toISOString(),
    updatedAt: row.updatedAt.toISOString(),
  };
}
