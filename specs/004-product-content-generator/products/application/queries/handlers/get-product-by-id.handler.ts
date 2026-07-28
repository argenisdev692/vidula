import { Inject, Injectable } from '@nestjs/common';
import { QueryHandler, type IQueryHandler } from '@nestjs/cqrs';
import { ClsService } from 'nestjs-cls';
import { LoggerService } from '../../../../../logger/logger.service';
import {
  PRODUCT_REPOSITORY,
  type IProductRepository,
} from '../../../domain/ports/product.repository.port';
import type { Product } from '../../../domain/product.types';
import { GetProductByIdQuery } from '../get-product-by-id.query';
import { findOwnedProductOrFail } from '../../services/find-owned-product';

@Injectable()
@QueryHandler(GetProductByIdQuery)
export class GetProductByIdHandler
  implements IQueryHandler<GetProductByIdQuery>
{
  constructor(
    @Inject(PRODUCT_REPOSITORY) private readonly repo: IProductRepository,
    private readonly logger: LoggerService,
    private readonly cls: ClsService,
  ) {
    this.logger.setContext(GetProductByIdHandler.name);
  }

  async execute(query: GetProductByIdQuery): Promise<Product> {
    const traceId = this.cls.get<string>('traceId');
    this.logger.info('GetProductByIdHandler', {
      traceId,
      actorId: query.actorId,
      id: query.id,
    });
    return findOwnedProductOrFail(
      this.repo,
      query.id,
      query.actorId,
      query.withTrashed,
    );
  }
}
