import {
  Body,
  Controller,
  Delete,
  ForbiddenException,
  Get,
  HttpCode,
  Param,
  ParseUUIDPipe,
  Patch,
  Post,
  Query,
  Res,
  StreamableFile,
  UseGuards,
} from '@nestjs/common';
import type { Response } from 'express';
import { CommandBus, QueryBus } from '@nestjs/cqrs';
import { ZodValidationPipe } from 'nestjs-zod';
import { CacheTTL } from '@nestjs/cache-manager';
import { SkipThrottle, Throttle } from '@nestjs/throttler';
import {
  ApiAcceptedResponse,
  ApiBadRequestResponse,
  ApiBearerAuth,
  ApiConflictResponse,
  ApiCreatedResponse,
  ApiForbiddenResponse,
  ApiNoContentResponse,
  ApiNotFoundResponse,
  ApiOkResponse,
  ApiParam,
  ApiQuery,
  ApiTags,
} from '@nestjs/swagger';
import { JwtAuthGuard } from '../../../../../core/guards/jwt-auth.guard';
import { CaslGuard } from '../../../../../core/guards/casl.guard';
import { CheckAbilities } from '../../../../../core/decorators/check-abilities.decorator';
import { CurrentUser } from '../../../../../core/decorators/current-user.decorator';
import { Action } from '../../../../../core/access/actions.enum';
import type { AuthenticatedUser } from '../../../../../core/access/actions.enum';
import { CaslAbilityFactory } from '../../../../../core/access/casl-ability.factory';
import { SkipCache } from '../../../../../core/decorators/skip-cache.decorator';
import { TTL_SECONDS } from '../../../../../shared/cache/cache-ttl.constants';
import { resolveTrashedMode } from '../../../../../shared/crud/trashed.util';
import { resolveDateRange } from '../../../../../shared/crud/date-range.util';
import type { ExportResult } from '../../../../../shared/export/table-export.util';
import { CreateProductSchema } from '../../../application/dtos/create-product.dto';
import type { CreateProductDto } from '../../../application/dtos/create-product.dto';
import { UpdateProductSchema } from '../../../application/dtos/update-product.dto';
import type { UpdateProductDto } from '../../../application/dtos/update-product.dto';
import { BulkIdsSchema } from '../../../application/dtos/bulk-ids.dto';
import type { BulkIdsDto } from '../../../application/dtos/bulk-ids.dto';
import {
  ExportProductsQuerySchema,
  GetProductQuerySchema,
  ListProductsQuerySchema,
} from '../../../application/dtos/list-products.dto';
import type {
  ExportProductsQueryDto,
  GetProductQueryDto,
  ListProductsQueryDto,
} from '../../../application/dtos/list-products.dto';
import { GenerateContentSchema } from '../../../application/dtos/generate-content.dto';
import type {
  GenerateContentDto,
  GenerateContentResponse,
} from '../../../application/dtos/generate-content.dto';
import {
  ContentGenerationStatusResponse,
  ProductResponse,
} from '../../../application/dtos/product.response';
import type { ContentGeneration } from '../../../domain/product.types';
import { CreateProductCommand } from '../../../application/commands/create-product.command';
import { UpdateProductCommand } from '../../../application/commands/update-product.command';
import { DeleteProductCommand } from '../../../application/commands/delete-product.command';
import { RestoreProductCommand } from '../../../application/commands/restore-product.command';
import { BulkDeleteProductsCommand } from '../../../application/commands/bulk-delete-products.command';
import { BulkRestoreProductsCommand } from '../../../application/commands/bulk-restore-products.command';
import { StartContentGenerationCommand } from '../../../application/commands/start-content-generation.command';
import { ListProductsQuery } from '../../../application/queries/list-products.query';
import { GetProductByIdQuery } from '../../../application/queries/get-product-by-id.query';
import { ExportProductsQuery } from '../../../application/queries/export-products.query';
import { GetContentGenerationStatusQuery } from '../../../application/queries/get-content-generation-status.query';

@ApiTags('Products')
@ApiBearerAuth()
@Controller('products')
@UseGuards(JwtAuthGuard, CaslGuard)
export class ProductsController {
  constructor(
    private readonly commandBus: CommandBus,
    private readonly queryBus: QueryBus,
    private readonly abilityFactory: CaslAbilityFactory,
  ) {}

  private toGenerationResponse(
    g: ContentGeneration,
  ): ContentGenerationStatusResponse {
    return {
      id: g.id,
      productId: g.productId,
      status: g.status,
      mode: g.mode,
      progress: g.progress,
      sessionsCount: g.sessionsCount,
      topicsCount: g.topicsCount,
      scriptsCount: g.scriptsCount,
      error: g.error,
      startedAt: g.startedAt,
      completedAt: g.completedAt,
      createdAt: g.createdAt,
      updatedAt: g.updatedAt,
    };
  }

