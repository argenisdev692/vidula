import { Injectable, InternalServerErrorException } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { createReadStream, statSync } from 'node:fs';
import OpenAI from 'openai';
import type { IPolicy } from 'cockatiel';
import { LoggerService } from '../../../logger/logger.service';
import { createExternalServicePolicy } from '../../../shared/external/resilience';
import { WHISPER_AUDIO } from '../video-export.constants';
import type { WordTimestamp } from '../video-export.types';

/** Hard cap per Whisper call — cockatiel owns the retries, the SDK does not. */
const WHISPER_TIMEOUT_MS = 120_000;

/**
 * Word-level transcription via the OpenAI Whisper API.
 *
 * This is the ONLY paid / external dependency in the pipeline and is invoked
 * solely when `ai_cleaning_enabled` is true. Whisper is used purely for the
 * word timestamps that drive filler / stutter / PAUSA cutting — it never
 * touches audio quality.
 */
@Injectable()
export class TranscriptionService {
  private client: OpenAI | null = null;
  private readonly apiKey?: string;
  private readonly model: string;
  // Retry + circuit breaker (cockatiel 'ai' profile) so a Whisper outage trips
  // the breaker instead of hammering the paid endpoint on every queued job.
  private readonly policy: IPolicy = createExternalServicePolicy(
    'openai-whisper',
    'ai',
  );

  constructor(
    private readonly config: ConfigService,
    private readonly logger: LoggerService,
  ) {
    this.logger.setContext(TranscriptionService.name);
    this.apiKey = this.config.get<string>('OPENAI_API_KEY');
    this.model = this.config.get<string>('OPENAI_WHISPER_MODEL', 'whisper-1');
  }

  /** True when the module can perform AI cleaning. */
  isEnabled(): boolean {
    return Boolean(this.apiKey);
  }

  async transcribeWords(
    audioPath: string,
    language: string,
  ): Promise<WordTimestamp[]> {
    if (!this.apiKey) {
      throw new InternalServerErrorException(
        'ai_cleaning_enabled is true but OPENAI_API_KEY is not configured.',
      );
    }

    const bytes = statSync(audioPath).size;
    if (bytes > WHISPER_AUDIO.maxFileBytes) {
      // Whisper rejects files over 25 MB. We extract a low-bitrate mono mp3, so
      // hitting this means a very long recording — chunking is the follow-up.
      throw new InternalServerErrorException(
        `Audio for transcription is ${(bytes / 1_048_576).toFixed(1)} MB, over the ` +
          `${(WHISPER_AUDIO.maxFileBytes / 1_048_576).toFixed(0)} MB Whisper limit. ` +
          'Use a shorter recording or run with ai_cleaning_enabled=false.',
      );
    }

    const client = this.getClient();
    // The stream is created INSIDE the lambda so each retry attempt gets a fresh
    // readable (a consumed stream cannot be replayed).
    const response = await this.policy.execute(() =>
      client.audio.transcriptions.create({
        file: createReadStream(audioPath),
        model: this.model,
        language,
        response_format: 'verbose_json',
        timestamp_granularities: ['word'],
      }),
    );

    const words = (response.words ?? [])
      .filter((w) => typeof w.start === 'number' && typeof w.end === 'number')
      .map<WordTimestamp>((w) => ({
        text: w.word,
        start: w.start,
        end: w.end,
      }));

    this.logger.info('TranscriptionService.transcribeWords', {
      model: this.model,
      words: words.length,
    });
    return words;
  }

  private getClient(): OpenAI {
    if (!this.client) {
      // maxRetries: 0 — cockatiel owns retries; leaving the SDK default (2) on
      // would multiply attempts (3 × 3). timeout bounds each attempt.
      this.client = new OpenAI({
        apiKey: this.apiKey,
        maxRetries: 0,
        timeout: WHISPER_TIMEOUT_MS,
      });
    }
    return this.client;
  }
}
