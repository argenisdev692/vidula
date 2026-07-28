import { Injectable } from '@nestjs/common';
import { Prisma } from '../../../../../generated/prisma/client';
import { PrismaService } from '../../../../../shared/database/prisma.service';
import {
  buildTrashedWhere,
  type TrashedMode,
} from '../../../../../shared/crud/trashed.util';
import { buildDateRangeWhere } from '../../../../../shared/crud/date-range.util';
import type {
  CreateContentGenerationData,
  CreateProductData,
  IProductRepository,
  ListProductsOptions,
  SeedSessionInput,
  UpdateContentGenerationData,
  UpdateProductData,
} from '../../../domain/ports/product.repository.port';
import type { ContentGeneration, Product } from '../../../domain/product.types';
import { NON_TERMINAL_GENERATION_STATUSES } from '../../../domain/product.types';
import {
  mapContentGeneration,
  mapProduct,
  PRODUCT_HTTP_SELECT,
} from '../mappers/product.mapper';

/** Hard cap for export row fetch (OWASP API #4). */
const PRODUCTS_EXPORT_MAX_ROWS = 10_000;

@Injectable()
export class PrismaProductRepository implements IProductRepository {
  constructor(private readonly prisma: PrismaService) {}

  private buildListWhere(
    userId: string,
    trashed: TrashedMode,
    options?: ListProductsOptions,
  ): Prisma.ProductWhereInput {
    return {
      userId,
      ...buildTrashedWhere(trashed),
      ...(options?.range ? buildDateRangeWhere(options.range) : {}),
      ...(options?.type ? { type: options.type } : {}),
      ...(options?.status ? { status: options.status } : {}),
      ...(options?.clientId ? { clientId: options.clientId } : {}),
      ...(options?.search
        ? {
            OR: [
              { title: { contains: options.search, mode: 'insensitive' } },
              { slug: { contains: options.search, mode: 'insensitive' } },
              { description: { contains: options.search, mode: 'insensitive' } },
            ],
          }
        : {}),
    };
  }

  async findAll(
    userId: string,
    limit = 50,
    skip = 0,
    trashed: TrashedMode = 'exclude',
    options?: ListProductsOptions,
  ): Promise<{ data: Product[]; total: number }> {
    const where = this.buildListWhere(userId, trashed, options);
    const take = Math.min(limit, 100);
    const [total, rows] = await this.prisma.$transaction([
      this.prisma.product.count({ where }),
      this.prisma.product.findMany({
        where,
        orderBy: { createdAt: 'desc' },
        take,
        skip,
        select: PRODUCT_HTTP_SELECT,
      }),
    ]);
    return { data: rows.map(mapProduct), total };
  }

  async findAllForExport(
    userId: string,
    trashed: TrashedMode = 'exclude',
    options?: ListProductsOptions,
  ): Promise<Product[]> {
    const where = this.buildListWhere(userId, trashed, options);
    const rows = await this.prisma.product.findMany({
      where,
      orderBy: { createdAt: 'desc' },
      take: PRODUCTS_EXPORT_MAX_ROWS,
      select: PRODUCT_HTTP_SELECT,
    });
    return rows.map(mapProduct);
  }

  async findById(
    id: string,
    options?: { withTrashed?: boolean; includeDetails?: boolean },
  ): Promise<Product | null> {
    const row = await this.prisma.product.findFirst({
      where: {
        id,
        ...(options?.withTrashed ? {} : { deletedAt: null }),
      },
      include: options?.includeDetails
        ? { classroom: true, videoCourse: true }
        : undefined,
    });
    return row ? mapProduct(row) : null;
  }

  async findIdBySlug(slug: string): Promise<string | null> {
    const row = await this.prisma.product.findFirst({
      where: { slug, deletedAt: null },
      select: { id: true },
    });
    return row?.id ?? null;
  }

  async create(data: CreateProductData): Promise<Product> {
    const row = await this.prisma.product.create({
      data: {
        userId: data.userId,
        clientId: data.clientId ?? null,
        type: data.type,
        title: data.title,
        slug: data.slug,
        description: data.description ?? null,
        price: new Prisma.Decimal(data.price),
        currency: data.currency,
        status: data.status,
        thumbnail: data.thumbnail ?? null,
        level: data.level,
        language: data.language,
        startDate: data.startDate ? new Date(data.startDate) : null,
        endDate: data.endDate ? new Date(data.endDate) : null,
        totalHours:
          data.totalHours != null ? new Prisma.Decimal(data.totalHours) : null,
        totalSessions: data.totalSessions ?? null,
        modality: data.modality ?? null,
        notes: data.notes ?? null,
        ...(data.type === 'classroom'
          ? {
              classroom: {
                create: {
                  maxStudents: data.classroom?.maxStudents ?? null,
                  meetUrl: data.classroom?.meetUrl ?? null,
                  objectives: data.classroom?.objectives ?? null,
                  requirements: data.classroom?.requirements ?? null,
                },
              },
            }
          : {
              videoCourse: {
                create: {
                  platform: data.videoCourse?.platform ?? null,
                  playlistUrl: data.videoCourse?.playlistUrl ?? null,
                  totalVideos: data.videoCourse?.totalVideos ?? 0,
                  totalDurationMinutes:
                    data.videoCourse?.totalDurationMinutes ?? null,
                  targetAudience: data.videoCourse?.targetAudience ?? null,
                },
              },
            }),
      },
      include: { classroom: true, videoCourse: true },
    });
    return mapProduct(row);
  }

