<script setup lang="ts">
/**
 * Edit campaign — dedicated review/edit page (GET /campaigns/{uuid}/edit,
 * VIEW_CAMPAIGNS to open, UPDATE_CAMPAIGNS to submit). A campaign is always
 * AI-born, so this screen is a review pass over an already-generated Meta
 * Ads package rather than a from-scratch editor — it also doubles as the
 * landing page a `generating` row resolves to right after the wizard kicks
 * off the job, in which case the form is replaced by a live progress view.
 */
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import BackLink from '@/common/ui/BackLink.vue';
import SecondaryButton from '@/volt/SecondaryButton.vue';
import AiProgressBar from './components/AiProgressBar.vue';
import CampaignForm from './components/CampaignForm.vue';
import { useAiGenerationProgress } from '@/modules/campaigns/composables/useAiGenerationProgress';
import type { CampaignDetail } from '@/modules/campaigns/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    campaign: CampaignDetail;
}>();

const { progress } = useAiGenerationProgress();

const activeProgress = computed(() =>
    progress.value?.campaign_uuid === props.campaign.uuid ? progress.value : null,
);

const isGenerating = computed<boolean>(() => props.campaign.status === 'generating');

function refresh(): void {
    router.reload({ only: ['campaign'] });
}
</script>

<template>
    <Head :title="`Edit ${campaign.topic}`" />

    <AppHeader title="Review campaign" subtitle="Fine-tune the AI-generated Meta Ads package before publishing" />

    <div class="form-page">
        <BackLink href="/campaigns" label="Back to campaigns" />

        <article class="card">
            <section v-if="isGenerating" class="generating">
                <i class="pi pi-sparkles" aria-hidden="true" />
                <h2>Still generating…</h2>
                <p>The quality-loop agent is working on “{{ campaign.topic }}”. This page will update automatically.</p>
                <AiProgressBar
                    :message="activeProgress?.message ?? 'Running the quality-loop…'"
                    :percent="activeProgress?.progress ?? 5"
                />
                <SecondaryButton
                    type="button"
                    label="Refresh"
                    icon="pi pi-refresh"
                    @click="refresh"
                />
            </section>

            <PermissionGuard v-else permission="UPDATE_CAMPAIGNS">
                <template #fallback>
                    <div class="empty">
                        <i class="pi pi-lock" aria-hidden="true" />
                        <p>You don't have permission to edit campaigns.</p>
                    </div>
                </template>

                <CampaignForm :campaign="campaign" />
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
