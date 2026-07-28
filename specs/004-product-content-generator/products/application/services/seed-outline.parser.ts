import { Injectable } from '@nestjs/common';
import type { ProductType } from '../../domain/product.types';
import type { SeedSessionInput } from '../../domain/ports/product.repository.port';
import {
  MAX_SESSIONS_PER_PRODUCT,
  MAX_TOPICS_PER_PRODUCT,
} from '../products-cache.constants';

export interface SeedOutline {
  sessions: SeedSessionInput[];
}

/**
 * Parses a markdown course/video index into sessions → topics.
 *
 * Shape A (classroom): `### Sesión N | …` headings + `- **Tema k:** title` bullets.
 * Shape B (video): `### BLOQUE N – title` + markdown tables with video titles,
 * or `## VÍDEO N – title` detail headings as fallback.
 */
@Injectable()
export class SeedOutlineParser {
  parse(markdown: string, productType: ProductType): SeedOutline {
    const trimmed = markdown.trim();
    if (!trimmed) {
      throw new Error('EMPTY_MARKDOWN');
    }

    const isVideo =
      productType === 'video_tutorial' || productType === 'video_pill';

    const sessions = isVideo
      ? this.parseVideoOutline(trimmed)
      : this.parseClassroomOutline(trimmed);

    if (sessions.length === 0) {
      throw new Error('UNPARSEABLE_MARKDOWN');
    }

    const topicsTotal = sessions.reduce((n, s) => n + s.topics.length, 0);
    if (sessions.length > MAX_SESSIONS_PER_PRODUCT) {
      throw new Error('TOO_MANY_SESSIONS');
    }
    if (topicsTotal > MAX_TOPICS_PER_PRODUCT) {
      throw new Error('TOO_MANY_TOPICS');
    }
    if (topicsTotal === 0) {
      throw new Error('UNPARSEABLE_MARKDOWN');
    }

    return { sessions };
  }

  private parseClassroomOutline(markdown: string): SeedSessionInput[] {
    const sessionRegex =
      /^###\s+Sesión\s+(\d+)\s*[|–—-]\s*(.+)$/gim;
    const matches = [...markdown.matchAll(sessionRegex)];
    if (matches.length === 0) {
      return [];
    }

    const sessions: SeedSessionInput[] = [];
    for (let i = 0; i < matches.length; i++) {
      const match = matches[i]!;
      const start = match.index! + match[0].length;
      const end = i + 1 < matches.length ? matches[i + 1]!.index! : markdown.length;
      const body = markdown.slice(start, end);
      const sessionNumber = Number.parseInt(match[1]!, 10);
      const title = match[2]!.trim();
      const topics = this.extractClassroomTopics(body);
      sessions.push({ sessionNumber, title, topics });
    }
    return sessions;
  }

  private extractClassroomTopics(
    body: string,
  ): SeedSessionInput['topics'] {
    const topicRegex = /^-\s+\*\*Tema\s+\d+:\*\*\s*(.+)$/gim;
    const topics: SeedSessionInput['topics'] = [];
    let sortOrder = 1;
    for (const m of body.matchAll(topicRegex)) {
      const title = m[1]!.trim();
      if (title) {
        topics.push({ title, sortOrder: sortOrder++ });
      }
    }
    return topics;
  }

  private parseVideoOutline(markdown: string): SeedSessionInput[] {
    const blockRegex =
      /^###\s+BLOQUE\s+(\d+)\s*[–—-]\s*(.+?)(?:\s*\(\d+\s*min\))?\s*$/gim;
    const blocks = [...markdown.matchAll(blockRegex)];

    if (blocks.length > 0) {
      const sessions: SeedSessionInput[] = [];
      for (let i = 0; i < blocks.length; i++) {
        const match = blocks[i]!;
        const start = match.index! + match[0].length;
        const end =
          i + 1 < blocks.length ? blocks[i + 1]!.index! : markdown.length;
        const body = markdown.slice(start, end);
        const sessionNumber = Number.parseInt(match[1]!, 10);
        const title = match[2]!.trim();
        const topics = this.extractVideoTableTopics(body);
        sessions.push({
          sessionNumber,
          title,
          topics:
            topics.length > 0
              ? topics
              : this.extractVideoHeadingTopics(body),
        });
      }
      return sessions;
    }

    // Fallback: treat ## VÍDEO N headings as a single block of topics
    const videoHeadings = this.extractVideoDetailHeadings(markdown);
    if (videoHeadings.length === 0) {
      return [];
    }
    return [
      {
        sessionNumber: 1,
        title: 'Videos',
        topics: videoHeadings,
      },
    ];
  }

  private extractVideoTableTopics(
    body: string,
  ): SeedSessionInput['topics'] {
    const topics: SeedSessionInput['topics'] = [];
    let sortOrder = 1;
    for (const line of body.split('\n')) {
      const trimmed = line.trim();
      if (!trimmed.startsWith('|')) continue;
      if (/^\|\s*-+/.test(trimmed) || /N[ºo]|Vídeo|Tema/i.test(trimmed)) {
        continue;
      }
      const cells = trimmed
        .split('|')
        .map((c) => c.trim())
        .filter((c) => c.length > 0);
      // Typical: Nº | Vídeo | Tema | Duración
      if (cells.length < 2) continue;
      const titleCell =
        cells.length >= 3 && /^\d+$/.test(cells[0]!)
          ? cells[1]!
          : cells[0]!;
      if (!titleCell || /^\d+$/.test(titleCell)) continue;
      topics.push({ title: titleCell, sortOrder: sortOrder++ });
    }
    return topics;
  }

  private extractVideoHeadingTopics(
    body: string,
  ): SeedSessionInput['topics'] {
    return this.extractVideoDetailHeadings(body);
  }

  private extractVideoDetailHeadings(
    markdown: string,
  ): SeedSessionInput['topics'] {
    const regex = /^##\s+V[IÍ]DEO\s+(\d+)\s*[–—-]\s*(.+)$/gim;
    const topics: SeedSessionInput['topics'] = [];
    let sortOrder = 1;
    for (const m of markdown.matchAll(regex)) {
      const title = m[2]!.trim();
      if (title) {
        topics.push({ title, sortOrder: sortOrder++ });
      }
    }
    return topics;
  }
}
