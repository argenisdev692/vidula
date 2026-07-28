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
import { BulkRestoreProductsCommand } from '../bulk-restore-products.command';
import { PRODUCTS_CACHE_PATTERN } from '../../products-cache.constants';

@Injectable()
@CommandHandler(BulkRestoreProductsCommand)
export class BulkRestoreProductsHandler
  implements ICommandHandler<BulkRestoreProductsCommand>
{
  constructor(
    @Inject(PRODUCT_REPOSITORY) private readonly repo: IProductRepository,
    @Inject(AUDIT_PORT) private readonly audit: IAuditPort,
    @Inject(CACHE_PORT) private readonly cache: ICachePort,
    private readonly logger: LoggerService,
    private readonly cls: ClsService,
  ) {
    this.logger.setContext(BulkRestoreProductsHandler.name);
  }

  async execute(
    command: BulkRestoreProductsCommand,
  ): Promise<{ count: number }> {
    const traceId = this.cls.get<string>('traceId');
    this.logger.info('BulkRestoreProductsHandler start', {
      traceId,
      actorId: command.actorId,
      count: command.ids.length,
    });
    const result = await this.persist(command);
    await this.cache.delByPattern(PRODUCTS_CACHE_PATTERN);
    this.logger.info('BulkRestoreProductsHandler end', {
      traceId,
      count: result.count,
    });
    return result;
  }

  @Transactional()
  private async persist(
    command: BulkRestoreProductsCommand,
  ): Promise<{ count: number }> {
    const result = await this.repo.bulkRestore(command.actorId, command.ids);
    await this.audit.log(
      {
        action: 'products.bulk_restored',
        actorId: command.actorId,
        resourceType: 'PRODUCT',
        metadata: { ids: command.ids, count: result.count },
      },
      { strict: true },
    );
    return result;
  }
}
