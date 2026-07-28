jest.mock('@nestjs-cls/transactional', () => ({
  Transactional:
    () => (_target: unknown, _key: string, descriptor: PropertyDescriptor) =>
      descriptor,
}));

import {
  ConflictException,
  NotFoundException,
  UnprocessableEntityException,
} from '@nestjs/common';
import { ProductGenerationRequestService } from '../../../application/services/product-generation-request.service';
import { SeedOutlineParser } from '../../../application/services/seed-outline.parser';
import type { ContentGeneration, Product } from '../../../domain/product.types';

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
const eventEmitter = { emit: jest.fn() };

const USER_ID = '018f0000-0000-7000-8000-000000000002';
const PRODUCT_ID = '018f0000-0000-7000-8000-000000000010';

const product: Product = {
  id: PRODUCT_ID,
  userId: USER_ID,
  clientId: null,
  type: 'classroom',
  title: 'Curso Copilot',
  slug: 'curso-copilot',
  description: null,
  price: '0',
  currency: 'EUR',
  status: 'draft',
  lifecycleStatus: 'active',
  thumbnail: null,
  level: 'beginner',
  language: 'es',
  startDate: null,
  endDate: null,
  totalHours: null,
  totalSessions: null,
  modality: null,
  notes: null,
  createdAt: new Date().toISOString(),
  updatedAt: new Date().toISOString(),
  deletedAt: null,
};

const generation: ContentGeneration = {
  id: '018f0000-0000-7000-8000-000000000020',
  productId: PRODUCT_ID,
  userId: USER_ID,
  status: 'pending',
  mode: 'replace',
  sourceMarkdown: 'x',
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

const validClassroomMd = `
### Sesión 1 | Intro
- **Tema 1:** First topic
- **Tema 2:** Second topic
### Sesión 2 | Next
- **Tema 3:** Third topic
`;

describe('ProductGenerationRequestService', () => {
  const parser = new SeedOutlineParser();
  const makeService = (repo: Record<string, jest.Mock>) =>
    new ProductGenerationRequestService(
      repo as never,
      audit as never,
      cache as never,
      eventEmitter as never,
      logger as never,
      cls as never,
      parser,
    );

  it('accepts generation, audits, emits event', async () => {
    const repo = {
      findById: jest.fn().mockResolvedValue(product),
      findInFlightGeneration: jest.fn().mockResolvedValue(null),
      createGeneration: jest.fn().mockResolvedValue(generation),
    };
    const service = makeService(repo);

    const result = await service.start({
      actorId: USER_ID,
      productId: PRODUCT_ID,
      markdown: validClassroomMd,
      mode: 'replace',
      forceReplace: false,
    });

    expect(result.generationId).toBe(generation.id);
    expect(repo.createGeneration).toHaveBeenCalled();
    expect(eventEmitter.emit).toHaveBeenCalledWith(
      'product.content.requested',
      expect.objectContaining({ generationId: generation.id }),
    );
  });

  it('rejects concurrent in-flight generation with 409', async () => {
    const repo = {
      findById: jest.fn().mockResolvedValue(product),
      findInFlightGeneration: jest.fn().mockResolvedValue(generation),
      createGeneration: jest.fn(),
    };
    const service = makeService(repo);

    await expect(
      service.start({
        actorId: USER_ID,
        productId: PRODUCT_ID,
        markdown: validClassroomMd,
        mode: 'replace',
        forceReplace: false,
      }),
    ).rejects.toBeInstanceOf(ConflictException);
    expect(repo.createGeneration).not.toHaveBeenCalled();
  });

  it('forceReplace supersedes in-flight generation', async () => {
    const repo = {
      findById: jest.fn().mockResolvedValue(product),
      findInFlightGeneration: jest.fn().mockResolvedValue(generation),
      updateGeneration: jest.fn().mockResolvedValue(generation),
      createGeneration: jest.fn().mockResolvedValue(generation),
    };
    const service = makeService(repo);

    await service.start({
      actorId: USER_ID,
      productId: PRODUCT_ID,
      markdown: validClassroomMd,
      mode: 'replace',
      forceReplace: true,
    });

    expect(repo.updateGeneration).toHaveBeenCalledWith(
      generation.id,
      expect.objectContaining({ status: 'failed' }),
    );
    expect(repo.createGeneration).toHaveBeenCalled();
  });

  it('rejects invalid markdown', async () => {
    const repo = {
      findById: jest.fn().mockResolvedValue(product),
      findInFlightGeneration: jest.fn().mockResolvedValue(null),
      createGeneration: jest.fn(),
    };
    const service = makeService(repo);

    await expect(
      service.start({
        actorId: USER_ID,
        productId: PRODUCT_ID,
        markdown: 'no sessions',
        mode: 'replace',
        forceReplace: false,
      }),
    ).rejects.toBeInstanceOf(UnprocessableEntityException);
  });

  it('rejects other owner', async () => {
    const repo = {
      findById: jest.fn().mockResolvedValue({ ...product, userId: 'other' }),
      findInFlightGeneration: jest.fn(),
      createGeneration: jest.fn(),
    };
    const service = makeService(repo);

    await expect(
      service.start({
        actorId: USER_ID,
        productId: PRODUCT_ID,
        markdown: validClassroomMd,
        mode: 'replace',
        forceReplace: false,
      }),
    ).rejects.toBeInstanceOf(NotFoundException);
  });
});
