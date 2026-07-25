import { Module } from '@nestjs/common';
import { BullModule } from '@nestjs/bullmq';
import { StorageModule } from '../../shared/storage/storage.module';
import { QUEUE_NAMES } from '../../shared/messaging/queues.constants';
import { VideoExportController } from './video-export.controller';
import { VideoExportService } from './video-export.service';
import { VideoExportProcessor } from './video-export.processor';
import { FfmpegService } from './pipeline/ffmpeg.service';
import { SilenceService } from './pipeline/silence.service';
import { TranscriptionService } from './pipeline/transcription.service';
import { FillerService } from './pipeline/filler.service';
import { CutPlannerService } from './pipeline/cut-planner.service';
import { AudioEnhanceService } from './pipeline/audio-enhance.service';
import { InputResolverService } from './pipeline/input-resolver.service';
import { R2VideoStorageService } from './pipeline/r2-video-storage.service';
import { WorkspaceService } from './pipeline/workspace.service';
import { ScriptReviewService } from './pipeline/script-review.service';
import { ReviewPdfService } from './pipeline/review-pdf.service';

/**
 * Video-export module — orchestration only, NO database tables.
 *
 * Controller → Service (enqueue) → BullMQ VIDEO_EXPORT queue → Processor →
 * ffmpeg/Whisper/R2 pipeline. AUDIT_PORT, LoggerService and ConfigService come
 * from their global modules; StorageModule provides the R2 client.
 *
 * Runtime requirements: ffmpeg + ffprobe on PATH, Redis (BullMQ), R2 env, and
 * OPENAI_API_KEY when callers use ai_cleaning_enabled.
 */
@Module({
  imports: [
    StorageModule,
    BullModule.registerQueue({ name: QUEUE_NAMES.VIDEO_EXPORT }),
  ],
  controllers: [VideoExportController],
  providers: [
    VideoExportService,
    VideoExportProcessor,
    // Pipeline
    FfmpegService,
    SilenceService,
    TranscriptionService,
    FillerService,
    CutPlannerService,
    AudioEnhanceService,
    InputResolverService,
    R2VideoStorageService,
    WorkspaceService,
    ScriptReviewService,
    ReviewPdfService,
  ],
})
export class VideoExportModule {}
