export class GetProductByIdQuery {
  constructor(
    public readonly actorId: string,
    public readonly id: string,
    public readonly withTrashed: boolean,
  ) {}
}
