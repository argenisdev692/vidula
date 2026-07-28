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
import { BulkDeleteProductsCommand } from '../bulk-delete-products.command';
import { PRODUCTS_CACHE_PATTERN } from '../../products-cache.constants';

@Injectable()
@CommandHandler(BulkDeleteProductsCommand)
export class BulkDeleteProductsHandler
  implements ICommandHandler<BulkDeleteProductsCommand>
{
  constructor(
    @Inject(PRODUCT_REPOSITORY) private readonly repo: IProductRepository,
    @Inject(AUDIT_PORT) private readonly audit: IAuditPort,
    @Inject(CACHE_PORT) private readonly cache: ICachePort,
    private readonly logger: LoggerService,
    private readonly cls: ClsService,
  ) {
    this.logger.setContext(BulkDeleteProductsHandler.name);
  }

  async execute(
    command: BulkDeleteProductsCommand,
  ): Promise<{ count: number }> {
    const traceId = this.cls.get<string>('traceId');
    this.logger.info('BulkDeleteProductsHandler start', {
      traceId,
      actorId: command.actorId,
      count: command.ids.length,
    });
    const result = await this.persist(command);
    await this.cache.delByPattern(PRODUCTS_CACHE_PATTERN);
    this.logger.info('BulkDeleteProductsHandler end', {
      traceId,
      count: result.count,
    });
    return result;
  }

  @Transactional()
  private async persist(
    command: BulkDeleteProductsCommand,
  ): Promise<{ count: number }> {
    const result = await this.repo.bulkDelete(command.actorId, command.ids);
    await this.audit.log(
      {
        action: 'products.bulk_deleted',
        actorId: command.actorId,
        resourceType: 'PRODUCT',
        metadata: { ids: command.ids, count: result.count },
      },
      { strict: true },
    );
    return result;
  }
}
