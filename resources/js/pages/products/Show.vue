<script setup lang="ts">
/**
 * Product detail — catalog fields + content generation + session tree.
 */
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import StatusBadge from '@/common/ui/StatusBadge.vue';
import BackLink from '@/common/ui/BackLink.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import { formatDateShort } from '@/modules/products/helpers/formatDate';
import { formatPrice, lifecycleTone, productTypeLabel } from '@/modules/products/helpers/formatProduct';
import GenerateContentPanel from './components/GenerateContentPanel.vue';
import type {
    Product,
    ProductGenerationStatus,
    ProductSessionSummary,
} from '@/modules/products/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    product: Product;
    generation: ProductGenerationStatus | null;
    sessions: ProductSessionSummary[];
}>();

const isSuspended = computed<boolean>(() => props.product.deleted_at !== null);
const title = computed<string>(() => props.product.title || 'Untitled product');

const ownerName = computed<string>(() => {
    const label = [props.product.user?.first_name, props.product.user?.last_name].filter(Boolean).join(' ').trim();
    return label || 'System';
});

const isClassroom = computed<boolean>(() => props.product.type === 'classroom');
const isVideo = computed<boolean>(() => props.product.type === 'video_tutorial' || props.product.type === 'video_pill');

function refreshShow(): void {
    router.reload({ only: ['product', 'generation', 'sessions'] });
}

function scriptTone(status: string | null): 'success' | 'primary' | 'muted' | 'danger' {
    if (status === 'verified' || status === 'recorded') {
        return 'success';
    }
    if (status === 'needs_review') {
        return 'danger';
    }
    if (status === 'generated') {
        return 'primary';
    }
    return 'muted';
}
</script>

<template>
    <Head :title="title" />

    <div class="detail-page">
        <header class="detail-header">
            <div class="detail-header__lead">
                <BackLink href="/products" label="Back to products" />
                <div>
                    <h1>{{ title }}</h1>
                    <p class="muted">{{ productTypeLabel(product.type) }} · {{ product.slug }}</p>
                </div>
            </div>
            <div class="badges">
                <StatusBadge :tone="lifecycleTone(product.status)" :label="product.status" />
                <StatusBadge v-if="isSuspended" tone="danger" label="Suspended" />
            </div>
        </header>

        <section class="show-card">
            <h2 class="show-card__title">Catalog</h2>
            <dl class="detail-grid">
                <div>
                    <dt>Price</dt>
                    <dd>{{ formatPrice(product.price, product.currency) }}</dd>
                </div>
                <div>
                    <dt>Level</dt>
                    <dd>{{ product.level }}</dd>
                </div>
                <div>
                    <dt>Language</dt>
                    <dd>{{ product.language }}</dd>
                </div>
                <div>
                    <dt>Owner</dt>
                    <dd>{{ ownerName }}</dd>
                </div>
                <div>
                    <dt>Client</dt>
                    <dd>{{ product.client?.client_name || '—' }}</dd>
                </div>
                <div>
                    <dt>Created</dt>
                    <dd>{{ formatDateShort(product.created_at) }}</dd>
                </div>
                <div v-if="product.sessions_count !== undefined">
                    <dt>Sessions</dt>
                    <dd>{{ product.sessions_count }}</dd>
                </div>
                <div v-if="product.materials_count !== undefined">
                    <dt>Materials</dt>
                    <dd>{{ product.materials_count }}</dd>
                </div>
            </dl>
        </section>

        <section v-if="product.description" class="show-card">
            <h2 class="show-card__title">Description</h2>
            <p class="prose">{{ product.description }}</p>
        </section>

        <section v-if="isClassroom && product.classroom" class="show-card">
            <h2 class="show-card__title">Classroom</h2>
            <dl class="detail-grid">
                <div>
                    <dt>Max students</dt>
                    <dd>{{ product.classroom.max_students ?? '—' }}</dd>
                </div>
                <div>
                    <dt>Meet URL</dt>
                    <dd>{{ product.classroom.meet_url || '—' }}</dd>
                </div>
                <div class="span-2">
                    <dt>Objectives</dt>
                    <dd class="prose">{{ product.classroom.objectives || '—' }}</dd>
                </div>
            </dl>
        </section>

        <section v-if="isVideo && product.video_course" class="show-card">
            <h2 class="show-card__title">Video course</h2>
            <dl class="detail-grid">
                <div>
                    <dt>Platform</dt>
                    <dd>{{ product.video_course.platform || '—' }}</dd>
                </div>
                <div>
                    <dt>Videos</dt>
                    <dd>{{ product.video_course.total_videos }}</dd>
                </div>
                <div>
                    <dt>Duration</dt>
                    <dd>
                        {{
                            product.video_course.total_duration_minutes !== null
                                ? `${product.video_course.total_duration_minutes} min`
                                : '—'
                        }}
                    </dd>
                </div>
                <div>
                    <dt>Playlist</dt>
                    <dd>{{ product.video_course.playlist_url || '—' }}</dd>
                </div>
            </dl>
        </section>

        <PermissionGuard permission="VIEW_PRODUCTS">
            <section class="show-card">
                <h2 class="show-card__title">Content generation</h2>
                <GenerateContentPanel
                    :product="product"
                    :generation="generation"
                    @refreshed="refreshShow"
                />
            </section>
        </PermissionGuard>

        <section v-if="sessions.length > 0" class="show-card">
            <h2 class="show-card__title">Content tree</h2>
            <div class="sessions">
                <article v-for="session in sessions" :key="session.session_number" class="session">
                    <h3>
                        Session {{ session.session_number }}
                        <span class="muted">{{ session.title }}</span>
                    </h3>
                    <ul>
                        <li v-for="topic in session.topics" :key="topic.uuid">
                            <span>{{ topic.title }}</span>
                            <StatusBadge
                                v-if="topic.status"
                                :tone="scriptTone(topic.status)"
                                :label="topic.status"
                            />
                            <span v-if="topic.estimated_minutes" class="muted mono">
                                {{ topic.estimated_minutes }} min
                            </span>
                        </li>
                    </ul>
                </article>
            </div>
        </section>
    </div>
</template>

<style scoped>
.detail-page {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    padding: 1rem;
}
.detail-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
}
.detail-header__lead {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}
.detail-header h1 {
    margin: 0;
    font-family: var(--font-sans);
    font-size: 1.5rem;
}
.muted {
    color: var(--text-muted);
    margin: 0.25rem 0 0;
    font-weight: var(--font-normal);
}
.badges {
    display: flex;
    gap: 0.5rem;
}
.show-card {
    background: color-mix(in srgb, var(--bg-surface) 60%, transparent);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-2xl);
    padding: var(--space-6) var(--space-8);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}
.show-card__title {
    margin: 0 0 var(--space-5);
    font-size: var(--text-xl);
    font-weight: var(--font-bold);
    color: var(--text-primary);
}
.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(10rem, 1fr));
    gap: 0.75rem 1.25rem;
    margin: 0;
}
.detail-grid .span-2 {
    grid-column: 1 / -1;
}
.detail-grid dt {
    font-size: 0.75rem;
    color: var(--text-secondary);
}
.detail-grid dd {
    margin: 0.15rem 0 0;
}
.prose {
    margin: 0;
    white-space: pre-wrap;
}
.sessions {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}
.session h3 {
    margin: 0 0 var(--space-2);
    font-size: var(--text-base);
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
    align-items: baseline;
}
.session ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}
.session li {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-2) var(--space-3);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-md);
    background: var(--bg-card);
}
.mono {
    font-family: var(--font-mono, monospace);
    font-size: var(--text-xs);
}
</style>