  @Post()
  @Throttle({ default: { limit: 10, ttl: 60_000 } })
  @ApiCreatedResponse({ type: ProductResponse })
  @ApiBadRequestResponse({ description: 'Validation failed' })
  @CheckAbilities({ action: Action.Create, subject: 'PRODUCT' })
  async create(
    @CurrentUser() user: AuthenticatedUser,
    @Body(new ZodValidationPipe(CreateProductSchema)) dto: CreateProductDto,
  ): Promise<ProductResponse> {
    return this.commandBus.execute(new CreateProductCommand(user.id, dto));
  }

  @Get()
  @SkipThrottle()
  @ApiOkResponse({ type: [ProductResponse] })
  @ApiQuery({ name: 'limit', required: false, type: Number })
  @ApiQuery({ name: 'skip', required: false, type: Number })
  @ApiQuery({ name: 'search', required: false, type: String })
  @ApiQuery({ name: 'type', required: false, type: String })
  @ApiQuery({ name: 'status', required: false, type: String })
  @ApiQuery({ name: 'clientId', required: false, type: String })
  @ApiForbiddenResponse()
  @CacheTTL(TTL_SECONDS.MEDIUM)
  @CheckAbilities({ action: Action.Read, subject: 'PRODUCT' })
  async findAll(
    @CurrentUser() user: AuthenticatedUser,
    @Query(new ZodValidationPipe(ListProductsQuerySchema))
    query: ListProductsQueryDto,
  ): Promise<{ data: ProductResponse[]; total: number }> {
    await this.assertCanViewTombstones(user, query);
    const result = await this.queryBus.execute(
      new ListProductsQuery(
        user.id,
        query.limit ?? 20,
        query.skip ?? 0,
        resolveTrashedMode({
          withTrashed: query.withTrashed,
          onlyTrashed: query.onlyTrashed,
        }),
        {
          search: query.search,
          range: resolveDateRange({
            start_date: query.start_date,
            end_date: query.end_date,
          }),
          type: query.type,
          status: query.status,
          clientId: query.clientId,
        },
      ),
    );
    return {
      data: result.data,
      total: result.total,
    };
  }

  @Get('export')
  @SkipCache()
  @Throttle({ default: { limit: 5, ttl: 60_000 } })
  @ApiOkResponse({
    description: 'Binary export file',
    content: { 'application/octet-stream': {} },
  })
  @ApiBadRequestResponse({ description: 'Validation failed' })
  @ApiForbiddenResponse()
  @CheckAbilities({ action: Action.Export, subject: 'PRODUCT' })
  async export(
    @CurrentUser() user: AuthenticatedUser,
    @Query(new ZodValidationPipe(ExportProductsQuerySchema))
    query: ExportProductsQueryDto,
    @Res({ passthrough: true }) res: Response,
  ): Promise<StreamableFile> {
    await this.assertCanViewTombstones(user, query);
    const result: ExportResult = await this.queryBus.execute(
      new ExportProductsQuery(
        user.id,
        query.format,
        resolveTrashedMode({
          withTrashed: query.withTrashed,
          onlyTrashed: query.onlyTrashed,
        }),
        {
          search: query.search,
          range: resolveDateRange({
            start_date: query.start_date,
            end_date: query.end_date,
          }),
          type: query.type,
          status: query.status,
          clientId: query.clientId,
        },
      ),
    );
    res.set({
      'Content-Type': result.contentType,
      'Content-Disposition': `attachment; filename="${result.filename}"`,
    });
    return new StreamableFile(result.buffer);
  }

  @Post('bulk-delete')
  @HttpCode(200)
  @SkipCache()
  @Throttle({ default: { limit: 5, ttl: 60_000 } })
  @ApiOkResponse({ description: '{ count }' })
  @ApiBadRequestResponse({ description: 'Validation failed' })
  @CheckAbilities({ action: Action.Delete, subject: 'PRODUCT' })
  async bulkDelete(
    @CurrentUser() user: AuthenticatedUser,
    @Body(new ZodValidationPipe(BulkIdsSchema)) dto: BulkIdsDto,
  ): Promise<{ count: number }> {
    return this.commandBus.execute(
      new BulkDeleteProductsCommand(user.id, dto.ids),
    );
  }

  @Post('bulk-restore')
  @HttpCode(200)
  @SkipCache()
  @Throttle({ default: { limit: 5, ttl: 60_000 } })
  @ApiOkResponse({ description: '{ count }' })
  @ApiBadRequestResponse({ description: 'Validation failed' })
  @CheckAbilities({ action: Action.Restore, subject: 'PRODUCT' })
  async bulkRestore(
    @CurrentUser() user: AuthenticatedUser,
    @Body(new ZodValidationPipe(BulkIdsSchema)) dto: BulkIdsDto,
  ): Promise<{ count: number }> {
    return this.commandBus.execute(
      new BulkRestoreProductsCommand(user.id, dto.ids),
    );
  }

