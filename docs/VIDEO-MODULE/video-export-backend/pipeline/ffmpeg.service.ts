import { Injectable } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { spawn } from 'node:child_process';
import { LoggerService } from '../../../logger/logger.service';
import {
  MERGE_INTERMEDIATE,
  RENDER_SPEC,
  WHISPER_AUDIO,
} from '../video-export.constants';
import type { TimeRange } from '../video-export.types';

/**
 * Thin wrapper around the `ffmpeg` / `ffprobe` binaries (invoked via
 * `child_process`, same approach as the reference FastAPI module — there is no
 * suitable pure-Node encoder). The runtime image MUST provide both binaries on
 * PATH (or via FFMPEG_BIN / FFPROBE_BIN).
 *
 * All commands run with `spawn` (no shell) so user-derived paths cannot inject
 * shell metacharacters (OWASP #3 Injection).
 */
@Injectable()
export class FfmpegService {
  private readonly ffmpegBin: string;
  private readonly ffprobeBin: string;

  constructor(
    config: ConfigService,
    private readonly logger: LoggerService,
  ) {
    this.logger.setContext(FfmpegService.name);
    this.ffmpegBin = config.get<string>('FFMPEG_BIN', 'ffmpeg');
    this.ffprobeBin = config.get<string>('FFPROBE_BIN', 'ffprobe');
  }

  /** Merge multiple parts into a near-lossless intermediate used for trimming. */
  async mergeVideos(inputs: string[], outPath: string): Promise<void> {
    const filterParts: string[] = [];
    const concatLabels: string[] = [];
    inputs.forEach((_, i) => {
      filterParts.push(
        `[${i}:v]${this.scalePadFilter()}[v${i}]`,
        `[${i}:a]aformat=sample_rates=${RENDER_SPEC.audioSampleRate}:channel_layouts=stereo[a${i}]`,
      );
      concatLabels.push(`[v${i}][a${i}]`);
    });
    filterParts.push(
      `${concatLabels.join('')}concat=n=${inputs.length}:v=1:a=1[outv][outa]`,
    );

    const args = ['-y'];
    for (const input of inputs) args.push('-i', input);
    args.push(
      '-filter_complex',
      filterParts.join(';'),
      '-map',
      '[outv]',
      '-map',
      '[outa]',
      '-c:v',
      'libx264',
      '-preset',
      MERGE_INTERMEDIATE.preset,
      '-crf',
      String(MERGE_INTERMEDIATE.crf),
      '-pix_fmt',
      RENDER_SPEC.pixelFormat,
      '-c:a',
      'aac',
      '-b:a',
      MERGE_INTERMEDIATE.audioBitrate,
      '-movflags',
      '+faststart',
      outPath,
    );
    await this.run(args, 'merge videos');
  }

  /**
   * Render the final HD MP4 from `videoPath`, keeping only `keepRanges`.
   * When `audioDspChain` is provided it is applied to the concatenated audio
   * before the final format step (studio-sound / noise removal).
   */
  async render(
    videoPath: string,
    keepRanges: TimeRange[],
    outPath: string,
    audioDspChain?: string,
  ): Promise<void> {
    const filterParts: string[] = [];
    const concatLabels: string[] = [];
    keepRanges.forEach((range, i) => {
      filterParts.push(
        `[0:v]trim=start=${range.start}:end=${range.end},setpts=PTS-STARTPTS[v${i}]`,
        `[0:a]atrim=start=${range.start}:end=${range.end},asetpts=PTS-STARTPTS[a${i}]`,
      );
      concatLabels.push(`[v${i}][a${i}]`);
    });
    filterParts.push(
      `${concatLabels.join('')}concat=n=${keepRanges.length}:v=1:a=1[cv][ca]`,
    );
    // Aspect-preserving normalization to 1920x1080 (idempotent for clips already at spec).
    filterParts.push(`[cv]${this.scalePadFilter()}[outv]`);
    const audioFormat = `aformat=sample_rates=${RENDER_SPEC.audioSampleRate}:channel_layouts=stereo`;
    filterParts.push(
      audioDspChain
        ? `[ca]${audioDspChain},${audioFormat}[outa]`
        : `[ca]${audioFormat}[outa]`,
    );

    const args = [
      '-y',
      '-i',
      videoPath,
      '-filter_complex',
      filterParts.join(';'),
      '-map',
      '[outv]',
      '-map',
      '[outa]',
      ...this.hdEncodeFlags(),
      outPath,
    ];
    await this.run(args, 'render export');
  }

