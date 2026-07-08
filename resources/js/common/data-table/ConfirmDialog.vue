<script setup lang="ts">
/**
 * Reusable confirmation modal for destructive / reversible list actions (delete,
 * restore, bulk delete, bulk restore). Wraps the Volt Dialog + Volt Button so
 * every module confirms through the same accessible modal instead of
 * `window.confirm()`. The confirm button receives autofocus and the dialog
 * closes on Escape / mask click (Volt Dialog defaults).
 *
 * The Volt Button pass-through only styles the `primary` tone, so the `danger` /
 * `success` accent is applied via a scoped class override (the SubmitButton
 * precedent) rather than the cosmetically-inert `severity` prop.
 *
 * Layer: common/ — imports volt/ only, never modules/ or Pages/.
 */
import Dialog from '@/volt/Dialog.vue';
import Button from '@/volt/Button.vue';

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

function cancel(): void {
    emit('cancel');
    visible.value = false;
}
</script>

<template>
    <Dialog
        v-model:visible="visible"
        :header="title"
        modal
        :draggable="false"
        :dismissable-mask="true"
        :style="{ width: '28rem', maxWidth: '92vw' }"
    >
        <p class="confirm__message">{{ message }}</p>

        <template #footer>
            <Button :label="cancelLabel" text severity="secondary" :disabled="loading" @click="cancel" />
            <Button
                class="confirm__action"
                :class="`confirm__action--${tone}`"
                :label="confirmLabel"
                :icon="loading ? undefined : confirmIcon"
                :loading="loading"
                autofocus
                @click="emit('confirm')"
            />
        </template>
    </Dialog>
</template>

<style scoped>
.confirm__message {
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-secondary);
    line-height: 1.5;
}

/* Tone accents — override the Volt Button primary background via token colours. */
.confirm__action--danger:not(:disabled):not(.p-disabled) {
    background: var(--accent-error) !important;
    border-color: var(--accent-error) !important;
    color: var(--on-accent, #fff) !important;
}

.confirm__action--success:not(:disabled):not(.p-disabled) {
    background: var(--accent-success) !important;
    border-color: var(--accent-success) !important;
    color: var(--on-accent, #fff) !important;
}
</style>
