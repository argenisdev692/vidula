import { Injectable } from '@nestjs/common';
import { MIN_SEGMENT_SECONDS } from '../video-export.constants';
import type { TimeRange } from '../video-export.types';

/**
 * Turns the union of all cut ranges (silence + filler + stutter + pause) into
 * the complementary KEEP ranges that the renderer trims-and-concats.
 * Ported from the reference `_invert_cuts`.
 */
@Injectable()
export class CutPlannerService {
  invertCuts(cuts: TimeRange[], durationSeconds: number): TimeRange[] {
    const normalized = cuts
      .map((c) => ({
        start: Math.max(0, c.start),
        end: Math.min(durationSeconds, c.end),
      }))
      .filter((c) => c.end - c.start >= MIN_SEGMENT_SECONDS)
      .sort((a, b) => a.start - b.start);

    const keep: TimeRange[] = [];
    let cursor = 0;
    for (const cut of normalized) {
      if (cut.start - cursor >= MIN_SEGMENT_SECONDS) {
        keep.push({ start: cursor, end: cut.start });
      }
      cursor = Math.max(cursor, cut.end);
    }
    if (durationSeconds - cursor >= MIN_SEGMENT_SECONDS) {
      keep.push({ start: cursor, end: durationSeconds });
    }

    // Never produce an empty timeline — fall back to the full clip.
    if (keep.length === 0) return [{ start: 0, end: durationSeconds }];
    return keep;
  }

  /** Total kept duration in seconds (rounded to ms). */
  totalDuration(keep: TimeRange[]): number {
    const total = keep.reduce((sum, r) => sum + (r.end - r.start), 0);
    return Math.round(total * 1000) / 1000;
  }
}
