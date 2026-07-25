import {
  Body,
  Controller,
  Get,
  HttpCode,
  Param,
  ParseUUIDPipe,
  Post,
  UseGuards,
} from '@nestjs/common';
import { ZodValidationPipe } from 'nestjs-zod';
import { Throttle } from '@nestjs/throttler';
import {
  ApiAcceptedResponse,
  ApiBadRequestResponse,
  ApiBearerAuth,
  ApiOkResponse,
  ApiOperation,
  ApiParam,
  ApiTags,
} from '@nestjs/swagger';
import { JwtAuthGuard } from '../../core/guards/jwt-auth.guard';
import { CaslGuard } from '../../core/guards/casl.guard';
import { CheckAbilities } from '../../core/decorators/check-abilities.decorator';
import { CurrentUser } from '../../core/decorators/current-user.decorator';
import { Action } from '../../core/access/actions.enum';
import type { AuthenticatedUser } from '../../core/access/actions.enum';
import { SkipCache } from '../../core/decorators/skip-cache.decorator';
import { VideoExportService } from './video-export.service';
import { ExportRequestSchema } from './dto/export-request.dto';
import type { ExportRequestDto } from './dto/export-request.dto';
import { MergeExportRequestSchema } from './dto/merge-export-request.dto';
import type { MergeExportRequestDto } from './dto/merge-export-request.dto';
import {
  PresignUploadResponseDto,
  PresignUploadSchema,
} from './dto/presign-upload.dto';
import type { PresignUploadDto } from './dto/presign-upload.dto';
import {
  EnqueueResponseDto,
  JobStatusResponseDto,
} from './dto/video-export.response.dto';
import type {
  EnqueueResponse,
  JobStatusResponse,
  PresignUploadResult,
} from './video-export.types';

/**
 * Endpoints mirror the reference FastAPI module's paths. Both POSTs are async:
 * they enqueue a BullMQ job and return 202 immediately; the client polls
 * GET /video-export/jobs/:job_uuid for the final result (same JSON shape).
 */
@ApiTags('Video Export')
@ApiBearerAuth()
@Controller()
@UseGuards(JwtAuthGuard, CaslGuard)
export class VideoExportController {
  constructor(private readonly service: VideoExportService) {}

  @Post('video-export/uploads/presign')
  @HttpCode(200)
  @SkipCache()
  @Throttle({ default: { limit: 60, ttl: 60_000 } })
  @ApiOperation({
    summary:
      'Presigned direct-to-R2 upload URL for one source video part. The browser PUTs the file to R2; the returned public_url is then sent in video_paths.',
  })
  @ApiOkResponse({ type: PresignUploadResponseDto })
  @ApiBadRequestResponse({ description: 'Validation failed' })
  @CheckAbilities({ action: Action.Create, subject: 'VIDEO_EXPORT' })
  async presignUpload(
    @CurrentUser() user: AuthenticatedUser,
    @Body(new ZodValidationPipe(PresignUploadSchema)) dto: PresignUploadDto,
  ): Promise<PresignUploadResult> {
    return this.service.createUploadUrl(user.id, dto);
  }

  @Post('video-export')
  @HttpCode(202)
  @SkipCache()
  @Throttle({ default: { limit: 5, ttl: 60_000 } })
  @ApiOperation({
    summary: 'Merge + silence/filler cleaning (async). Returns a job to poll.',
  })
  @ApiAcceptedResponse({ type: EnqueueResponseDto })
  @ApiBadRequestResponse({ description: 'Validation failed' })
  @CheckAbilities({ action: Action.Create, subject: 'VIDEO_EXPORT' })
  async createExport(
    @CurrentUser() user: AuthenticatedUser,
    @Body(new ZodValidationPipe(ExportRequestSchema)) dto: ExportRequestDto,
  ): Promise<EnqueueResponse> {
    return this.service.enqueueClean(user.id, dto);
  }

  @Post('video-export-merge')
  @HttpCode(202)
  @SkipCache()
  @Throttle({ default: { limit: 5, ttl: 60_000 } })
  @ApiOperation({
    summary: 'Merge-only HD render (async). Returns a job to poll.',
  })
  @ApiAcceptedResponse({ type: EnqueueResponseDto })
  @ApiBadRequestResponse({ description: 'Validation failed' })
  @CheckAbilities({ action: Action.Create, subject: 'VIDEO_EXPORT' })
  async createMergeExport(
    @CurrentUser() user: AuthenticatedUser,
    @Body(new ZodValidationPipe(MergeExportRequestSchema))
    dto: MergeExportRequestDto,
  ): Promise<EnqueueResponse> {
    return this.service.enqueueMerge(user.id, dto);
  }

  @Get('video-export/jobs/:job_uuid')
  @SkipCache()
  @ApiOperation({
    summary:
      'Poll a video-export job. On completion returns the final JSON (storage_url, diagnostics).',
  })
  @ApiParam({ name: 'job_uuid', type: String, format: 'uuid' })
  @ApiOkResponse({ type: JobStatusResponseDto })
  @CheckAbilities({ action: Action.Read, subject: 'VIDEO_EXPORT' })
  async getJob(
    @CurrentUser() user: AuthenticatedUser,
    @Param('job_uuid', ParseUUIDPipe) jobUuid: string,
  ): Promise<JobStatusResponse> {
    return this.service.getJobStatus(jobUuid, user);
  }
}
