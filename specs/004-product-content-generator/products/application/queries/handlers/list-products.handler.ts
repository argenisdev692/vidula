import { Inject, Injectable } from '@nestjs/common';
import { QueryHandler, type IQueryHandler } from '@nestjs/cqrs';
import { ClsService } from 'nestjs-cls';
import { LoggerService } from '../../../../../logger/logger.service';
import {
  PRODUCT_REPOSITORY,
  type IProductRepository,
} from '../../../domain/ports/product.repository.port';
import type { Product } from '../../../domain/product.types';
import { ListProductsQuery } from '../list-products.query';

@Injectable()
@QueryHandler(ListProductsQuery)
export class ListProductsHandler implements IQueryHandler<ListProductsQuery> {
  constructor(
    @Inject(PRODUCT_REPOSITORY) private readonly repo: IProductRepository,
    private readonly logger: LoggerService,
    private readonly cls: ClsService,
  ) {
    this.logger.setContext(ListProductsHandler.name);
  }

  async execute(
    query: ListProductsQuery,
  ): Promise<{ data: Product[]; total: number }> {
    const traceId = this.cls.get<string>('traceId');
    this.logger.info('ListProductsHandler', {
      traceId,
      actorId: query.actorId,
      limit: query.limit,
      skip: query.skip,
    });
    return this.repo.findAll(
      query.actorId,
      query.limit,
      query.skip,
      query.trashed,
      query.options,
    );
  }
}
