import { Processor, WorkerHost } from '@nestjs/bullmq';
import { Inject, Injectable } from '@nestjs/common';
import { Job } from 'bullmq';
import { ClsService } from 'nestjs-cls';
import { LoggerService } from '../../../../logger/logger.service';
import { QUEUE_NAMES } from '../../../../shared/messaging/queues.constants';
import { AUDIT_PORT, type IAuditPort } from '../../../../shared/activity-log/audit.port';
import { CACHE_PORT, type ICachePort } from '../../../../shared/cache/cache.port';
import {
  PRODUCT_REPOSITORY,
  type IProductRepository,
} from '../../domain/ports/product.repository.port';
import type { ProductType } from '../../domain/product.types';
import { SeedOutlineParser } from '../../application/services/seed-outline.parser';
import { PRODUCTS_CACHE_PATTERN } from '../../application/products-cache.constants';

export interface ProductContentJobData {
  generationId: string;
  productId: string;
  userId: string;
  productType: ProductType;
  mode: string;
}

/**
 * Wave B skeleton: parse seed → persist sessions/topics/script stubs → complete.
 * Real AI/Tavily/Context7 grounding is deferred to Wave B+.
 */
@Injectable()
@Processor(QUEUE_NAMES.PRODUCT_CONTENT, { concurrency: 2 })
export class ProductContentProcessor extends WorkerHost {
  constructor(
    @Inject(PRODUCT_REPOSITORY) private readonly repo: IProductRepository,
    @Inject(AUDIT_PORT) private readonly audit: IAuditPort,
    @Inject(CACHE_PORT) private readonly cache: ICachePort,
    private readonly logger: LoggerService,
    private readonly cls: ClsService,
    private readonly parser: SeedOutlineParser,
  ) {
    super();
    this.logger.setContext(ProductContentProcessor.name);
  }

  async process(job: Job<ProductContentJobData>): Promise<void> {
    const { generationId, productId, userId, productType, mode } = job.data;
    const traceId = this.cls.get<string>('traceId') ?? generationId;

    this.logger.info('ProductContentProcessor start', {
      traceId,
      generationId,
      productId,
      productType,
      mode,
    });

    try {
      const generation = await this.repo.findGenerationById(generationId);
      if (!generation) {
        this.logger.warn('Generation missing — aborting job', {
          traceId,
          generationId,
        });
        return;
      }

      await this.repo.updateGeneration(generationId, {
        status: 'parsing',
        progress: 10,
        startedAt: new Date(),
        error: null,
      });
      await this.cache.delByPattern(PRODUCTS_CACHE_PATTERN);

      const outline = this.parser.parse(
        generation.sourceMarkdown,
        productType,
      );

      await this.repo.updateGeneration(generationId, {
        status: 'generating',
        progress: 40,
      });

      const counts = await this.repo.replaceContentTree(
        productId,
        outline.sessions,
      );

      await this.repo.updateGeneration(generationId, {
        status: 'verifying',
        progress: 70,
        sessionsCount: counts.sessionsCount,
        topicsCount: counts.topicsCount,
        scriptsCount: counts.scriptsCount,
      });

      // Wave B: skip rendering/packaging — jump to completed
      await this.repo.updateGeneration(generationId, {
        status: 'completed',
        progress: 100,
        completedAt: new Date(),
      });

      await this.audit.log(
        {
          action: 'products.generation_completed',
          actorId: userId,
          resourceType: 'PRODUCT',
          resourceId: productId,
          metadata: {
            generationId,
            ...counts,
          },
        },
        { strict: false },
      );

      await this.cache.delByPattern(PRODUCTS_CACHE_PATTERN);

      this.logger.info('ProductContentProcessor completed', {
        traceId,
        generationId,
        ...counts,
      });
    } catch (err) {
      const message =
        err instanceof Error ? err.message : 'Content generation failed';
      this.logger.error('ProductContentProcessor failed', {
        traceId,
        generationId,
        error: message,
      });

      await this.repo.updateGeneration(generationId, {
        status: 'failed',
        error: message.slice(0, 2000),
        completedAt: new Date(),
      });

      await this.audit.log(
        {
          action: 'products.generation_failed',
          actorId: userId,
          resourceType: 'PRODUCT',
          resourceId: productId,
          metadata: { generationId },
        },
        { strict: false },
      );

      await this.cache.delByPattern(PRODUCTS_CACHE_PATTERN);
      throw err;
    }
  }
}
