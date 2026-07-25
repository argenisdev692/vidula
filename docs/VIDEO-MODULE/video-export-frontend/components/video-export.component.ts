import {
  ChangeDetectionStrategy,
  Component,
  DestroyRef,
  computed,
  inject,
  signal,
} from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';

import { VideoExportFeatureService } from '../services/video-export-feature.service';
import {
  ExportMode,
  JobStatus,
  JobStatusResponseDto,
  UploadItem,
  VideoExportRequest,
  isTerminalStatus,
} from '../models/video-export.types';
import { PageHeaderComponent } from '../../../components/page-header/page-header.component';
import { SidebarComponent } from '../../../components/sidebar/sidebar.component';
import { extractApiErrorMessage } from '../../../shared/api-error';
import { NotificationService } from '../../../shared/notification.service';

const POLL_INTERVAL_MS = 2500;

@Component({
  selector: 'app-video-export',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    ReactiveFormsModule,
    PageHeaderComponent,
    SidebarComponent,
  ],
  templateUrl: './video-export.component.html',
  styleUrl: './video-export.component.css',
})
export class VideoExportComponent {
  private fb = inject(FormBuilder);
  private service = inject(VideoExportFeatureService);
  private notify = inject(NotificationService);
  private destroyRef = inject(DestroyRef);

  readonly drawerVisible = signal(false);

  // ── Submission state ──
  readonly submitting = signal<ExportMode | null>(null);

  // ── Job tracking state ──
  readonly jobUuid = signal<string | null>(null);
  readonly job = signal<JobStatusResponseDto | null>(null);
  readonly polling = signal(false);
  private pollHandle: ReturnType<typeof setInterval> | null = null;

  readonly status = computed<JobStatus | null>(() => this.job()?.status ?? null);
  readonly result = computed(() => this.job()?.result ?? null);
  readonly diagnostics = computed(() => this.result()?.diagnostics ?? null);
  readonly jobError = computed(() => this.job()?.error ?? null);

  readonly statusLabels: Record<JobStatus, string> = {
    queued: 'Queued',
    active: 'Processing',
    completed: 'Completed',
    failed: 'Failed',
    delayed: 'Delayed',
    not_found: 'Not found',
  };

  readonly form = this.fb.group({
    aiCleaning: [true],
    audioEnhance: [true],
    scriptReview: [false],
  });

  // ── Dropzone state ──
  readonly files = signal<UploadItem[]>([]);
  readonly isDragOver = signal(false);
  readonly uploading = signal(false);
  readonly hasFiles = computed(() => this.files().length > 0);

  // ── Script upload state ──
  readonly scriptFile = signal<File | null>(null);
  readonly scriptUploading = signal(false);
  readonly scriptUploaded = signal(false);
  readonly scriptUrl = signal<string | null>(null);
  readonly scriptFormat = signal<'markdown' | 'pdf' | null>(null);
  readonly showScriptUpload = computed(() => this.form.value.scriptReview === true);

  constructor() {
    this.destroyRef.onDestroy(() => this.stopPolling());
  }

