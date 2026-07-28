import { Injectable } from '@nestjs/common';
import { CommandHandler, type ICommandHandler } from '@nestjs/cqrs';
import { StartContentGenerationCommand } from '../start-content-generation.command';
import { ProductGenerationRequestService } from '../../services/product-generation-request.service';
import type { GenerateContentResponse } from '../../dtos/generate-content.dto';

@Injectable()
@CommandHandler(StartContentGenerationCommand)
export class StartContentGenerationHandler
  implements ICommandHandler<StartContentGenerationCommand>
{
  constructor(
    private readonly requestService: ProductGenerationRequestService,
  ) {}

  async execute(
    command: StartContentGenerationCommand,
  ): Promise<GenerateContentResponse> {
    return this.requestService.start({
      actorId: command.actorId,
      productId: command.productId,
      markdown: command.markdown,
      mode: command.mode,
      forceReplace: command.forceReplace,
    });
  }
}