  async update(id: string, data: UpdateProductData): Promise<Product> {
    const row = await this.prisma.product.update({
      where: { id },
      data: {
        ...(data.clientId !== undefined ? { clientId: data.clientId } : {}),
        ...(data.title !== undefined ? { title: data.title } : {}),
        ...(data.slug !== undefined ? { slug: data.slug } : {}),
        ...(data.description !== undefined
          ? { description: data.description }
          : {}),
        ...(data.price !== undefined
          ? { price: new Prisma.Decimal(data.price) }
          : {}),
        ...(data.currency !== undefined ? { currency: data.currency } : {}),
        ...(data.status !== undefined ? { status: data.status } : {}),
        ...(data.thumbnail !== undefined ? { thumbnail: data.thumbnail } : {}),
        ...(data.level !== undefined ? { level: data.level } : {}),
        ...(data.language !== undefined ? { language: data.language } : {}),
        ...(data.startDate !== undefined
          ? { startDate: data.startDate ? new Date(data.startDate) : null }
          : {}),
        ...(data.endDate !== undefined
          ? { endDate: data.endDate ? new Date(data.endDate) : null }
          : {}),
        ...(data.totalHours !== undefined
          ? {
              totalHours:
                data.totalHours != null
                  ? new Prisma.Decimal(data.totalHours)
                  : null,
            }
          : {}),
        ...(data.totalSessions !== undefined
          ? { totalSessions: data.totalSessions }
          : {}),
        ...(data.modality !== undefined ? { modality: data.modality } : {}),
        ...(data.notes !== undefined ? { notes: data.notes } : {}),
        ...(data.classroom
          ? {
              classroom: {
                upsert: {
                  create: {
                    maxStudents: data.classroom.maxStudents ?? null,
                    meetUrl: data.classroom.meetUrl ?? null,
                    objectives: data.classroom.objectives ?? null,
                    requirements: data.classroom.requirements ?? null,
                  },
                  update: {
                    maxStudents: data.classroom.maxStudents ?? null,
                    meetUrl: data.classroom.meetUrl ?? null,
                    objectives: data.classroom.objectives ?? null,
                    requirements: data.classroom.requirements ?? null,
                  },
                },
              },
            }
          : {}),
        ...(data.videoCourse
          ? {
              videoCourse: {
                upsert: {
                  create: {
                    platform: data.videoCourse.platform ?? null,
                    playlistUrl: data.videoCourse.playlistUrl ?? null,
                    totalVideos: data.videoCourse.totalVideos ?? 0,
                    totalDurationMinutes:
                      data.videoCourse.totalDurationMinutes ?? null,
                    targetAudience: data.videoCourse.targetAudience ?? null,
                  },
                  update: {
                    platform: data.videoCourse.platform ?? null,
                    playlistUrl: data.videoCourse.playlistUrl ?? null,
                    totalVideos: data.videoCourse.totalVideos ?? 0,
                    totalDurationMinutes:
                      data.videoCourse.totalDurationMinutes ?? null,
                    targetAudience: data.videoCourse.targetAudience ?? null,
                  },
                },
              },
            }
          : {}),
      },
      include: { classroom: true, videoCourse: true },
    });
    return mapProduct(row);
  }

  async softDelete(id: string): Promise<void> {
    await this.prisma.product.update({
      where: { id },
      data: { deletedAt: new Date() },
    });
  }

  async restore(id: string): Promise<Product> {
    const row = await this.prisma.product.update({
      where: { id },
      data: { deletedAt: null },
      include: { classroom: true, videoCourse: true },
    });
    return mapProduct(row);
  }

  async bulkDelete(
    userId: string,
    ids: string[],
  ): Promise<{ count: number }> {
    const result = await this.prisma.product.updateMany({
      where: { userId, id: { in: ids }, deletedAt: null },
      data: { deletedAt: new Date() },
    });
    return { count: result.count };
  }