  /** Merge-only HD render (single or multi input), no cleaning. */
  async mergeAndRender(inputs: string[], outPath: string): Promise<void> {
    const args = ['-y'];
    if (inputs.length === 1) {
      args.push(
        '-i',
        inputs[0],
        '-vf',
        this.scalePadFilter(),
        '-af',
        `aformat=sample_rates=${RENDER_SPEC.audioSampleRate}:channel_layouts=stereo`,
        ...this.hdEncodeFlags(),
        outPath,
      );
    } else {
      const filterParts: string[] = [];
      const concatLabels: string[] = [];
      inputs.forEach((_, i) => {
        filterParts.push(
          `[${i}:v]${this.scalePadFilter()}[v${i}]`,
          `[${i}:a]aformat=sample_rates=${RENDER_SPEC.audioSampleRate}:channel_layouts=stereo[a${i}]`,
        );
        concatLabels.push(`[v${i}][a${i}]`);
      });
      filterParts.push(
        `${concatLabels.join('')}concat=n=${inputs.length}:v=1:a=1[outv][outa]`,
      );
      for (const input of inputs) args.push('-i', input);
      args.push(
        '-filter_complex',
        filterParts.join(';'),
        '-map',
        '[outv]',
        '-map',
        '[outa]',
        ...this.hdEncodeFlags(),
        outPath,
      );
    }
    await this.run(args, 'merge and render');
  }

  /** Extract a small mono mp3 for Whisper (kept under the API size cap). */
  async extractWhisperAudio(videoPath: string, outPath: string): Promise<void> {
    await this.run(
      [
        '-y',
        '-i',
        videoPath,
        '-vn',
        '-ac',
        String(WHISPER_AUDIO.channels),
        '-ar',
        String(WHISPER_AUDIO.sampleRate),
        '-b:a',
        WHISPER_AUDIO.bitrate,
        outPath,
      ],
      'extract whisper audio',
    );
  }

  /** Total duration in seconds (0 when ffprobe cannot read it). */
  async probeDurationSeconds(path: string): Promise<number> {
    const out = await this.probe([
      '-v',
      'quiet',
      '-show_entries',
      'format=duration',
      '-of',
      'default=noprint_wrappers=1:nokey=1',
      path,
    ]);
    const value = Number.parseFloat(out.trim());
    return Number.isFinite(value) ? value : 0;
  }

  /** Recording timestamp (epoch ms) from container metadata, or null. */
  async probeCreationTime(path: string): Promise<number | null> {
    const out = await this.probe([
      '-v',
      'quiet',
      '-show_entries',
      'format_tags=creation_time',
      '-of',
      'default=noprint_wrappers=1:nokey=1',
      path,
    ]);
    const raw = out.trim();
    if (!raw) return null;
    const ms = Date.parse(raw);
    return Number.isNaN(ms) ? null : ms;
  }

  // ── internals ────────────────────────────────────────────────────────────

  private scalePadFilter(): string {
    const { width, height, fps } = RENDER_SPEC;
    return (
      `scale=${width}:${height}:force_original_aspect_ratio=decrease,` +
      `pad=${width}:${height}:(ow-iw)/2:(oh-ih)/2,setsar=1,fps=${fps}`
    );
  }

  private hdEncodeFlags(): string[] {
    return [
      '-c:v',
      RENDER_SPEC.videoCodec,
      '-preset',
      RENDER_SPEC.preset,
      '-b:v',
      RENDER_SPEC.videoBitrate,
      '-maxrate',
      RENDER_SPEC.videoMaxrate,
      '-bufsize',
      RENDER_SPEC.videoBufsize,
      '-r',
      String(RENDER_SPEC.fps),
      '-pix_fmt',
      RENDER_SPEC.pixelFormat,
      '-c:a',
      RENDER_SPEC.audioCodec,
      '-b:a',
      RENDER_SPEC.audioBitrate,
      '-ar',
      String(RENDER_SPEC.audioSampleRate),
      '-ac',
      String(RENDER_SPEC.audioChannels),
      '-movflags',
      '+faststart',
    ];
  }

  /** Run ffmpeg, capturing the tail of stderr; throws with it on failure. */
  async run(args: string[], action: string): Promise<string> {
    return this.exec(this.ffmpegBin, args, action);
  }

  private async probe(args: string[]): Promise<string> {
    return this.exec(this.ffprobeBin, args, 'ffprobe', true);
  }

  private exec(
    bin: string,
    args: string[],
    action: string,
    captureStdout = false,
  ): Promise<string> {
    return new Promise<string>((resolve, reject) => {
      const child = spawn(bin, args, { windowsHide: true });
      let stdout = '';
      let stderrTail = '';
      const maxTail = 64 * 1024;

      if (captureStdout) {
        child.stdout.on('data', (chunk: Buffer) => {
          stdout += chunk.toString();
        });
      }
      child.stderr.on('data', (chunk: Buffer) => {
        stderrTail = (stderrTail + chunk.toString()).slice(-maxTail);
      });
      child.on('error', (err) => {
        reject(
          new Error(
            `Failed to spawn ${bin} (${action}): ${err.message}. Is it installed and on PATH?`,
          ),
        );
      });
      child.on('close', (code) => {
        if (code === 0) {
          resolve(captureStdout ? stdout : stderrTail);
          return;
        }
        this.logger.error('ffmpeg command failed', { action, code });
        reject(
          new Error(
            `ffmpeg failed to ${action} (exit ${code ?? 'null'}): ${stderrTail.trim().slice(-2000)}`,
          ),
        );
      });
    });
  }
}
