import type { UpdateProductDto } from '../dtos/update-product.dto';

export class UpdateProductCommand {
  constructor(
    public readonly actorId: string,
    public readonly id: string,
    public readonly dto: UpdateProductDto,
  ) {}
}
