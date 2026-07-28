import type { CreateProductDto } from '../dtos/create-product.dto';

export class CreateProductCommand {
  constructor(
    public readonly actorId: string,
    public readonly dto: CreateProductDto,
  ) {}
}
