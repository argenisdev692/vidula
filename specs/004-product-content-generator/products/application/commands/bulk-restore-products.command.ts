export class BulkRestoreProductsCommand {
  constructor(
    public readonly actorId: string,
    public readonly ids: string[],
  ) {}
}
