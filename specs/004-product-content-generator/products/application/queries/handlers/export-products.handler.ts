import { Inject, Injectable } from '@nestjs/common';
import { QueryHandler, type IQueryHandler } from '@nestjs/cqrs';
import { ClsService } from 'nestjs-cls';
import { LoggerService } from '../../../../../logger/logger.service';
import {
  buildExport,
  type ExportColumn,
  type ExportResult,
} from '../../../../../shared/export/table-export.util';
import {
  PRODUCT_REPOSITORY,
  type IProductRepository,
} from '../../../domain/ports/product.repository.port';
import type { Product } from '../../../domain/product.types';
import { ExportProductsQuery } from '../export-products.query';
import { AUDIT_PORT, type IAuditPort } from '../../../../../shared/activity-log/audit.port';

const PRODUCT_EXPORT_COLUMNS: ExportColumn<Product>[] = [
  { header: 'ID', value: (p) => p.id, width: 38, pdf: 'line' },
  { header: 'Title', value: (p) => p.title, width: 28, pdf: 'heading' },
  { header: 'Slug', value: (p) => p.slug, width: 24, pdf: 'line' },
  { header: 'Type', value: (p) => p.type, width: 16, pdf: 'line' },
  { header: 'Status', value: (p) => p.status, width: 12, pdf: 'line' },
  { header: 'Price', value: (p) => p.price, width: 12, pdf: 'line' },
  { header: 'Currency', value: (p) => p.currency, width: 10 },
  { header: 'Client ID', value: (p) => p.clientId, width: 38 },
  { header: 'Created At', value: (p) => p.createdAt, width: 24, pdf: 'line' },
  { header: 'Deleted At', value: (p) => p.deletedAt, width: 24, pdf: 'line' },
];

@Injectable()
@QueryHandler(ExportProductsQuery)
export class ExportProductsHandler
  implements IQueryHandler<ExportProductsQuery>
{
  constructor(
    @Inject(PRODUCT_REPOSITORY) private readonly repo: IProductRepository,
    @Inject(AUDIT_PORT) private readonly audit: IAuditPort,
    private readonly logger: LoggerService,
    private readonly cls: ClsService,
  ) {
    this.logger.setContext(ExportProductsHandler.name);
  }

  async execute(query: ExportProductsQuery): Promise<ExportResult> {
    const traceId = this.cls.get<string>('traceId');
    this.logger.info('ExportProductsHandler', {
      traceId,
      actorId: query.actorId,
      format: query.format,
    });

    const rows = await this.repo.findAllForExport(
      query.actorId,
      query.trashed,
      query.options,
    );

    await this.audit.log(
      {
        action: 'products.export',
        actorId: query.actorId,
        resourceType: 'PRODUCT',
        metadata: { format: query.format, count: rows.length },
      },
      { strict: false },
    );

    return buildExport(query.format, rows, PRODUCT_EXPORT_COLUMNS, {
      filenamePrefix: 'products',
      title: 'Products',
      timestamp: new Date().toISOString().replace(/[:.]/g, '-'),
    });
  }
}
