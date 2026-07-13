<script setup lang="ts">
/**
 * Edit content — dedicated review/edit page (GET /social-media/{uuid}/edit,
 * VIEW_SOCIAL_MEDIA to open, UPDATE_SOCIAL_MEDIA to submit). Content is
 * always AI-born, so this screen is a review pass over an already-generated
 * package rather than a from-scratch editor — it also doubles as the landing
 * page a `generating` row resolves to right after the wizard kicks off the
 * job, in which case the form is replaced by a live progress view.
 */
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import BackLink from '@/common/ui/BackLink.vue';
import AiProgressBar from './components/AiProgressBar.vue';
import SocialMediaContentForm from './components/SocialMediaContentForm.vue';
import { useAiGenerationProgress } from '@/modules/social-media/composables/useAiGenerationProgress';
import type { SocialMediaContentDetail } from '@/modules/social-media/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    content: SocialMediaContentDetail;
}>();

const { progress } = useAiGenerationProgress();

const activeProgress = computed(() =>
    progress.value?.content_uuid === props.content.uuid ? progress.value : null,
);

const isGenerating = computed<boolean>(() => props.content.status === 'generating');

function refresh(): void {
    router.reload({ only: ['content'] });
}
</script>

<template>
    <Head :title="`Edit ${content.topic}`" />

    <AppHeader title="Review content" subtitle="Fine-tune the AI-generated package before publishing" />

    <div class="form-page">
        <BackLink href="/social-media" label="Back to social media" />

        <article class="card">
            <section v-if="isGenerating" class="generating">
                <i class="pi pi-sparkles" aria-hidden="true" />
                <h2>Still generating…</h2>
                <p>The quality-loop agent is working on “{{ content.topic }}”. This page will update automatically.</p>
                <AiProgressBar
                    :message="activeProgress?.message ?? 'Running the quality-loop…'"
                    :percent="activeProgress?.progress ?? 5"
                />
                <button type="button" class="generating__refresh" @click="refresh">
                    <i class="pi pi-refresh" aria-hidden="true" /> Refresh
                </button>
            </section>

            <PermissionGuard v-else permission="UPDATE_SOCIAL_MEDIA">
                <template #fallback>
                    <div class="empty">
                        <i class="pi pi-lock" aria-hidden="true" />
                        <p>You don't have permission to edit social media content.</p>
                    </div>
                </template>

                <SocialMediaContentForm :content="content" />
            </PermissionGuard>
        </article>
    </div>
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

.generating {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-10) var(--space-6);
    text-align: center;
}

.generating .pi-sparkles {
    font-size: var(--text-3xl);
    color: var(--accent-primary);
}

.generating h2 {
    margin: 0;
    font-size: var(--text-lg);
    color: var(--text-primary);
}

.generating p {
    margin: 0;
    max-width: 32rem;
    font-size: var(--text-sm);
    color: var(--text-muted);
}

.generating .ai-progress {
    width: 100%;
    max-width: 24rem;
}

.generating__refresh {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    margin-top: var(--space-2);
    padding: var(--space-2) var(--space-4);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    background: transparent;
    color: var(--text-secondary);
    cursor: pointer;
    transition: border-color var(--transition), color var(--transition);
}

.generating__refresh:hover {
    border-color: var(--accent-primary);
    color: var(--accent-primary);
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
