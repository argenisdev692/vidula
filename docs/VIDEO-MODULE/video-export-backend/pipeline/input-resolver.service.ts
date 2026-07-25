import {
  BadRequestException,
  Injectable,
  UnprocessableEntityException,
} from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import axios from 'axios';
import { createWriteStream } from 'node:fs';
import { basename } from 'node:path';
import { pipeline } from 'node:stream/promises';
import type { Readable } from 'node:stream';
import { LoggerService } from '../../../logger/logger.service';
import { FfmpegService } from './ffmpeg.service';
import { WorkspaceService } from './workspace.service';
import type { ResolvedInput } from '../video-export.types';

const MAX_DOWNLOAD_BYTES = 2 * 1024 * 1024 * 1024; // 2 GB per part
const MAX_SCRIPT_BYTES = 25 * 1024 * 1024; // 25 MB — script PDF/markdown
const DOWNLOAD_TIMEOUT_MS = 120_000;

/**
 * Resolves request `video_paths` (R2/HTTP URLs) into readable local files, then
 * optionally orders them by recording `creation_time` so the client never has
 * to rename parts to `video_1`, `video_2`, …
 *
 * Inputs are URL-only (OWASP #15 / LFI): non-http(s) references are rejected so
 * a caller cannot point the pipeline at an arbitrary local file and have it
 * merged/rendered and uploaded to public storage. Remaining URLs go through the
 * SSRF guard, which rejects obvious internal/loopback hosts.
 */
@Injectable()
export class InputResolverService {
  constructor(
    private readonly config: ConfigService,
    private readonly logger: LoggerService,
    private readonly ffmpeg: FfmpegService,
    private readonly workspace: WorkspaceService,
  ) {
    this.logger.setContext(InputResolverService.name);
  }

  async resolveAll(
    jobUuid: string,
    references: string[],
    sortByCreationTime: boolean,
  ): Promise<ResolvedInput[]> {
    const downloadDir = this.workspace.path(jobUuid, '_downloads');
    await this.workspace.ensureDir(downloadDir);

    const resolved: ResolvedInput[] = [];
    for (let i = 0; i < references.length; i++) {
      resolved.push(await this.resolveOne(references[i], i, downloadDir));
    }

    return sortByCreationTime ? this.orderByCreation(resolved) : resolved;
  }

  /**
   * Fetch a small script file (PDF or markdown) into memory for the review
   * step. http(s) URL only (local paths rejected), with the same SSRF guard
   * and a 25 MB cap.
   */
  async fetchScriptBytes(reference: string): Promise<Buffer> {
    this.assertHttpUrl(reference, 'script_path');
    this.assertSafeUrl(reference);
    try {
      const response = await axios.get<ArrayBuffer>(reference, {
        responseType: 'arraybuffer',
        timeout: this.config.get<number>(
          'VIDEO_EXPORT_DOWNLOAD_TIMEOUT_MS',
          DOWNLOAD_TIMEOUT_MS,
        ),
        maxContentLength: MAX_SCRIPT_BYTES,
        maxBodyLength: MAX_SCRIPT_BYTES,
        // SSRF: the guard only validates the literal host; following a 3xx would
        // let a validated public URL bounce the fetch to an internal target
        // (e.g. 169.254.169.254). Public R2/object URLs serve the body directly.
        maxRedirects: 0,
      });
      return Buffer.from(response.data);
    } catch (error) {
      throw new UnprocessableEntityException(
        `Failed to download script '${reference}': ${
          error instanceof Error ? error.message : String(error)
        }`,
      );
    }
  }

  private async resolveOne(
    reference: string,
    index: number,
    downloadDir: string,
  ): Promise<ResolvedInput> {
    this.assertHttpUrl(reference, 'video_paths');
    this.assertSafeUrl(reference);

    const localPath = `${downloadDir}/${index}-${this.safeName(reference)}`;
    const fallbackKey = await this.download(reference, localPath);

    const creationTime = await this.ffmpeg.probeCreationTime(localPath);
    return {
      reference,
      localPath,
      downloaded: true,
      orderKey: creationTime ?? fallbackKey,
    };
  }

