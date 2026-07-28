import { z } from 'zod';
import { createZodDto } from 'nestjs-zod';

const ClassroomDetailSchema = z.object({
  id: z.string().uuid(),
  maxStudents: z.number().int().nullable(),
  meetUrl: z.string().nullable(),
  objectives: z.string().nullable(),
  requirements: z.string().nullable(),
});

const VideoCourseDetailSchema = z.object({
  id: z.string().uuid(),
  platform: z.string().nullable(),
  playlistUrl: z.string().nullable(),
  totalVideos: z.number().int(),
  totalDurationMinutes: z.number().int().nullable(),
  targetAudience: z.string().nullable(),
});

export const ProductResponseSchema = z.object({
  id: z.string().uuid(),
  userId: z.string().uuid(),
  clientId: z.string().uuid().nullable(),
  type: z.enum(['classroom', 'video_tutorial', 'video_pill']),
  title: z.string(),
  slug: z.string(),
  description: z.string().nullable(),
  price: z.string(),
  currency: z.string(),
  status: z.enum(['draft', 'published', 'archived']),
  lifecycleStatus: z.enum(['active', 'suspended']),
  thumbnail: z.string().nullable(),
  level: z.string(),
  language: z.string(),
  startDate: z.string().nullable(),
  endDate: z.string().nullable(),
  totalHours: z.string().nullable(),
  totalSessions: z.number().int().nullable(),
  modality: z.enum(['online', 'presential', 'hybrid']).nullable(),
  notes: z.string().nullable(),
  createdAt: z.string().datetime(),
  updatedAt: z.string().datetime(),
  deletedAt: z.string().datetime().nullable(),
  classroom: ClassroomDetailSchema.nullable().optional(),
  videoCourse: VideoCourseDetailSchema.nullable().optional(),
});

export class ProductResponse extends createZodDto(ProductResponseSchema) {}

export const ContentGenerationStatusResponseSchema = z.object({
  id: z.string().uuid(),
  productId: z.string().uuid(),
  status: z.string(),
  mode: z.string(),
  progress: z.number().int(),
  sessionsCount: z.number().int(),
  topicsCount: z.number().int(),
  scriptsCount: z.number().int(),
  error: z.string().nullable(),
  startedAt: z.string().datetime().nullable(),
  completedAt: z.string().datetime().nullable(),
  createdAt: z.string().datetime(),
  updatedAt: z.string().datetime(),
});

export class ContentGenerationStatusResponse extends createZodDto(
  ContentGenerationStatusResponseSchema,
) {}
