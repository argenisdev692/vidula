import { Inject, Injectable, NotFoundException } from '@nestjs/common';
import { QueryHandler, type IQueryHandler } from '@nestjs/cqrs';
import { ClsService } from 'nestjs-cls';
import { LoggerService } from '../../../../../logger/logger.service';
import {
  PRODUCT_REPOSITORY,
  type IProductRepository,
} from '../../../domain/ports/product.repository.port';
import type { ContentGeneration } from '../../../domain/product.types';
import { GetContentGenerationStatusQuery } from '../get-content-generation-status.query';
import {
  GENERATION_NOT_FOUND,
} from '../../products-cache.constants';
import { findOwnedProductOrFail } from '../../services/find-owned-product';

@Injectable()
@QueryHandler(GetContentGenerationStatusQuery)
export class GetContentGenerationStatusHandler
  implements IQueryHandler<GetContentGenerationStatusQuery>
{
  constructor(
    @Inject(PRODUCT_REPOSITORY) private readonly repo: IProductRepository,
    private readonly logger: LoggerService,
    private readonly cls: ClsService,
  ) {
    this.logger.setContext(GetContentGenerationStatusHandler.name);
  }

  async execute(
    query: GetContentGenerationStatusQuery,
  ): Promise<ContentGeneration> {
    const traceId = this.cls.get<string>('traceId');
    this.logger.info('GetContentGenerationStatusHandler', {
      traceId,
      productId: query.productId,
      generationId: query.generationId,
    });

    await findOwnedProductOrFail(
      this.repo,
      query.productId,
      query.actorId,
    );

    const generation = await this.repo.findGenerationById(query.generationId);
    if (
      !generation ||
      generation.productId !== query.productId ||
      generation.userId !== query.actorId
    ) {
      throw new NotFoundException(GENERATION_NOT_FOUND);
    }
    return generation;
  }
}
