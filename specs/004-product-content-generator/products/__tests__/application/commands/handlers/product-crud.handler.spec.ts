jest.mock('@nestjs-cls/transactional', () => ({
  Transactional:
    () => (_target: unknown, _key: string, descriptor: PropertyDescriptor) =>
      descriptor,
}));

import {
  ConflictException,
  NotFoundException,
} from '@nestjs/common';
import { CreateProductHandler } from '../../../../application/commands/handlers/create-product.handler';
import { DeleteProductHandler } from '../../../../application/commands/handlers/delete-product.handler';
import { CreateProductCommand } from '../../../../application/commands/create-product.command';
import { DeleteProductCommand } from '../../../../application/commands/delete-product.command';
import type { Product } from '../../../../domain/product.types';

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

const USER_ID = '018f0000-0000-7000-8000-000000000002';

const baseProduct: Product = {
  id: '018f0000-0000-7000-8000-000000000010',
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
  classroom: {
    id: '018f0000-0000-7000-8000-000000000011',
    maxStudents: null,
    meetUrl: null,
    objectives: null,
    requirements: null,
  },
};

const makeRepo = (overrides: Record<string, jest.Mock> = {}) => ({
  findIdBySlug: jest.fn().mockResolvedValue(null),
  create: jest.fn().mockResolvedValue(baseProduct),
  findById: jest.fn().mockResolvedValue(baseProduct),
  softDelete: jest.fn().mockResolvedValue(undefined),
  ...overrides,
});

describe('CreateProductHandler', () => {
  it('creates product, audits, and invalidates cache', async () => {
    const repo = makeRepo();
    const handler = new CreateProductHandler(
      repo as never,
      audit as never,
      cache as never,
      logger as never,
      cls as never,
    );

    const result = await handler.execute(
      new CreateProductCommand(USER_ID, {
        type: 'classroom',
        title: 'Curso Copilot',
        price: '0',
        currency: 'EUR',
        status: 'draft',
        level: 'beginner',
        language: 'es',
      } as never),
    );

    expect(result.id).toBe(baseProduct.id);
    expect(repo.create).toHaveBeenCalled();
    expect(audit.log).toHaveBeenCalledWith(
      expect.objectContaining({ action: 'products.created' }),
      { strict: true },
    );
    expect(cache.delByPattern).toHaveBeenCalled();
  });

  it('throws ConflictException when slug exists', async () => {
    const repo = makeRepo({
      findIdBySlug: jest.fn().mockResolvedValue('other-id'),
    });
    const handler = new CreateProductHandler(
      repo as never,
      audit as never,
      cache as never,
      logger as never,
      cls as never,
    );

    await expect(
      handler.execute(
        new CreateProductCommand(USER_ID, {
          type: 'classroom',
          title: 'Curso Copilot',
          slug: 'curso-copilot',
          price: '0',
          currency: 'EUR',
          status: 'draft',
          level: 'beginner',
          language: 'es',
        } as never),
      ),
    ).rejects.toBeInstanceOf(ConflictException);
  });
});

describe('DeleteProductHandler', () => {
  it('soft-deletes owned product', async () => {
    const repo = makeRepo();
    const handler = new DeleteProductHandler(
      repo as never,
      audit as never,
      cache as never,
      logger as never,
      cls as never,
    );

    await handler.execute(new DeleteProductCommand(USER_ID, baseProduct.id));
    expect(repo.softDelete).toHaveBeenCalledWith(baseProduct.id);
  });

  it('throws NotFoundException for other owner', async () => {
    const repo = makeRepo({
      findById: jest.fn().mockResolvedValue({
        ...baseProduct,
        userId: 'other-user',
      }),
    });
    const handler = new DeleteProductHandler(
      repo as never,
      audit as never,
      cache as never,
      logger as never,
      cls as never,
    );

    await expect(
      handler.execute(new DeleteProductCommand(USER_ID, baseProduct.id)),
    ).rejects.toBeInstanceOf(NotFoundException);
  });
});
