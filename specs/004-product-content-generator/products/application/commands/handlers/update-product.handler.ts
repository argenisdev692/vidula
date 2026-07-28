import {
  ConflictException,
  Inject,
  Injectable,
} from '@nestjs/common';
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
import { UpdateProductCommand } from '../update-product.command';
import {
  PRODUCT_SLUG_CONFLICT,
  PRODUCTS_CACHE_PATTERN,
  toProductSlug,
} from '../../products-cache.constants';
import { findOwnedProductOrFail } from '../../services/find-owned-product';

@Injectable()
@CommandHandler(UpdateProductCommand)
export class UpdateProductHandler
  implements ICommandHandler<UpdateProductCommand>
{
  constructor(
    @Inject(PRODUCT_REPOSITORY) private readonly repo: IProductRepository,
    @Inject(AUDIT_PORT) private readonly audit: IAuditPort,
    @Inject(CACHE_PORT) private readonly cache: ICachePort,
    private readonly logger: LoggerService,
    private readonly cls: ClsService,
  ) {
    this.logger.setContext(UpdateProductHandler.name);
  }

  async execute(command: UpdateProductCommand): Promise<Product> {
    const traceId = this.cls.get<string>('traceId');
    const { actorId, id, dto } = command;
    this.logger.info('UpdateProductHandler start', { traceId, actorId, id });

    const existing = await findOwnedProductOrFail(this.repo, id, actorId);

    let slug = dto.slug;
    if (!slug && dto.title) {
      slug = toProductSlug(dto.title);
    }
    if (slug && slug !== existing.slug) {
      const taken = await this.repo.findIdBySlug(slug);
      if (taken && taken !== id) {
        throw new ConflictException(PRODUCT_SLUG_CONFLICT);
      }
    }

    const product = await this.persist(command, slug);
    await this.cache.delByPattern(PRODUCTS_CACHE_PATTERN);
    this.logger.info('UpdateProductHandler end', { traceId, id });
    return product;
  }

  @Transactional()
  private async persist(
    command: UpdateProductCommand,
    slug: string | undefined,
  ): Promise<Product> {
    const { actorId, id, dto } = command;
    const product = await this.repo.update(id, {
      ...dto,
      ...(slug !== undefined ? { slug } : {}),
    });
    await this.audit.log(
      {
        action: 'products.updated',
        actorId,
        resourceType: 'PRODUCT',
        resourceId: id,
      },
      { strict: true },
    );
    return product;
  }
}
