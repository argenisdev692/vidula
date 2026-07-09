<script setup lang="ts">
/**
 * Reusable modal shell — a single, consistent dialog chrome for the whole app:
 * a centered header (optional icon badge · title · subtitle · gradient accent),
 * a scrollable body slot, and a standardized footer (text Cancel + GradientButton
 * confirm). Every FormDialog and the shared ConfirmDialog compose this instead of
 * hand-rolling a Volt Dialog + footer, so titles, spacing and buttons never drift.
 *
 * Wraps @/volt/Dialog.vue with `:closable="false"` and renders its own centered
 * close affordance so the title can be truly centered (the Volt header is a
 * left/right flex row). Focus is trapped by the underlying PrimeVue Dialog;
 * `autofocusConfirm` additionally moves focus onto the confirm action.
 *
 * Layer: common/ — imports volt/ + common/form only, never modules/ or Pages/.
 */
import { nextTick, ref, watch } from 'vue';
import Dialog from '@/volt/Dialog.vue';
import Button from '@/volt/Button.vue';
import GradientButton from '@/common/form/GradientButton.vue';

const visible = defineModel<boolean>('visible', { default: false });

const props = withDefaults(
    defineProps<{
        title: string;
        subtitle?: string;
        /** primeicons class for the header badge, e.g. `pi pi-user`. */
        icon?: string;
        tone?: 'primary' | 'danger' | 'success';
        width?: string;
        confirmLabel?: string;
        confirmIcon?: string;
        cancelLabel?: string;
        loading?: boolean;
        /** Close on mask click / Escape. Pass `!processing` while submitting. */
        dismissable?: boolean;
        showFooter?: boolean;
        confirmDisabled?: boolean;
        autofocusConfirm?: boolean;
    }>(),
    {
        subtitle: '',
        icon: '',
        tone: 'primary',
        width: '32rem',
        confirmLabel: 'Confirm',
        confirmIcon: 'pi pi-check',
        cancelLabel: 'Cancel',
        loading: false,
        dismissable: true,
        showFooter: true,
        confirmDisabled: false,
        autofocusConfirm: false,
    },
);

const emit = defineEmits<{ confirm: []; cancel: [] }>();

const confirmRef = ref<InstanceType<typeof GradientButton> | null>(null);

function cancel(): void {
    emit('cancel');
    visible.value = false;
}

watch(visible, (open) => {
    if (open && props.autofocusConfirm) {
        void nextTick(() => confirmRef.value?.focus());
    }
});
</script>

<template>
    <Dialog
        v-model:visible="visible"
        :closable="false"
        modal
        :draggable="false"
        :dismissable-mask="dismissable"
        :style="{ width, maxWidth: '96vw' }"
    >
        <div class="modal">
            <button type="button" class="modal__close" aria-label="Close" @click="cancel">
                <i class="pi pi-times" aria-hidden="true" />
            </button>

            <header class="modal__header">
                <span v-if="icon" class="modal__icon" :class="`modal__icon--${tone}`">
                    <i :class="icon" aria-hidden="true" />
                </span>
                <h2 class="modal__title">{{ title }}</h2>
                <p v-if="subtitle" class="modal__subtitle">{{ subtitle }}</p>
                <span class="modal__accent" :class="`modal__accent--${tone}`" aria-hidden="true" />
            </header>

            <div class="modal__body">
                <slot />
            </div>
        </div>

        <template #footer>
            <div v-if="showFooter" class="modal__footer">
                <slot name="footer">
                    <Button
                        :label="cancelLabel"
                        text
                        severity="secondary"
                        :disabled="loading"
                        @click="cancel"
                    />
                    <GradientButton
                        ref="confirmRef"
                        :label="confirmLabel"
                        :icon="confirmIcon"
                        :tone="tone"
                        :loading="loading"
                        :disabled="confirmDisabled"
                        @click="emit('confirm')"
                    />
                </slot>
            </div>
        </template>
    </Dialog>
</template>

<style scoped>
.modal {
    position: relative;
    padding-top: var(--space-2);
}

.modal__close {
    position: absolute;
    top: 0;
    right: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border: none;
    border-radius: var(--radius-md);
    background: transparent;
    color: var(--text-muted);
    cursor: pointer;
    transition: background var(--transition), color var(--transition);
}

.modal__close:hover {
    background: color-mix(in srgb, var(--text-primary) 8%, transparent);
    color: var(--text-primary);
}

.modal__header {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: var(--space-2);
    padding: var(--space-2) var(--space-6) var(--space-5);
}

.modal__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    margin-bottom: var(--space-1);
    border-radius: var(--radius-xl);
    color: var(--on-accent);
    font-size: var(--text-xl);
}

.modal__icon--primary {
    background: linear-gradient(135deg, var(--blue-500) 0%, var(--cyan) 100%);
    box-shadow: 0 6px 18px color-mix(in srgb, var(--accent-primary) 35%, transparent);
}

.modal__icon--danger {
    background: linear-gradient(135deg, var(--accent-error) 0%, color-mix(in srgb, var(--accent-error) 70%, var(--bg-void)) 100%);
    box-shadow: 0 6px 18px color-mix(in srgb, var(--accent-error) 35%, transparent);
}

.modal__icon--success {
    background: linear-gradient(135deg, var(--accent-success) 0%, color-mix(in srgb, var(--accent-success) 70%, var(--bg-void)) 100%);
    box-shadow: 0 6px 18px color-mix(in srgb, var(--accent-success) 35%, transparent);
}

.modal__title {
    margin: 0;
    font-size: var(--text-xl);
    font-weight: var(--font-bold);
    color: var(--text-primary);
    line-height: 1.2;
}

.modal__subtitle {
    margin: 0;
    max-width: 34rem;
    font-size: var(--text-sm);
    color: var(--text-secondary);
    line-height: 1.5;
}

.modal__accent {
    width: 3rem;
    height: 3px;
    margin-top: var(--space-1);
    border-radius: 999px;
}

.modal__accent--primary {
    background: linear-gradient(90deg, var(--blue-500), var(--cyan));
}

.modal__accent--danger {
    background: var(--accent-error);
}

.modal__accent--success {
    background: var(--accent-success);
}

.modal__body {
    display: flex;
    flex-direction: column;
}

.modal__footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: var(--space-3);
    width: 100%;
}
</style>
