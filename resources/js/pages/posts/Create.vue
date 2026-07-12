<script setup lang="ts">
/**
 * New post — dedicated create page (GET /posts/create, CREATE_POSTS). Same
 * shell as the other detail screens (BackLink + card), wider than the Users
 * form to fit the content editor + AI assist panel side by side.
 */
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import BackLink from '@/common/ui/BackLink.vue';
import PostForm from './components/PostForm.vue';
import type { CategoryOption } from '@/modules/post/types';

defineOptions({ layout: AppLayout });

defineProps<{
    categories: CategoryOption[];
}>();
</script>

<template>
    <Head title="New post" />

    <AppHeader title="New post" subtitle="Write manually or generate a SEO/EEAT-scored draft with AI" />

    <PermissionGuard permission="CREATE_POSTS">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to create posts.</p>
            </div>
        </template>

        <div class="form-page">
            <BackLink href="/posts" label="Back to posts" />

            <article class="card">
                <PostForm mode="create" :categories="categories" />
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
