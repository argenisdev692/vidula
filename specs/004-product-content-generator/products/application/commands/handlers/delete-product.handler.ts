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
import { DeleteProductCommand } from '../delete-product.command';
import { PRODUCTS_CACHE_PATTERN } from '../../products-cache.constants';
import { findOwnedProductOrFail } from '../../services/find-owned-product';

@Injectable()
@CommandHandler(DeleteProductCommand)
export class DeleteProductHandler
  implements ICommandHandler<DeleteProductCommand>
{
  constructor(
    @Inject(PRODUCT_REPOSITORY) private readonly repo: IProductRepository,
    @Inject(AUDIT_PORT) private readonly audit: IAuditPort,
    @Inject(CACHE_PORT) private readonly cache: ICachePort,
    private readonly logger: LoggerService,
    private readonly cls: ClsService,
  ) {
    this.logger.setContext(DeleteProductHandler.name);
  }

  async execute(command: DeleteProductCommand): Promise<void> {
    const traceId = this.cls.get<string>('traceId');
    const { actorId, id } = command;
    this.logger.info('DeleteProductHandler start', { traceId, actorId, id });
    await findOwnedProductOrFail(this.repo, id, actorId);
    await this.persist(command);
    await this.cache.delByPattern(PRODUCTS_CACHE_PATTERN);
    this.logger.info('DeleteProductHandler end', { traceId, id });
  }

  @Transactional()
  private async persist(command: DeleteProductCommand): Promise<void> {
    const { actorId, id } = command;
    await this.repo.softDelete(id);
    await this.audit.log(
      {
        action: 'products.deleted',
        actorId,
        resourceType: 'PRODUCT',
        resourceId: id,
      },
      { strict: true },
    );
  }
}
