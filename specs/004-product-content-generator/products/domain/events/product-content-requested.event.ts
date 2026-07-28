export class ProductContentRequestedEvent {
  constructor(
    public readonly generationId: string,
    public readonly productId: string,
    public readonly userId: string,
    public readonly productType: string,
    public readonly mode: string,
  ) {}
}