  async bulkRestore(
    userId: string,
    ids: string[],
  ): Promise<{ count: number }> {
    const result = await this.prisma.product.updateMany({
      where: { userId, id: { in: ids }, deletedAt: { not: null } },
      data: { deletedAt: null },
    });
    return { count: result.count };
  }

  async findInFlightGeneration(
    productId: string,
  ): Promise<ContentGeneration | null> {
    const row = await this.prisma.contentGeneration.findFirst({
      where: {
        productId,
        status: { in: [...NON_TERMINAL_GENERATION_STATUSES] },
      },
      orderBy: { createdAt: 'desc' },
    });
    return row ? mapContentGeneration(row) : null;
  }

  async createGeneration(
    data: CreateContentGenerationData,
  ): Promise<ContentGeneration> {
    const row = await this.prisma.contentGeneration.create({
      data: {
        productId: data.productId,
        userId: data.userId,
        sourceMarkdown: data.sourceMarkdown,
        mode: data.mode,
        model: data.model ?? null,
        status: 'pending',
        progress: 0,
      },
    });
    return mapContentGeneration(row);
  }

  async findGenerationById(id: string): Promise<ContentGeneration | null> {
    const row = await this.prisma.contentGeneration.findUnique({
      where: { id },
    });
    return row ? mapContentGeneration(row) : null;
  }

  async updateGeneration(
    id: string,
    data: UpdateContentGenerationData,
  ): Promise<ContentGeneration> {
    const row = await this.prisma.contentGeneration.update({
      where: { id },
      data: {
        ...(data.status !== undefined ? { status: data.status } : {}),
        ...(data.progress !== undefined ? { progress: data.progress } : {}),
        ...(data.sessionsCount !== undefined
          ? { sessionsCount: data.sessionsCount }
          : {}),
        ...(data.topicsCount !== undefined
          ? { topicsCount: data.topicsCount }
          : {}),
        ...(data.scriptsCount !== undefined
          ? { scriptsCount: data.scriptsCount }
          : {}),
        ...(data.error !== undefined ? { error: data.error } : {}),
        ...(data.startedAt !== undefined ? { startedAt: data.startedAt } : {}),
        ...(data.completedAt !== undefined
          ? { completedAt: data.completedAt }
          : {}),
        ...(data.model !== undefined ? { model: data.model } : {}),
      },
    });
    return mapContentGeneration(row);
  }

  async replaceContentTree(
    productId: string,
    sessions: SeedSessionInput[],
  ): Promise<{
    sessionsCount: number;
    topicsCount: number;
    scriptsCount: number;
  }> {
    const now = new Date();

    return this.prisma.$transaction(async (tx) => {
      await tx.productSession.updateMany({
        where: { productId, deletedAt: null },
        data: { deletedAt: now },
      });

      if (sessions.length === 0) {
        return { sessionsCount: 0, topicsCount: 0, scriptsCount: 0 };
      }

      await tx.productSession.createMany({
        data: sessions.map((session) => ({
          productId,
          sessionNumber: session.sessionNumber,
          title: session.title,
        })),
      });

      const sessionRows = await tx.productSession.findMany({
        where: {
          productId,
          deletedAt: null,
          sessionNumber: { in: sessions.map((s) => s.sessionNumber) },
        },
        select: { id: true, sessionNumber: true },
      });
      const sessionIdByNumber = new Map(
        sessionRows.map((r) => [r.sessionNumber, r.id]),
      );

      const topicCreates: {
        productSessionId: string;
        title: string;
        description: string | null;
        sortOrder: number;
      }[] = [];

      for (const session of sessions) {
        const sessionId = sessionIdByNumber.get(session.sessionNumber);
        if (!sessionId) continue;
        for (const topic of session.topics) {
          topicCreates.push({
            productSessionId: sessionId,
            title: topic.title,
            description: topic.description ?? null,
            sortOrder: topic.sortOrder,
          });
        }
      }

      if (topicCreates.length === 0) {
        return {
          sessionsCount: sessions.length,
          topicsCount: 0,
          scriptsCount: 0,
        };
      }

      await tx.productSessionTopic.createMany({ data: topicCreates });

      const topicRows = await tx.productSessionTopic.findMany({
        where: {
          productSessionId: { in: [...sessionIdByNumber.values()] },
          deletedAt: null,
        },
        select: { id: true },
      });

      await tx.productScript.createMany({
        data: topicRows.map((topic) => ({
          productSessionTopicId: topic.id,
          status: 'draft' as const,
        })),
      });

      return {
        sessionsCount: sessions.length,
        topicsCount: topicCreates.length,
        scriptsCount: topicRows.length,
      };
    });
  }
}
