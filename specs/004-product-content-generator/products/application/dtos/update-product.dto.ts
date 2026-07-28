import { z } from 'zod';
import { createZodDto } from 'nestjs-zod';

const ClassroomDetailSchema = z
  .object({
    maxStudents: z.number().int().positive().nullable().optional(),
    meetUrl: z.string().url().max(2048).nullable().optional(),
    objectives: z.string().max(20_000).nullable().optional(),
    requirements: z.string().max(20_000).nullable().optional(),
  })
  .optional();

const VideoCourseDetailSchema = z
  .object({
    platform: z
      .enum(['youtube', 'vimeo', 'local', 'other'])
      .nullable()
      .optional(),
    playlistUrl: z.string().url().max(2048).nullable().optional(),
    totalVideos: z.number().int().min(0).optional(),
    totalDurationMinutes: z.number().int().min(0).nullable().optional(),
    targetAudience: z.string().max(20_000).nullable().optional(),
  })
  .optional();

export const UpdateProductSchema = z
  .object({
    title: z.string().min(2).max(255).optional(),
    slug: z
      .string()
      .min(2)
      .max(255)
      .regex(/^[a-z0-9]+(?:-[a-z0-9]+)*$/)
      .optional(),
    description: z.string().max(50_000).nullable().optional(),
    clientId: z.string().uuid().nullable().optional(),
    price: z
      .string()
      .regex(/^\d+(\.\d{1,2})?$/)
      .optional(),
    currency: z.string().length(3).optional(),
    status: z.enum(['draft', 'published', 'archived']).optional(),
    thumbnail: z.string().url().max(2048).nullable().optional(),
    level: z.string().min(1).max(50).optional(),
    language: z.string().min(2).max(10).optional(),
    startDate: z.string().date().nullable().optional(),
    endDate: z.string().date().nullable().optional(),
    totalHours: z
      .string()
      .regex(/^\d+(\.\d{1,2})?$/)
      .nullable()
      .optional(),
    totalSessions: z.number().int().min(0).nullable().optional(),
    modality: z.enum(['online', 'presential', 'hybrid']).nullable().optional(),
    notes: z.string().max(20_000).nullable().optional(),
    classroom: ClassroomDetailSchema,
    videoCourse: VideoCourseDetailSchema,
  })
  .refine((obj) => Object.keys(obj).length > 0, {
    message: 'At least one field is required',
  });

export class UpdateProductDto extends createZodDto(UpdateProductSchema) {}
