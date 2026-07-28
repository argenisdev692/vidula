import { z } from 'zod';
import { createZodDto } from 'nestjs-zod';
import { MAX_SOURCE_MARKDOWN_BYTES } from '../products-cache.constants';

export const GenerateContentSchema = z.object({
  markdown: z
    .string()
    .min(1)
    .max(MAX_SOURCE_MARKDOWN_BYTES)
    .refine((v) => Buffer.byteLength(v, 'utf8') <= MAX_SOURCE_MARKDOWN_BYTES, {
      message: `Markdown must be at most ${MAX_SOURCE_MARKDOWN_BYTES} bytes`,
    }),
  mode: z.enum(['replace']).default('replace'),
  forceReplace: z.boolean().default(false),
});

export class GenerateContentDto extends createZodDto(GenerateContentSchema) {}

export interface GenerateContentResponse {
  generationId: string;
  status: string;
}
