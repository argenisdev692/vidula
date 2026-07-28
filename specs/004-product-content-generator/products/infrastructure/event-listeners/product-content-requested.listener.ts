import { Injectable } from '@nestjs/common';
import { OnEvent } from '@nestjs/event-emitter';
import { InjectQueue } from '@nestjs/bullmq';
import { Queue } from 'bullmq';
import { ClsService } from 'nestjs-cls';
import { LoggerService } from '../../../../logger/logger.service';
import { QUEUE_NAMES } from '../../../../shared/messaging/queues.constants';
import { ProductContentRequestedEvent } from '../../domain/events/product-content-requested.event';

@Injectable()
export class ProductContentRequestedListener {
  constructor(
    @InjectQueue(QUEUE_NAMES.PRODUCT_CONTENT)
    private readonly queue: Queue,
    private readonly logger: LoggerService,
    private readonly cls: ClsService,
  ) {
    this.logger.setContext(ProductContentRequestedListener.name);
  }

  @OnEvent('product.content.requested', { async: true })
  async handle(event: ProductContentRequestedEvent): Promise<void> {
    const traceId = this.cls.get<string>('traceId') ?? event.generationId;

    this.logger.info('ProductContentRequestedListener received event', {
      traceId,
      generationId: event.generationId,
      productId: event.productId,
    });

    await this.queue.add(
      'process-product-content',
      {
        generationId: event.generationId,
        productId: event.productId,
        userId: event.userId,
        productType: event.productType,
        mode: event.mode,
      },
      {
        jobId: `product-content:${event.generationId}`,
        attempts: 3,
        backoff: { type: 'exponential', delay: 60_000 },
        removeOnComplete: 100,
        removeOnFail: false,
      },
    );

    this.logger.info('Product content job enqueued', {
      traceId,
      generationId: event.generationId,
    });
  }
}
