import { ProductContentProcessor } from '../../../infrastructure/jobs/product-content.processor';
import { SeedOutlineParser } from '../../../application/services/seed-outline.parser';
import type { ContentGeneration } from '../../../domain/product.types';

const logger = {
  info: jest.fn(),
  warn: jest.fn(),
  error: jest.fn(),
  debug: jest.fn(),
  setContext: jest.fn(),
};
const cls = { get: jest.fn().mockReturnValue('trace-test') };
const cache = { delByPattern: jest.fn().mockResolvedValue(undefined) };
const audit = { log: jest.fn().mockResolvedValue(undefined) };

const generation: ContentGeneration = {
  id: '018f0000-0000-7000-8000-000000000020',
  productId: '018f0000-0000-7000-8000-000000000010',
  userId: '018f0000-0000-7000-8000-000000000002',
  status: 'pending',
  mode: 'replace',
  sourceMarkdown: `
### Sesión 1 | Intro
- **Tema 1:** First topic
### Sesión 2 | Next
- **Tema 2:** Second topic
`,
  model: null,
  progress: 0,
  sessionsCount: 0,
  topicsCount: 0,
  scriptsCount: 0,
  pdfPath: null,
  mdPath: null,
  zipPath: null,
  error: null,
  startedAt: null,
  completedAt: null,
  createdAt: new Date().toISOString(),
  updatedAt: new Date().toISOString(),
};

describe('ProductContentProcessor', () => {
  it('parses seed and persists content tree then completes', async () => {
    const repo = {
      findGenerationById: jest.fn().mockResolvedValue(generation),
      updateGeneration: jest.fn().mockResolvedValue(generation),
      replaceContentTree: jest.fn().mockResolvedValue({
        sessionsCount: 2,
        topicsCount: 2,
        scriptsCount: 2,
      }),
    };

    const processor = new ProductContentProcessor(
      repo as never,
      audit as never,
      cache as never,
      logger as never,
      cls as never,
      new SeedOutlineParser(),
    );

    await processor.process({
      data: {
        generationId: generation.id,
        productId: generation.productId,
        userId: generation.userId,
        productType: 'classroom',
        mode: 'replace',
      },
    } as never);

    expect(repo.replaceContentTree).toHaveBeenCalledWith(
      generation.productId,
      expect.arrayContaining([
        expect.objectContaining({ sessionNumber: 1 }),
      ]),
    );
    expect(repo.updateGeneration).toHaveBeenCalledWith(
      generation.id,
      expect.objectContaining({ status: 'completed', progress: 100 }),
    );
    expect(audit.log).toHaveBeenCalledWith(
      expect.objectContaining({ action: 'products.generation_completed' }),
      { strict: false },
    );
  });
});
