<script setup lang="ts">
/**
 * Blog category detail — read-only view rendered by GET /blog-categories/{uuid}
 * (VIEW_BLOG_CATEGORIES). The handler resolves the record `withTrashed`, so a
 * suspended category is viewable here; its status is shown via a badge.
 */
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import BackLink from '@/common/ui/BackLink.vue';
import { formatDate } from '@/modules/blog/helpers/formatDate';
import type { SharedProps } from '@/types/inertia';
import type { BlogCategory } from '@/modules/blog/types';

defineOptions({ layout: AppLayout });

/** The detail render returns the full model (adds `updated_at` over list rows). */
type BlogCategoryDetail = BlogCategory & { updated_at?: string | null };

const props = defineProps<{
    blogCategory: BlogCategoryDetail;
}>();

usePage<SharedProps>();

const isSuspended = computed<boolean>(() => props.blogCategory.deleted_at !== null);
const name = computed<string>(() => props.blogCategory.blog_category_name ?? 'Untitled category');
</script>

<template>
    <Head :title="name" />

    <AppHeader title="Blog Category" subtitle="Category detail" />

    <PermissionGuard permission="VIEW_BLOG_CATEGORIES">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to view this blog category.</p>
            </div>
        </template>

        <div class="detail">
            <BackLink href="/blog-categories" label="Back to categories" />

            <article class="card">
                <div class="card__head">
                    <h2 class="card__title">{{ name }}</h2>
                    <span class="badge" :class="isSuspended ? 'badge--suspended' : 'badge--active'">
                        {{ isSuspended ? 'Suspended' : 'Active' }}
                    </span>
                </div>

                <div class="card__media">
                    <img
                        v-if="blogCategory.image_url"
                        :src="blogCategory.image_url"
                        :alt="`${name} cover image`"
                        class="cover"
                    />
                    <div v-else class="cover cover--empty" aria-hidden="true">
                        <i class="pi pi-image" />
                        <span>No cover image</span>
                    </div>
                </div>

                <dl class="facts">
                    <div class="fact fact--wide">
                        <dt>Description</dt>
                        <dd>{{ blogCategory.blog_category_description || '—' }}</dd>
                    </div>
                    <div class="fact">
                        <dt>Created</dt>
                        <dd>{{ formatDate(blogCategory.created_at) }}</dd>
                    </div>
                    <div class="fact">
                        <dt>Last updated</dt>
                        <dd>{{ formatDate(blogCategory.updated_at ?? null) }}</dd>
                    </div>
                </dl>
            </article>
        </div>
    </PermissionGuard>
</template>

<style scoped>
.detail {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    width: 100%;
    max-width: 48rem;
    margin-inline: auto;
}

.card {
    background: color-mix(in srgb, var(--bg-surface) 60%, transparent);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-2xl);
    padding: var(--space-6) var(--space-8);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

.card__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
    margin-bottom: var(--space-5);
}

.card__title {
    margin: 0;
    font-size: var(--text-xl);
    font-weight: var(--font-bold);
    color: var(--text-primary);
}

.card__media {
    margin-bottom: var(--space-6);
}

.cover {
    width: 100%;
    max-height: 20rem;
    object-fit: cover;
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-subtle);
    background: var(--bg-elevated);
}

.cover--empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
    height: 10rem;
    color: var(--text-muted);
    font-size: var(--text-sm);
}

.cover--empty .pi {
    font-size: var(--text-2xl);
}

.facts {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-5);
    margin: 0;
}

.fact--wide {
    grid-column: 1 / -1;
}

.fact dt {
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--text-muted);
    margin-bottom: var(--space-1);
}

.fact dd {
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-primary);
    line-height: 1.5;
}

.badge {
    display: inline-flex;
    align-items: center;
    padding: 2px var(--space-3);
    border-radius: var(--radius-sm);
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
}

.badge--active {
    background: color-mix(in srgb, var(--accent-success) 18%, transparent);
    color: var(--accent-success);
}

.badge--suspended {
    background: color-mix(in srgb, var(--accent-error) 18%, transparent);
    color: var(--accent-error);
}

.empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-16) var(--space-6);
    color: var(--text-muted);
}

.empty .pi {
    font-size: var(--text-3xl);
}

@media (max-width: 560px) {
    .facts {
        grid-template-columns: 1fr;
    }

    .card {
        padding: var(--space-5) var(--space-4);
    }
}
</style>
