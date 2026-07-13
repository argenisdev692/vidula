<script setup lang="ts">
/** Real-time progress bar for the AI generation job — fed by `useAiGenerationProgress`. */
defineProps<{
    message: string;
    percent: number;
}>();
</script>

<template>
    <div class="ai-progress">
        <p class="ai-progress__message"><i class="pi pi-spin pi-spinner" aria-hidden="true" /> {{ message }}</p>
        <div class="ai-progress__track">
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
</style>