  /** Sort by creation time only when EVERY part has a resolvable key. */
  private orderByCreation(inputs: ResolvedInput[]): ResolvedInput[] {
    if (inputs.some((i) => i.orderKey === null)) {
      this.logger.warn(
        'InputResolverService: missing creation_time on some parts, keeping array order',
      );
      return inputs;
    }
    return [...inputs].sort((a, b) => (a.orderKey ?? 0) - (b.orderKey ?? 0));
  }

  /** Streams a remote part to disk with a size + timeout cap. Returns Last-Modified ms. */
  private async download(url: string, dest: string): Promise<number | null> {
    try {
      const response = await axios.get<Readable>(url, {
        responseType: 'stream',
        timeout: this.config.get<number>(
          'VIDEO_EXPORT_DOWNLOAD_TIMEOUT_MS',
          DOWNLOAD_TIMEOUT_MS,
        ),
        maxContentLength: MAX_DOWNLOAD_BYTES,
        maxBodyLength: MAX_DOWNLOAD_BYTES,
        // SSRF: the guard only validates the literal host; following a 3xx would
        // let a validated public URL bounce the fetch to an internal target
        // (e.g. 169.254.169.254). Public R2/object URLs serve the body directly.
        maxRedirects: 0,
      });
      await pipeline(response.data, createWriteStream(dest));

      const header: unknown = response.headers['last-modified'];
      const ms = typeof header === 'string' ? Date.parse(header) : NaN;
      return Number.isNaN(ms) ? null : ms;
    } catch (error) {
      throw new UnprocessableEntityException(
        `Failed to download source video '${url}': ${
          error instanceof Error ? error.message : String(error)
        }`,
      );
    }
  }

  /** URL-only contract: reject local paths so the pipeline can never read an arbitrary file. */
  private assertHttpUrl(reference: string, field: string): void {
    if (!this.isHttpUrl(reference)) {
      throw new BadRequestException(
        `Only http(s) URLs are accepted for ${field}; upload the file to storage first (got '${reference}').`,
      );
    }
  }

  private isHttpUrl(reference: string): boolean {
    return /^https?:\/\//i.test(reference);
  }

  private safeName(url: string): string {
    try {
      const name = basename(new URL(url).pathname) || 'part.mp4';
      return name.replace(/[^\w.-]/g, '_').slice(-128);
    } catch {
      return 'part.mp4';
    }
  }

  private assertSafeUrl(reference: string): void {
    let rawHost: string;
    try {
      rawHost = new URL(reference).hostname.toLowerCase();
    } catch {
      throw new BadRequestException(`Invalid video URL: ${reference}`);
    }
    // WHATWG URL keeps IPv6 in brackets (e.g. "[::1]"); strip them so the
    // checks below see the bare address. "::ffff:10.0.0.1" maps an IPv4 into
    // IPv6 — fold it back to its IPv4 form so the v4 ranges still catch it.
    const host = rawHost.replace(/^\[|\]$/g, '');
    const v4 = host.startsWith('::ffff:') ? host.slice('::ffff:'.length) : host;

    const blocked =
      host === 'localhost' ||
      host === '' ||
      // IPv6 loopback / unspecified, ULA (fc00::/7) and link-local (fe80::/10).
      host === '::1' ||
      host === '::' ||
      /^f[cd][0-9a-f]*:/.test(host) ||
      /^fe[89ab][0-9a-f]*:/.test(host) ||
      // IPv4 loopback / unspecified / private / link-local / CGNAT.
      /^0\./.test(v4) ||
      v4 === '0.0.0.0' ||
      /^127\./.test(v4) ||
      /^10\./.test(v4) ||
      /^192\.168\./.test(v4) ||
      /^169\.254\./.test(v4) ||
      /^172\.(1[6-9]|2\d|3[01])\./.test(v4) ||
      /^100\.(6[4-9]|[7-9]\d|1[01]\d|12[0-7])\./.test(v4);
    if (blocked) {
      throw new BadRequestException(
        `Refusing to fetch internal host (SSRF guard): ${host}`,
      );
    }
  }
}
