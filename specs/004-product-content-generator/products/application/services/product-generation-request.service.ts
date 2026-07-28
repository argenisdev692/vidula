import {
  ConflictException,
  Inject,
  Injectable,
  NotFoundException,
  UnprocessableEntityException,
} from '@nestjs/common';
import { EventEmitter2 } from '@nestjs/event-emitter';
import { ClsService } from 'nestjs-cls';
import { Transactional } from '@nestjs-cls/transactional';
import { LoggerService } from '../../../../logger/logger.service';
import { AUDIT_PORT, type IAuditPort } from '../../../../shared/activity-log/audit.port';
import { CACHE_PORT, type ICachePort } from '../../../../shared/cache/cache.port';
import {
  PRODUCT_REPOSITORY,
  type IProductRepository,
} from '../../domain/ports/product.repository.port';
import { ProductContentRequestedEvent } from '../../domain/events/product-content-requested.event';
import { GENERATABLE_PRODUCT_TYPES } from '../../domain/product.types';
import type { ContentGeneration } from '../../domain/product.types';
import {
  GENERATION_IN_FLIGHT,
  GENERATION_INVALID_MARKDOWN,
  GENERATION_TYPE_UNSUPPORTED,
  MAX_SOURCE_MARKDOWN_BYTES,
  PRODUCT_NOT_FOUND,
  PRODUCTS_CACHE_PATTERN,
} from '../products-cache.constants';
import { SeedOutlineParser } from './seed-outline.parser';

@Injectable()
export class ProductGenerationRequestService {
  constructor(
    @Inject(PRODUCT_REPOSITORY)
    private readonly productRepo: IProductRepository,
    @Inject(AUDIT_PORT) private readonly audit: IAuditPort,
    @Inject(CACHE_PORT) private readonly cache: ICachePort,
    private readonly eventEmitter: EventEmitter2,
    private readonly logger: LoggerService,
    private readonly cls: ClsService,
    private readonly parser: SeedOutlineParser,
  ) {
    this.logger.setContext(ProductGenerationRequestService.name);
  }

  async start(params: {
    actorId: string;
    productId: string;
    markdown: string;
    mode: 'replace';
    forceReplace: boolean;
  }): Promise<{ generationId: string; status: string }> {
    const traceId = this.cls.get<string>('traceId');
    const { actorId, productId, markdown, mode, forceReplace } = params;

    this.logger.info('ProductGenerationRequestService.start', {
      traceId,
      actorId,
      productId,
      mode,
      markdownBytes: Buffer.byteLength(markdown, 'utf8'),
    });

    if (Buffer.byteLength(markdown, 'utf8') > MAX_SOURCE_MARKDOWN_BYTES) {
      throw new UnprocessableEntityException(GENERATION_INVALID_MARKDOWN);
    }

    const product = await this.productRepo.findById(productId);
    if (!product || product.userId !== actorId) {
      throw new NotFoundException(PRODUCT_NOT_FOUND);
    }

    if (!GENERATABLE_PRODUCT_TYPES.includes(product.type)) {
      throw new UnprocessableEntityException(GENERATION_TYPE_UNSUPPORTED);
    }

    try {
      this.parser.parse(markdown, product.type);
    } catch {
      throw new UnprocessableEntityException(GENERATION_INVALID_MARKDOWN);
    }

    const inFlight = await this.productRepo.findInFlightGeneration(productId);
    if (inFlight && !forceReplace) {
      throw new ConflictException(GENERATION_IN_FLIGHT);
    }

    if (inFlight && forceReplace) {
      await this.productRepo.updateGeneration(inFlight.id, {
        status: 'failed',
        error: 'Superseded by forceReplace',
        completedAt: new Date(),
      });
    }

    const generation = await this.persist({
      actorId,
      productId,
      markdown,
      mode,
    });

    await this.cache.delByPattern(PRODUCTS_CACHE_PATTERN);
    this.eventEmitter.emit(
      'product.content.requested',
      new ProductContentRequestedEvent(
        generation.id,
        productId,
        actorId,
        product.type,
        mode,
      ),
    );

    this.logger.info('ProductGenerationRequestService.end', {
      traceId,
      generationId: generation.id,
    });

    return { generationId: generation.id, status: generation.status };
  }

  @Transactional()
  private async persist(params: {
    actorId: string;
    productId: string;
    markdown: string;
    mode: string;
  }): Promise<ContentGeneration> {
    const generation = await this.productRepo.createGeneration({
      productId: params.productId,
      userId: params.actorId,
      sourceMarkdown: params.markdown,
      mode: params.mode,
    });

    await this.audit.log(
      {
        action: 'products.generation_started',
        actorId: params.actorId,
        resourceType: 'PRODUCT',
        resourceId: params.productId,
        metadata: {
          generationId: generation.id,
          mode: params.mode,
          markdownBytes: Buffer.byteLength(params.markdown, 'utf8'),
        },
      },
      { strict: true },
    );

    return generation;
  }
}
