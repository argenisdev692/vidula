export class StartContentGenerationCommand {
  constructor(
    public readonly actorId: string,
    public readonly productId: string,
    public readonly markdown: string,
    public readonly mode: 'replace',
    public readonly forceReplace: boolean,
  ) {}
}
