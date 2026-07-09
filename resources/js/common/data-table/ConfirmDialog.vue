<script setup lang="ts">
/**
 * Reusable confirmation modal for destructive / reversible list actions (delete,
 * restore, bulk delete, bulk restore). Composes the shared {@see AppModal} shell
 * so every confirmation shares the same centered header (tone icon badge + title)
 * and standardized footer (text Cancel + tone GradientButton) instead of
 * `window.confirm()`. The confirm action receives autofocus and the dialog closes
 * on Escape / mask click.
 *
 * Layer: common/ — imports common/ui only, never modules/ or Pages/.
 */
import AppModal from '@/common/ui/AppModal.vue';

const visible = defineModel<boolean>('visible', { default: false });

withDefaults(
    defineProps<{
        title: string;
        message: string;
        confirmLabel?: string;
        cancelLabel?: string;
        confirmIcon?: string;
        tone?: 'danger' | 'success' | 'primary';
        loading?: boolean;
    }>(),
    {
        confirmLabel: 'Confirm',
        cancelLabel: 'Cancel',
        confirmIcon: 'pi pi-check',
        tone: 'danger',
        loading: false,
    },
);

const emit = defineEmits<{ confirm: []; cancel: [] }>();
</script>

<template>
    <AppModal
        v-model:visible="visible"
        :title="title"
        :icon="confirmIcon"
        :tone="tone"
        :confirm-label="confirmLabel"
        :confirm-icon="confirmIcon"
        :cancel-label="cancelLabel"
        :loading="loading"
        width="28rem"
        autofocus-confirm
        @confirm="emit('confirm')"
        @cancel="emit('cancel')"
    >
        <p class="confirm__message">{{ message }}</p>
    </AppModal>
</template>

<style scoped>
.confirm__message {
    margin: 0;
    text-align: center;
    font-size: var(--text-sm);
    color: var(--text-secondary);
    line-height: 1.5;
}
</style>
