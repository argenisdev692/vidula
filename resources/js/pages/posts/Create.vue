<script setup lang="ts">
/**
 * New post — dedicated create page (GET /posts/create, CREATE_POSTS). Same
 * shell as Users invite (BackLink + Volt Card), wider to fit the content
 * editor + AI assist panel side by side.
 */
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import BackLink from '@/common/ui/BackLink.vue';
import Card from '@/volt/Card.vue';
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

            <Card class="form-card">
                <template #content>
                    <PostForm mode="create" :categories="categories" />
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
