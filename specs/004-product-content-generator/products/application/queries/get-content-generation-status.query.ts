export class GetContentGenerationStatusQuery {
  constructor(
    public readonly actorId: string,
    public readonly productId: string,
    public readonly generationId: string,
  ) {}
}
