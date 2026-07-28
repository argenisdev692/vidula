export class BulkDeleteProductsCommand {
  constructor(
    public readonly actorId: string,
    public readonly ids: string[],
  ) {}
}