  // ── Dropzone handlers ──
  onFilesSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files) this.addFiles(Array.from(input.files));
    input.value = '';
  }

  onDrop(event: DragEvent): void {
    event.preventDefault();
    this.isDragOver.set(false);
    const dropped = event.dataTransfer?.files;
    if (dropped) this.addFiles(Array.from(dropped));
  }

  onDragOver(event: DragEvent): void {
    event.preventDefault();
    this.isDragOver.set(true);
  }

  onDragLeave(event: DragEvent): void {
    event.preventDefault();
    this.isDragOver.set(false);
  }

  removeFile(index: number): void {
    if (this.uploading()) return;
    this.files.update((items) => items.filter((_, i) => i !== index));
  }

  private addFiles(incoming: File[]): void {
    const videos = incoming.filter((f) => f.type.startsWith('video/'));
    const rejected = incoming.length - videos.length;
    if (rejected > 0) {
      this.notify.warn(
        `${rejected} non-video file(s) were ignored.`,
        'Unsupported files skipped'
      );
    }
    if (videos.length === 0) return;
    const items: UploadItem[] = videos.map((file) => ({
      file,
      status: 'pending',
      progress: 0,
    }));
    this.files.update((current) => [...current, ...items]);
  }

  // ── Script upload handlers ──
  onScriptSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
      const file = input.files[0];
      const isPdf = file.type === 'application/pdf' || file.name.endsWith('.pdf');
      const isMarkdown = file.name.endsWith('.md') || file.name.endsWith('.markdown');

      if (!isPdf && !isMarkdown) {
        this.notify.warn(
          'Please upload a PDF or Markdown file.',
          'Invalid script format'
        );
        input.value = '';
        return;
      }

      this.scriptFile.set(file);
      this.scriptFormat.set(isPdf ? 'pdf' : 'markdown');
      this.scriptUploaded.set(false);
      this.scriptUrl.set(null);
    }
    input.value = '';
  }

  removeScript(): void {
    if (this.scriptUploading()) return;
    this.scriptFile.set(null);
    this.scriptUploaded.set(false);
    this.scriptUrl.set(null);
    this.scriptFormat.set(null);
  }

  statusLabel(status: JobStatus): string {
    return this.statusLabels[status] ?? status;
  }

  diagnosticEntries(): { key: string; value: string }[] {
    const diag = this.diagnostics();
    if (!diag) return [];
    return Object.entries(diag).map(([key, value]) => ({
      key: this.humanize(key),
      value: this.formatValue(value),
    }));
  }

  // ── Submit ──
  async onSubmit(mode: ExportMode): Promise<void> {
    if (this.form.invalid || this.submitting() || this.uploading()) {
      this.form.markAllAsTouched();
      return;
    }

    if (!this.hasFiles()) {
      this.notify.warn('Add at least one source video file.', 'No videos');
      return;
    }

    this.submitting.set(mode);
    this.resetJob();

    try {
      // Phase 1 — upload every pending part directly to R2 and collect its URL.
      const videoPaths = await this.uploadAllParts();

      // Phase 2 — upload script if provided and scriptReview is enabled.
      let scriptPath: string | undefined;
      let scriptFormat: 'markdown' | 'pdf' | undefined;
      if (this.form.value.scriptReview && this.scriptFile() && !this.scriptUploaded()) {
        await this.uploadScript();
        scriptPath = this.scriptUrl() ?? undefined;
        scriptFormat = this.scriptFormat() ?? undefined;
      } else if (this.form.value.scriptReview && this.scriptUploaded()) {
        scriptPath = this.scriptUrl() ?? undefined;
        scriptFormat = this.scriptFormat() ?? undefined;
      }

      // Phase 3 — enqueue the export job using the resulting public URLs.
      const jobUuid = crypto.randomUUID();
      const request: VideoExportRequest = {
        video_paths: videoPaths,
        job_uuid: jobUuid,
        script_path: scriptPath,
        script_format: scriptFormat,
        ...this.buildOptions(),
      };
      const res =
        mode === 'full'
          ? await this.service.createExport(request)
          : await this.service.createMergeExport(request);

      const detail = res.detail ?? `Tracking job ${res.job_uuid}.`;
      if (res.status === 'duplicate') this.notify.info(detail, 'Duplicate job');
      else this.notify.success(detail, 'Job queued');
      this.jobUuid.set(res.job_uuid);
      this.startPolling(res.job_uuid);
    } catch (err) {
      this.notify.error(err);
    } finally {
      this.submitting.set(null);
    }
  }

  /**
   * Uploads all parts that are not yet `done`, in array order (so the merge
   * order matches the list), and returns their public R2 URLs. Throws if any
   * upload fails so the caller aborts before enqueuing an incomplete job.
   */
  private async uploadAllParts(): Promise<string[]> {
    this.uploading.set(true);
    try {
      const items = this.files();
      for (let i = 0; i < items.length; i++) {
        if (items[i].status === 'done' && items[i].publicUrl) continue;
        await this.uploadOne(i);
      }
      const done = this.files();
      const urls = done
        .map((item) => item.publicUrl)
        .filter((url): url is string => !!url);
      if (urls.length !== done.length) {
        throw new Error('Some parts failed to upload. Retry before exporting.');
      }
      return urls;
    } finally {
      this.uploading.set(false);
    }
  }

  private async uploadOne(index: number): Promise<void> {
    const file = this.files()[index].file;
    this.patchItem(index, { status: 'uploading', progress: 0, error: undefined });
    try {
      const slot = await this.service.presignUpload(file);
      await this.service.uploadToR2(file, slot.upload_url, (percent) =>
        this.patchItem(index, { progress: percent }),
      );
      this.patchItem(index, {
        status: 'done',
        progress: 100,
        publicUrl: slot.public_url,
      });
    } catch (err) {
      this.patchItem(index, {
        status: 'error',
        error: extractApiErrorMessage(err),
      });
      throw err;
    }
  }

  private patchItem(index: number, patch: Partial<UploadItem>): void {
    this.files.update((items) =>
      items.map((item, i) => (i === index ? { ...item, ...patch } : item)),
    );
  }

  private async uploadScript(): Promise<void> {
    const file = this.scriptFile();
    if (!file) throw new Error('No script file to upload');

    this.scriptUploading.set(true);
    try {
      const slot = await this.service.presignUpload(file);
      await this.service.uploadToR2(file, slot.upload_url);
      this.scriptUrl.set(slot.public_url);
      this.scriptUploaded.set(true);
    } catch (err) {
      this.notify.error(err);
      throw err;
    } finally {
      this.scriptUploading.set(false);
    }
  }

  // ── Polling ──
  private startPolling(jobUuid: string): void {
    this.stopPolling();
    this.polling.set(true);
    this.poll(jobUuid);
    this.pollHandle = setInterval(() => this.poll(jobUuid), POLL_INTERVAL_MS);
  }

  private poll(jobUuid: string): void {
    this.service
      .getJob(jobUuid)
      .then((status) => {
        this.job.set(status);
        if (isTerminalStatus(status.status)) {
          this.stopPolling();
          this.notifyTerminal(status);
        }
      })
      .catch((err) => {
        this.stopPolling();
        this.notify.error(err);
      });
  }

  private stopPolling(): void {
    if (this.pollHandle !== null) {
      clearInterval(this.pollHandle);
      this.pollHandle = null;
    }
    this.polling.set(false);
  }

  refresh(): void {
    const id = this.jobUuid();
    if (id) this.poll(id);
  }

  // ── Helpers ──
  private buildOptions(): Pick<VideoExportRequest, 'aiCleaning' | 'audioEnhance' | 'scriptReview'> {
    const raw = this.form.getRawValue();
    return {
      aiCleaning: !!raw.aiCleaning,
      audioEnhance: !!raw.audioEnhance,
      scriptReview: !!raw.scriptReview,
    };
  }

  private resetJob(): void {
    this.stopPolling();
    this.jobUuid.set(null);
    this.job.set(null);
  }

  private notifyTerminal(status: JobStatusResponseDto): void {
    if (status.status === 'completed') {
      this.notify.success('Your video export has finished.', 'Export ready');
    } else if (status.status === 'failed') {
      this.notify.error(status.error ?? 'The export job failed.', 'Export failed');
    }
  }

  private humanize(key: string): string {
    return key
      .replace(/_/g, ' ')
      .replace(/\b\w/g, (c) => c.toUpperCase());
  }

  private formatValue(value: unknown): string {
    if (value === null || value === undefined) return '—';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (Array.isArray(value)) return value.length ? value.join(', ') : '—';
    return String(value);
  }
}
