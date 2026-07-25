import { Injectable, inject } from '@angular/core';
import { VideoExportService } from '../../../api/services/video-export.service';
import { Function as ApiExportBody } from '../../../api/models/function';
import { PresignUploadResponseDto } from '../../../api/models/presign-upload-response-dto';
import {
  EnqueueResponseDto,
  JobStatusResponseDto,
  VideoExportRequest,
} from '../models/video-export.types';

@Injectable({ providedIn: 'root' })
export class VideoExportFeatureService {
  private api = inject(VideoExportService);

  /**
   * Step 1 of the direct-to-R2 upload: ask the API for a short-lived presigned
   * PUT URL for this file. The bytes do NOT flow through the backend. The
   * request goes through the generated client (and the app auth interceptor).
   */
  presignUpload(file: File): Promise<PresignUploadResponseDto> {
    const body = {
      filename: file.name,
      content_type: file.type || 'video/mp4',
      size_bytes: file.size,
    };
    return this.api.videoExportControllerPresignUpload({ body });
  }

  /**
   * Step 2: PUT the file straight to R2 using the presigned URL.
   *
   * Uses a raw XMLHttpRequest (not Angular HttpClient) on purpose:
   *  - bypasses the app's auth interceptor — an Authorization header would break
   *    the SigV4 signature and R2 would reject the upload;
   *  - exposes real upload progress for the dropzone UI.
   *
   * The `Content-Type` MUST match the one the URL was signed with.
   */
  uploadToR2(
    file: File,
    uploadUrl: string,
    onProgress?: (percent: number) => void,
  ): Promise<void> {
    return new Promise<void>((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhr.open('PUT', uploadUrl, true);
      xhr.setRequestHeader('Content-Type', file.type || 'video/mp4');

      xhr.upload.onprogress = (event) => {
        if (event.lengthComputable && onProgress) {
          onProgress(Math.round((event.loaded / event.total) * 100));
        }
      };
      xhr.onload = () => {
        if (xhr.status >= 200 && xhr.status < 300) {
          resolve();
        } else {
          reject(new Error(`R2 upload failed (HTTP ${xhr.status})`));
        }
      };
      xhr.onerror = () =>
        reject(new Error('Network error while uploading to storage'));
      xhr.send(file);
    });
  }

  /** Merge + silence/filler AI cleaning (async). Returns a job to poll. */
  createExport(request: VideoExportRequest): Promise<EnqueueResponseDto> {
    return this.api.videoExportControllerCreateExport({
      body: this.toApiBody(request),
    });
  }

  /** Merge-only HD render (async). Returns a job to poll. */
  createMergeExport(request: VideoExportRequest): Promise<EnqueueResponseDto> {
    return this.api.videoExportControllerCreateMergeExport({
      body: this.toApiBody(request),
    });
  }

  /** Poll a single video-export job by its UUID. */
  getJob(jobUuid: string): Promise<JobStatusResponseDto> {
    return this.api.videoExportControllerGetJob({ job_uuid: jobUuid });
  }

  /**
   * Adapts the typed feature request to the generated (untyped) API body.
   * The OpenAPI schema for the body is an empty placeholder, so the cast is
   * isolated here to keep the rest of the feature fully typed.
   */
  private toApiBody(request: VideoExportRequest): ApiExportBody {
    return {
      video_paths: request.video_paths,
      job_uuid: request.job_uuid,
      ai_cleaning_enabled: request.aiCleaning ?? false,
      audio_enhanced: request.audioEnhance ?? false,
      script_reviewed: request.scriptReview ?? false,
      script_path: request.script_path,
      script_format: request.script_format,
    } as ApiExportBody;
  }
}
