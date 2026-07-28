export class DeleteProductCommand {
  constructor(
    public readonly actorId: string,
    public readonly id: string,
  ) {}
}