  @Get(':id')
  @ApiOkResponse({ type: ProductResponse })
  @ApiNotFoundResponse()
  @ApiParam({ name: 'id', type: String, format: 'uuid' })
  @CacheTTL(TTL_SECONDS.MEDIUM)
  @CheckAbilities({ action: Action.Read, subject: 'PRODUCT' })
  async findOne(
    @CurrentUser() user: AuthenticatedUser,
    @Param('id', ParseUUIDPipe) id: string,
    @Query(new ZodValidationPipe(GetProductQuerySchema))
    query: GetProductQueryDto,
  ): Promise<ProductResponse> {
    return this.queryBus.execute(
      new GetProductByIdQuery(user.id, id, query.withTrashed === true),
    );
  }

  @Patch(':id')
  @Throttle({ default: { limit: 20, ttl: 60_000 } })
  @ApiOkResponse({ type: ProductResponse })
  @ApiNotFoundResponse()
  @ApiBadRequestResponse({ description: 'Validation failed' })
  @ApiParam({ name: 'id', type: String, format: 'uuid' })
  @CheckAbilities({ action: Action.Update, subject: 'PRODUCT' })
  async update(
    @CurrentUser() user: AuthenticatedUser,
    @Param('id', ParseUUIDPipe) id: string,
    @Body(new ZodValidationPipe(UpdateProductSchema)) dto: UpdateProductDto,
  ): Promise<ProductResponse> {
    return this.commandBus.execute(
      new UpdateProductCommand(user.id, id, dto),
    );
  }

  @Delete(':id')
  @HttpCode(204)
  @Throttle({ default: { limit: 20, ttl: 60_000 } })
  @ApiNoContentResponse()
  @ApiNotFoundResponse()
  @ApiParam({ name: 'id', type: String, format: 'uuid' })
  @CheckAbilities({ action: Action.Delete, subject: 'PRODUCT' })
  async remove(
    @CurrentUser() user: AuthenticatedUser,
    @Param('id', ParseUUIDPipe) id: string,
  ): Promise<void> {
    await this.commandBus.execute(new DeleteProductCommand(user.id, id));
  }

  @Post(':id/restore')
  @HttpCode(200)
  @SkipCache()
  @ApiOkResponse({ type: ProductResponse })
  @ApiNotFoundResponse()
  @ApiParam({ name: 'id', type: String, format: 'uuid' })
  @CheckAbilities({ action: Action.Restore, subject: 'PRODUCT' })
  async restore(
    @CurrentUser() user: AuthenticatedUser,
    @Param('id', ParseUUIDPipe) id: string,
  ): Promise<ProductResponse> {
    return this.commandBus.execute(new RestoreProductCommand(user.id, id));
  }

  @Post(':id/generate-content')
  @HttpCode(202)
  @SkipCache()
  @Throttle({ productContentGenerate: { limit: 5, ttl: 60_000 } })
  @ApiAcceptedResponse({ description: 'Generation accepted' })
  @ApiConflictResponse({ description: 'Generation already in flight' })
  @ApiBadRequestResponse({ description: 'Validation failed' })
  @ApiNotFoundResponse()
  @ApiParam({ name: 'id', type: String, format: 'uuid' })
  @CheckAbilities({ action: Action.Export, subject: 'PRODUCT' })
  async generateContent(
    @CurrentUser() user: AuthenticatedUser,
    @Param('id', ParseUUIDPipe) id: string,
    @Body(new ZodValidationPipe(GenerateContentSchema)) dto: GenerateContentDto,
  ): Promise<GenerateContentResponse> {
    return this.commandBus.execute(
      new StartContentGenerationCommand(
        user.id,
        id,
        dto.markdown,
        dto.mode,
        dto.forceReplace,
      ),
    );
  }

  @Get(':id/generations/:generationId')
  @ApiOkResponse({ type: ContentGenerationStatusResponse })
  @ApiNotFoundResponse()
  @ApiParam({ name: 'id', type: String, format: 'uuid' })
  @ApiParam({ name: 'generationId', type: String, format: 'uuid' })
  @CacheTTL(TTL_SECONDS.SHORT)
  @CheckAbilities({ action: Action.Read, subject: 'PRODUCT' })
  async getGenerationStatus(
    @CurrentUser() user: AuthenticatedUser,
    @Param('id', ParseUUIDPipe) id: string,
    @Param('generationId', ParseUUIDPipe) generationId: string,
  ): Promise<ContentGenerationStatusResponse> {
    const generation = await this.queryBus.execute(
      new GetContentGenerationStatusQuery(user.id, id, generationId),
    );
    return this.toGenerationResponse(generation);
  }

  private async assertCanViewTombstones(
    actor: AuthenticatedUser,
    query: { onlyTrashed?: boolean },
  ): Promise<void> {
    if (!query.onlyTrashed) return;
    const ability = await this.abilityFactory.createForUser(actor);
    if (!ability.can(Action.Restore, 'PRODUCT')) {
      throw new ForbiddenException(
        'Viewing suspended products requires the Restore PRODUCT ability',
      );
    }
  }
}
