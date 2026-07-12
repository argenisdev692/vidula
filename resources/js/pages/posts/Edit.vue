<script setup lang="ts">
/**
 * Edit post — dedicated edit page (GET /posts/{uuid}/edit, VIEW_POSTS to open,
 * UPDATE_POSTS to submit). Same shell as Create, pre-filled from the full post
 * detail; the AI assist panel can still regenerate content on top of an
 * existing draft.
 */
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import BackLink from '@/common/ui/BackLink.vue';
import PostForm from './components/PostForm.vue';
import type { CategoryOption, PostDetail } from '@/modules/post/types';

defineOptions({ layout: AppLayout });

defineProps<{
    post: PostDetail;
    categories: CategoryOption[];
}>();
</script>

<template>
    <Head :title="`Edit ${post.post_title}`" />

    <AppHeader title="Edit post" subtitle="Update the content, metadata or regenerate with AI" />

    <PermissionGuard permission="UPDATE_POSTS">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to edit posts.</p>
            </div>
        </template>

        <div class="form-page">
            <BackLink href="/posts" label="Back to posts" />

            <article class="card">
                <PostForm mode="edit" :post="post" :categories="categories" />
            </article>
        </div>
    </PermissionGuard>
</template>

<style scoped>
.form-page {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    width: 100%;
    max-width: 78rem;
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

@media (max-width: 640px) {
    .card {
        padding: var(--space-5) var(--space-4);
    }
}
</style>
