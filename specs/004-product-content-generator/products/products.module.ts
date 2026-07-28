import { Module } from '@nestjs/common';
import { CqrsModule } from '@nestjs/cqrs';
import { BullModule } from '@nestjs/bullmq';
import { CacheModule } from '../../shared/cache/cache.module';
import { QUEUE_NAMES } from '../../shared/messaging/queues.constants';
import { AUDIT_PORT } from '../../shared/activity-log/audit.port';
import { ActivityLogService } from '../../shared/activity-log/activity-log.service';
import { CACHE_PORT } from '../../shared/cache/cache.port';
import { CacheService } from '../../shared/cache/cache.service';

import { ProductsController } from './infrastructure/api/controllers/products.controller';
import { PRODUCT_REPOSITORY } from './domain/ports/product.repository.port';
import { PrismaProductRepository } from './infrastructure/persistence/repositories/prisma-product.repository';
import { ProductGenerationRequestService } from './application/services/product-generation-request.service';
import { SeedOutlineParser } from './application/services/seed-outline.parser';
import { ProductContentRequestedListener } from './infrastructure/event-listeners/product-content-requested.listener';
import { ProductContentProcessor } from './infrastructure/jobs/product-content.processor';

import { CreateProductHandler } from './application/commands/handlers/create-product.handler';
import { UpdateProductHandler } from './application/commands/handlers/update-product.handler';
import { DeleteProductHandler } from './application/commands/handlers/delete-product.handler';
import { RestoreProductHandler } from './application/commands/handlers/restore-product.handler';
import { BulkDeleteProductsHandler } from './application/commands/handlers/bulk-delete-products.handler';
import { BulkRestoreProductsHandler } from './application/commands/handlers/bulk-restore-products.handler';
import { StartContentGenerationHandler } from './application/commands/handlers/start-content-generation.handler';

import { ListProductsHandler } from './application/queries/handlers/list-products.handler';
import { GetProductByIdHandler } from './application/queries/handlers/get-product-by-id.handler';
import { ExportProductsHandler } from './application/queries/handlers/export-products.handler';
import { GetContentGenerationStatusHandler } from './application/queries/handlers/get-content-generation-status.handler';

const CommandHandlers = [
  CreateProductHandler,
  UpdateProductHandler,
  DeleteProductHandler,
  RestoreProductHandler,
  BulkDeleteProductsHandler,
  BulkRestoreProductsHandler,
  StartContentGenerationHandler,
];

const QueryHandlers = [
  ListProductsHandler,
  GetProductByIdHandler,
  ExportProductsHandler,
  GetContentGenerationStatusHandler,
];

@Module({
  imports: [
    CqrsModule,
    CacheModule,
    BullModule.registerQueue({ name: QUEUE_NAMES.PRODUCT_CONTENT }),
  ],
  controllers: [ProductsController],
  providers: [
    ...CommandHandlers,
    ...QueryHandlers,
    ProductGenerationRequestService,
    SeedOutlineParser,
    ProductContentRequestedListener,
    ProductContentProcessor,
    { provide: PRODUCT_REPOSITORY, useClass: PrismaProductRepository },
    { provide: AUDIT_PORT, useExisting: ActivityLogService },
    { provide: CACHE_PORT, useExisting: CacheService },
  ],
})
export class ProductsModule {}
