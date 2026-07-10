<script setup lang="ts">
/**
 * Blog category detail — read-only view rendered by GET /blog-categories/{uuid}
 * (VIEW_BLOG_CATEGORIES). The handler resolves the record `withTrashed`, so a
 * suspended category is viewable here; its status is shown via a badge. The
 * detail chrome (back link, glass card, facts styling) lives in the shared
 * {@see DetailCard}; only the cover-image block is specific to this page.
 */
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import DetailCard from '@/common/ui/DetailCard.vue';
import StatusBadge from '@/common/ui/StatusBadge.vue';
import { formatDate } from '@/modules/blog/helpers/formatDate';
import type { BlogCategory } from '@/modules/blog/types';

defineOptions({ layout: AppLayout });

/** The detail render returns the full model (adds `updated_at` over list rows). */
type BlogCategoryDetail = BlogCategory & { updated_at?: string | null };

const props = defineProps<{
    blogCategory: BlogCategoryDetail;
}>();

const isSuspended = computed<boolean>(() => props.blogCategory.deleted_at !== null);
const name = computed<string>(() => props.blogCategory.blog_category_name ?? 'Untitled category');
</script>

<template>
    <Head :title="name" />

    <DetailCard
        header-title="Blog Category"
        header-subtitle="Category detail"
        permission="VIEW_BLOG_CATEGORIES"
        fallback-text="You don't have permission to view this blog category."
        back-href="/blog-categories"
        back-label="Back to categories"
        :title="name"
    >
        <template #badges>
            <StatusBadge
                :tone="isSuspended ? 'danger' : 'success'"
                :label="isSuspended ? 'Suspended' : 'Active'"
            />
        </template>

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
    </DetailCard>
</template>

<style scoped>
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
</style>
