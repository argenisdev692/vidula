export class RestoreProductCommand {
  constructor(
    public readonly actorId: string,
    public readonly id: string,
  ) {}
}
