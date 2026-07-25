import { Injectable } from '@nestjs/common';
import { LoggerService } from '../../../logger/logger.service';
import {
  FILLER_TERMS,
  MIN_SEGMENT_SECONDS,
  PAUSE_BACKTRACK,
  PAUSE_KEYWORDS,
  STUTTER,
} from '../video-export.constants';
import type { TimeRange, WordTimestamp } from '../video-export.types';

const FILLER_PATTERNS: readonly RegExp[] = [
  /^e+h+$/,
  /^e+m+$/,
  /^h?m{2,}$/,
  /^u+h+$/,
  /^u+m+$/,
];

const PAUSE_MARKER_WORDS = new Set([
  'pausa',
  'pauza',
  'pauso',
  'pausas',
  'pausar',
  'pause',
  'pousa',
]);

/**
 * Transcript-driven cut detection. Operates on Whisper word timestamps and
 * produces CUT ranges for fillers ("muletillas"), stutters and PAUSA bad takes.
 * Ported from the reference FastAPI `export_service.py`.
 */
@Injectable()
export class FillerService {
  private readonly fillerSet = new Set(
    FILLER_TERMS.map((t) => FillerService.cleanToken(t)).filter(Boolean),
  );

  constructor(private readonly logger: LoggerService) {
    this.logger.setContext(FillerService.name);
  }

  /** Cut every filler word occurrence. */
  findFillerCuts(words: WordTimestamp[]): TimeRange[] {
    const cuts: TimeRange[] = [];
    for (const word of words) {
      const token = FillerService.cleanToken(word.text);
      if (!this.isFiller(token)) continue;
      cuts.push(
        FillerService.expandToMin(
          Math.max(0, word.start - 0.05),
          word.end + 0.05,
        ),
      );
    }
    this.logger.info('FillerService.findFillerCuts', { cuts: cuts.length });
    return cuts;
  }

  /**
   * Cut stuttered repeats ("y y y vamos" → keep last; "va vamos" → drop "va").
   * Conservative: only repeated runs, or a short token completed by the next.
   */
  findStutterCuts(words: WordTimestamp[]): TimeRange[] {
    const tokens = words.map((w) => FillerService.cleanToken(w.text));
    const cuts: TimeRange[] = [];
    let index = 0;
    while (index < tokens.length) {
      const token = tokens[index];
      if (!token || token.length > STUTTER.maxTokenChars) {
        index += 1;
        continue;
      }

      let runEnd = index;
      while (runEnd + 1 < tokens.length) {
        const gap = words[runEnd + 1].start - words[runEnd].end;
        if (gap > STUTTER.maxGapSeconds || tokens[runEnd + 1] !== token) break;
        runEnd += 1;
      }

      if (runEnd - index + 1 >= 2) {
        // Cut every repeat except the last in the run.
        for (let k = index; k < runEnd; k++) {
          cuts.push(FillerService.stutterRange(words[k]));
        }
        // Prefix stutter: "v v vamos" — also cut the last short repeat.
        const next = runEnd + 1;
        if (
          token.length <= 3 &&
          next < tokens.length &&
          tokens[next] !== token &&
          tokens[next].length > token.length &&
          tokens[next].startsWith(token) &&
          words[next].start - words[runEnd].end <= STUTTER.maxGapSeconds
        ) {
          cuts.push(FillerService.stutterRange(words[runEnd]));
        }
        index = runEnd + 1;
        continue;
      }
      index += 1;
    }
    this.logger.info('FillerService.findStutterCuts', { cuts: cuts.length });
    return cuts;
  }

  /**
   * Detect PAUSA / PAUSA ACÁ markers and remove the bad take that precedes them.
   * Walks backwards word-by-word from the keyword until a natural silence (gap
   * >= threshold) marks where the bad take began.
   */
  findPauseCuts(
    words: WordTimestamp[],
    keywords: readonly string[] = PAUSE_KEYWORDS,
  ): TimeRange[] {
    if (words.length === 0) return [];

    const keywordParts = this.normalizeKeywords(keywords);
    const tokens = words.map((w) => FillerService.cleanToken(w.text));
    const cuts: TimeRange[] = [];
    const consumed = new Set<number>();

    for (const parts of keywordParts) {
      const n = parts.length;
      for (let i = 0; i + n <= words.length; i++) {
        if (consumed.has(i)) continue;
        if (!this.keywordMatchesAt(tokens, i, parts)) continue;
        consumed.add(i);

        const keywordEnd = words[i + n - 1].end;
        let cutStart = words[i].start;
        const origin = cutStart;
        let walker = i - 1;
        while (walker >= 0) {
          const gap = words[walker + 1].start - words[walker].end;
          if (gap >= PAUSE_BACKTRACK.silenceThresholdSeconds) break;
          if (origin - words[walker].start > PAUSE_BACKTRACK.maxSeconds) break;
          cutStart = words[walker].start;
          walker -= 1;
        }
        cuts.push({ start: Math.max(0, cutStart), end: keywordEnd });
      }
    }
    this.logger.info('FillerService.findPauseCuts', { cuts: cuts.length });
    return cuts;
  }

  // ── internals ────────────────────────────────────────────────────────────

  private isFiller(token: string): boolean {
    if (!token) return false;
    if (this.fillerSet.has(token)) return true;
    return FILLER_PATTERNS.some((p) => p.test(token));
  }

  private normalizeKeywords(keywords: readonly string[]): string[][] {
    const seen = new Set<string>();
    const result: string[][] = [];
    for (const kw of keywords) {
      const parts = FillerService.normalize(kw)
        .trim()
        .split(/\s+/)
        .map((p) => FillerService.cleanToken(p))
        .filter(Boolean);
      if (parts.length === 0) continue;
      const key = parts.join(' ');
      if (seen.has(key)) continue;
      seen.add(key);
      result.push(parts);
    }
    // Longest (most specific) keyword first so "pausa aca" wins over "pausa".
    return result.sort((a, b) => b.length - a.length);
  }

  private keywordMatchesAt(
    tokens: string[],
    start: number,
    parts: string[],
  ): boolean {
    return parts.every((part, j) =>
      FillerService.keywordPartMatches(tokens[start + j], part),
    );
  }

  private static keywordPartMatches(token: string, part: string): boolean {
    if (token === part) return true;
    if (part === 'pausa' || part === 'pauza') {
      return FillerService.looksLikePauseMarker(token);
    }
    return false;
  }

  private static looksLikePauseMarker(token: string): boolean {
    if (PAUSE_MARKER_WORDS.has(token)) return true;
    return (
      (token.startsWith('paus') || token.startsWith('pauz')) &&
      token.length <= 7
    );
  }

  private static stutterRange(word: WordTimestamp): TimeRange {
    return FillerService.expandToMin(
      Math.max(0, word.start - 0.02),
      word.end + 0.05,
    );
  }

  /** Inflate a tiny cut up to MIN_SEGMENT_SECONDS so the inverter won't drop it. */
  private static expandToMin(start: number, end: number): TimeRange {
    const duration = end - start;
    if (duration >= MIN_SEGMENT_SECONDS) return { start, end };
    const missing = MIN_SEGMENT_SECONDS - duration;
    return { start: Math.max(0, start - missing / 2), end: end + missing / 2 };
  }

  /** Casefold + strip diacritics so "PAUSA ACÁ" === "pausa aca". */
  private static normalize(text: string): string {
    return text
      .normalize('NFD')
      .replace(/\p{Mn}/gu, '')
      .toLowerCase();
  }

  private static cleanToken(text: string): string {
    return FillerService.normalize(text).replace(/[^0-9a-z]+/g, '');
  }
}
