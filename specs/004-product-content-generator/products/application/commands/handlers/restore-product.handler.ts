import { Inject, Injectable } from '@nestjs/common';
import { CommandHandler, type ICommandHandler } from '@nestjs/cqrs';
import { ClsService } from 'nestjs-cls';
import { Transactional } from '@nestjs-cls/transactional';
import { LoggerService } from '../../../../../logger/logger.service';
import { AUDIT_PORT, type IAuditPort } from '../../../../../shared/activity-log/audit.port';
import { CACHE_PORT, type ICachePort } from '../../../../../shared/cache/cache.port';
import {
  PRODUCT_REPOSITORY,
  type IProductRepository,
} from '../../../domain/ports/product.repository.port';
import type { Product } from '../../../domain/product.types';
import { RestoreProductCommand } from '../restore-product.command';
import { PRODUCTS_CACHE_PATTERN } from '../../products-cache.constants';
import { findOwnedProductOrFail } from '../../services/find-owned-product';

@Injectable()
@CommandHandler(RestoreProductCommand)
export class RestoreProductHandler
  implements ICommandHandler<RestoreProductCommand>
{
  constructor(
    @Inject(PRODUCT_REPOSITORY) private readonly repo: IProductRepository,
    @Inject(AUDIT_PORT) private readonly audit: IAuditPort,
    @Inject(CACHE_PORT) private readonly cache: ICachePort,
    private readonly logger: LoggerService,
    private readonly cls: ClsService,
  ) {
    this.logger.setContext(RestoreProductHandler.name);
  }

  async execute(command: RestoreProductCommand): Promise<Product> {
    const traceId = this.cls.get<string>('traceId');
    const { actorId, id } = command;
    this.logger.info('RestoreProductHandler start', { traceId, actorId, id });
    await findOwnedProductOrFail(this.repo, id, actorId, true);
    const product = await this.persist(command);
    await this.cache.delByPattern(PRODUCTS_CACHE_PATTERN);
    this.logger.info('RestoreProductHandler end', { traceId, id });
    return product;
  }

  @Transactional()
  private async persist(command: RestoreProductCommand): Promise<Product> {
    const { actorId, id } = command;
    const product = await this.repo.restore(id);
    await this.audit.log(
      {
        action: 'products.restored',
        actorId,
        resourceType: 'PRODUCT',
        resourceId: id,
      },
      { strict: true },
    );
    return product;
  }
}
