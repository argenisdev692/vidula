import type { TrashedMode } from '../../../../shared/crud/trashed.util';
import type { DateRange } from '../../../../shared/crud/date-range.util';
import type { ProductStatus, ProductType } from '../../domain/product.types';

export class ListProductsQuery {
  constructor(
    public readonly actorId: string,
    public readonly limit: number,
    public readonly skip: number,
    public readonly trashed: TrashedMode,
    public readonly options?: {
      search?: string;
      range?: DateRange;
      type?: ProductType;
      status?: ProductStatus;
      clientId?: string;
    },
  ) {}
}
