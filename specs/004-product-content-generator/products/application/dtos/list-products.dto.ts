import { z } from 'zod';
import { createZodDto } from 'nestjs-zod';
import {
  trashedFlagsShape,
  stringBoolean,
  rejectBothTrashedFlags,
  BOTH_TRASHED_FLAGS_ERROR,
} from '../../../../shared/crud/trashed.util';
import {
  dateRangeShape,
  rejectInvertedDateRange,
  INVERTED_DATE_RANGE_ERROR,
} from '../../../../shared/crud/date-range.util';

/**
 * Soft-delete uses `withTrashed` / `onlyTrashed` only — products have a domain
 * `status` column (draft/published/archived), so statusFlagShape is omitted.
 */
const productFilterShape = {
  search: z.string().max(255).optional(),
  type: z.enum(['classroom', 'video_tutorial', 'video_pill']).optional(),
  status: z.enum(['draft', 'published', 'archived']).optional(),
  clientId: z.string().uuid().optional(),
  ...trashedFlagsShape,
  ...dateRangeShape,
} as const;

export const ListProductsQuerySchema = z
  .object({
    limit: z.coerce.number().int().min(1).max(100).optional(),
    skip: z.coerce.number().int().min(0).optional(),
    ...productFilterShape,
  })
  .refine(rejectBothTrashedFlags, BOTH_TRASHED_FLAGS_ERROR)
  .refine(rejectInvertedDateRange, INVERTED_DATE_RANGE_ERROR);

export class ListProductsQueryDto extends createZodDto(ListProductsQuerySchema) {}

export const GetProductQuerySchema = z.object({
  withTrashed: stringBoolean.optional(),
});

export class GetProductQueryDto extends createZodDto(GetProductQuerySchema) {}

export const ExportProductsQuerySchema = z
  .object({
    format: z.enum(['csv', 'xlsx', 'pdf']),
    ...productFilterShape,
  })
  .refine(rejectBothTrashedFlags, BOTH_TRASHED_FLAGS_ERROR)
  .refine(rejectInvertedDateRange, INVERTED_DATE_RANGE_ERROR);

export class ExportProductsQueryDto extends createZodDto(
  ExportProductsQuerySchema,
) {}
