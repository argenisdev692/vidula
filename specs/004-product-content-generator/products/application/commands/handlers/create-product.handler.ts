import { ConflictException, Inject, Injectable } from '@nestjs/common';
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
import { CreateProductCommand } from '../create-product.command';
import {
  PRODUCT_SLUG_CONFLICT,
  PRODUCTS_CACHE_PATTERN,
  toProductSlug,
} from '../../products-cache.constants';

@Injectable()
@CommandHandler(CreateProductCommand)
export class CreateProductHandler
  implements ICommandHandler<CreateProductCommand>
{
  constructor(
    @Inject(PRODUCT_REPOSITORY) private readonly repo: IProductRepository,
    @Inject(AUDIT_PORT) private readonly audit: IAuditPort,
    @Inject(CACHE_PORT) private readonly cache: ICachePort,
    private readonly logger: LoggerService,
    private readonly cls: ClsService,
  ) {
    this.logger.setContext(CreateProductHandler.name);
  }

  async execute(command: CreateProductCommand): Promise<Product> {
    const traceId = this.cls.get<string>('traceId');
    const { actorId, dto } = command;
    this.logger.info('CreateProductHandler start', { traceId, actorId });

    const slug = dto.slug ?? toProductSlug(dto.title);
    if (!slug) {
      throw new ConflictException(PRODUCT_SLUG_CONFLICT);
    }
    const existing = await this.repo.findIdBySlug(slug);
    if (existing) {
      throw new ConflictException(PRODUCT_SLUG_CONFLICT);
    }

    const product = await this.persist(command, slug);
    await this.cache.delByPattern(PRODUCTS_CACHE_PATTERN);
    this.logger.info('CreateProductHandler end', {
      traceId,
      productId: product.id,
    });
    return product;
  }

  @Transactional()
  private async persist(
    command: CreateProductCommand,
    slug: string,
  ): Promise<Product> {
    const { actorId, dto } = command;
    const product = await this.repo.create({
      userId: actorId,
      clientId: dto.clientId ?? null,
      type: dto.type,
      title: dto.title,
      slug,
      description: dto.description ?? null,
      price: dto.price,
      currency: dto.currency,
      status: dto.status,
      thumbnail: dto.thumbnail ?? null,
      level: dto.level,
      language: dto.language,
      startDate: dto.startDate ?? null,
      endDate: dto.endDate ?? null,
      totalHours: dto.totalHours ?? null,
      totalSessions: dto.totalSessions ?? null,
      modality: dto.modality ?? null,
      notes: dto.notes ?? null,
      classroom: dto.classroom ?? null,
      videoCourse: dto.videoCourse ?? null,
    });

    await this.audit.log(
      {
        action: 'products.created',
        actorId,
        resourceType: 'PRODUCT',
        resourceId: product.id,
        metadata: { type: product.type, slug: product.slug },
      },
      { strict: true },
    );
    return product;
  }
}
