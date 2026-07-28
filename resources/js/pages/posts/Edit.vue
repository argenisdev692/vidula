<script setup lang="ts">
/**
 * Edit / view post — dedicated page (GET /posts/{uuid}/edit). Shell uses
 * VIEW_POSTS so the table's View action can open the page; submit controls
 * inside {@see PostForm} remain gated by UPDATE_POSTS.
 */
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import BackLink from '@/common/ui/BackLink.vue';
import Card from '@/volt/Card.vue';
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

    <PermissionGuard permission="VIEW_POSTS">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to view posts.</p>
            </div>
        </template>

        <div class="form-page">
            <BackLink href="/posts" label="Back to posts" />

            <Card class="form-card">
                <template #content>
                    <PostForm mode="edit" :post="post" :categories="categories" />
                </template>
            </Card>
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

.form-card {
    width: 100%;
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
</style>
