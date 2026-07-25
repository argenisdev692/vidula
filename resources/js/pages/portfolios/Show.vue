<script setup lang="ts">
/**
 * Portfolio project detail — read-only fields rendered by GET /portfolios/{uuid}
 * (VIEW_PORTFOLIOS). The handler resolves the record `withTrashed` + its ordered
 * `gallery` relation (no `user` eager-load here — see GetPortfolioHandler /
 * EloquentPortfolioRepository::findByUuid — so the author is intentionally not
 * shown on this page). A suspended project is viewable here; its soft-delete
 * status is shown via a badge alongside the separate landing-page visibility
 * (`is_public`) badge.
 *
 * Gallery image management (add / drag-reorder / remove) lives in
 * {@see PortfolioGallery} below the facts card — it is a per-project concern
 * that only makes sense once the record exists, gated by UPDATE_PORTFOLIOS.
 * The detail chrome (back link, glass card, facts styling) lives in the shared
 * {@see DetailCard}.
 */
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import Image from 'primevue/image';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import DetailCard from '@/common/ui/DetailCard.vue';
import StatusBadge from '@/common/ui/StatusBadge.vue';
import Tag from '@/volt/Tag.vue';
import PortfolioGallery from './components/PortfolioGallery.vue';
import { imagePreviewPt } from '@/common/media/imagePreviewPt';
import { formatDate } from '@/modules/portfolio/helpers/formatDate';
import type { Portfolio } from '@/modules/portfolio/types';

defineOptions({ layout: AppLayout });

/** The detail render returns the full model (adds `updated_at`/`gallery` over list rows). */
type PortfolioDetail = Portfolio & { updated_at?: string | null; gallery: Portfolio['gallery'] };

const props = defineProps<{
    portfolio: PortfolioDetail;
}>();

const isSuspended = computed<boolean>(() => props.portfolio.deleted_at !== null);
const title = computed<string>(() => props.portfolio.title || 'Untitled project');
const gallery = computed(() => props.portfolio.gallery ?? []);
</script>

<template>
    <Head :title="title" />

    <DetailCard
        header-title="Portfolio"
        header-subtitle="Project detail"
        permission="VIEW_PORTFOLIOS"
        fallback-text="You don't have permission to view this portfolio project."
        back-href="/portfolios"
        back-label="Back to portfolio"
        :title="title"
        :columns="3"
        max-width="56rem"
    >
        <template #badges>
            <Tag :value="portfolio.project_type" severity="info" />
            <StatusBadge
                :tone="portfolio.is_public ? 'success' : 'muted'"
                :label="portfolio.is_public ? 'Public' : 'Hidden'"
            />
            <StatusBadge
                :tone="isSuspended ? 'danger' : 'success'"
                :label="isSuspended ? 'Suspended' : 'Active'"
            />
        </template>

        <div class="pf-media">
            <div v-if="portfolio.cover_url" class="pf-media__cover">
                <Image
                    :src="portfolio.cover_url"
                    :alt="`${title} cover`"
                    preview
                    :pt="imagePreviewPt"
                    image-class="pf-media__cover-img"
                />
            </div>
            <div v-if="portfolio.video_url" class="pf-media__video">
                <video :src="portfolio.video_url" controls muted preload="metadata" />
            </div>
        </div>

        <dl class="facts">
            <div class="fact">
                <dt>Client</dt>
                <dd>{{ portfolio.client_name }}</dd>
            </div>
            <div class="fact">
                <dt>Sort order</dt>
                <dd>{{ portfolio.sort_order }}</dd>
            </div>
            <div class="fact">
                <dt>Published</dt>
                <dd>{{ formatDate(portfolio.published_at) }}</dd>
            </div>
            <div class="fact">
                <dt>Live URL</dt>
                <dd>
                    <a v-if="portfolio.live_url" :href="portfolio.live_url" target="_blank" rel="noopener noreferrer" class="link">
                        {{ portfolio.live_url }}
                    </a>
                    <span v-else>—</span>
                </dd>
            </div>
            <div class="fact">
                <dt>Created</dt>
                <dd>{{ formatDate(portfolio.created_at) }}</dd>
            </div>
            <div class="fact">
                <dt>Last updated</dt>
                <dd>{{ formatDate(portfolio.updated_at ?? null) }}</dd>
            </div>
            <div class="fact fact--wide">
                <dt>Tech stack</dt>
                <dd>
                    <div v-if="(portfolio.tech_stack ?? []).length" class="pf-tech">
                        <Tag
                            v-for="tech in portfolio.tech_stack"
                            :key="tech"
                            :value="tech"
                            severity="secondary"
                        />
                    </div>
                    <span v-else>—</span>
                </dd>
            </div>
            <div class="fact fact--wide">
                <dt>Description</dt>
                <dd>{{ portfolio.description || '—' }}</dd>
            </div>
        </dl>

        <div class="pf-gallery-section">
            <h3 class="pf-gallery-section__title">Gallery images</h3>
            <PortfolioGallery :portfolio-uuid="portfolio.uuid" :images="gallery" />
        </div>
    </DetailCard>
</template>

<style scoped>
.pf-media {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-4);
    margin-bottom: var(--space-5);
}

@media (max-width: 640px) {
    .pf-media {
        grid-template-columns: 1fr;
    }
}

.pf-media__cover,
.pf-media__video {
    display: flex;
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-subtle);
    background: var(--bg-elevated);
    overflow: hidden;
}

.pf-media__cover {
    width: 100%;
}

.pf-media__cover :deep(.pf-media__cover-img) {
    width: 100%;
    max-height: 16rem;
    object-fit: cover;
    cursor: pointer;
}

.pf-media__video video {
    width: 100%;
    max-height: 16rem;
}

.pf-gallery-section {
    margin-top: var(--space-6);
    padding-top: var(--space-5);
    border-top: 1px solid var(--border-default);
}

.pf-gallery-section__title {
    margin: 0 0 var(--space-3);
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--text-muted);
}

.pf-tech {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
}
</style>
