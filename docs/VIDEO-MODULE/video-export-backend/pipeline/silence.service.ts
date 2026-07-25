import { Injectable } from '@nestjs/common';
import { LoggerService } from '../../../logger/logger.service';
import { FfmpegService } from './ffmpeg.service';
import { SILENCE_NOISE_DB } from '../video-export.constants';
import type { TimeRange } from '../video-export.types';

/**
 * Silence detection via ffmpeg's built-in `silencedetect` filter — no ML model
 * required (the reference module used Silero VAD; ffmpeg is enough for the
 * "remove silences ≥ N seconds" use case and keeps the worker pure-Node).
 *
 * Returns the silence intervals as CUT ranges (the cut-planner later inverts
 * them into keep ranges).
 */
@Injectable()
export class SilenceService {
  constructor(
    private readonly ffmpeg: FfmpegService,
    private readonly logger: LoggerService,
  ) {
    this.logger.setContext(SilenceService.name);
  }

  async detectSilenceCuts(
    videoPath: string,
    thresholdSeconds: number,
    durationSeconds: number,
  ): Promise<TimeRange[]> {
    const stderr = await this.ffmpeg.run(
      [
        '-hide_banner',
        '-nostats',
        '-i',
        videoPath,
        '-af',
        `silencedetect=noise=${SILENCE_NOISE_DB}:d=${thresholdSeconds}`,
        '-f',
        'null',
        '-',
      ],
      'detect silence',
    );

    const cuts = this.parse(stderr, durationSeconds);
    this.logger.info('SilenceService.detectSilenceCuts', {
      thresholdSeconds,
      cuts: cuts.length,
    });
    return cuts;
  }

  /** Parse the `silence_start` / `silence_end` lines emitted on stderr. */
  private parse(stderr: string, durationSeconds: number): TimeRange[] {
    const cuts: TimeRange[] = [];
    let openStart: number | null = null;

    for (const line of stderr.split('\n')) {
      const startMatch = line.match(/silence_start:\s*(-?\d+(?:\.\d+)?)/);
      if (startMatch) {
        openStart = Math.max(0, Number.parseFloat(startMatch[1]));
        continue;
      }
      const endMatch = line.match(/silence_end:\s*(-?\d+(?:\.\d+)?)/);
      if (endMatch && openStart !== null) {
        const end = Number.parseFloat(endMatch[1]);
        if (end > openStart) cuts.push({ start: openStart, end });
        openStart = null;
      }
    }

    // A silence still open at EOF runs to the end of the media.
    if (openStart !== null && durationSeconds > openStart) {
      cuts.push({ start: openStart, end: durationSeconds });
    }
    return cuts;
  }
}
