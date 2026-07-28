<script setup lang="ts">
/**
 * Shared real-time AI job progress bar (campaigns / posts / social / products).
 * Fed by Echo progress events or polled status percent.
 */
defineProps<{
    message: string;
    percent: number;
}>();
</script>

<template>
    <div class="ai-progress" role="status" aria-live="polite">
        <p class="ai-progress__message">
            <i class="pi pi-spin pi-spinner" aria-hidden="true" />
            {{ message }}
        </p>
        <div
            class="ai-progress__track"
            role="progressbar"
            :aria-valuenow="percent"
            aria-valuemin="0"
            aria-valuemax="100"
        >
            <div class="ai-progress__fill" :style="{ width: `${percent}%` }" />
        </div>
    </div>
</template>

<style scoped>
.ai-progress {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
}

.ai-progress__message {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    margin: 0;
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.ai-progress__track {
    width: 100%;
    height: 4px;
    border-radius: var(--radius-full, 99px);
    background: var(--border-subtle);
    overflow: hidden;
}

.ai-progress__fill {
    height: 100%;
    border-radius: var(--radius-full, 99px);
    background: var(--grad-primary, var(--accent-primary));
    transition: width 0.3s ease;
}

@media (prefers-reduced-motion: reduce) {
    .ai-progress__fill {
        transition: none;
    }

    .ai-progress__message .pi-spin {
        animation: none;
    }
}
</style>
