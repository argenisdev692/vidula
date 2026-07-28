import { NotFoundException } from '@nestjs/common';
import type { IProductRepository } from '../../domain/ports/product.repository.port';
import type { Product } from '../../domain/product.types';
import { PRODUCT_NOT_FOUND } from '../products-cache.constants';

/** Shared ownership check — same 404 for missing vs other-owner (IDOR). */
export async function findOwnedProductOrFail(
  repo: IProductRepository,
  id: string,
  actorId: string,
  withTrashed = false,
): Promise<Product> {
  const product = await repo.findById(id, {
    withTrashed,
    includeDetails: true,
  });
  if (!product || product.userId !== actorId) {
    throw new NotFoundException(PRODUCT_NOT_FOUND);
  }
  return product;
}
