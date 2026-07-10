<script setup lang="ts">
/**
 * Create / edit modal for a date exception (a closure, or a forced-open window
 * overriding the weekly template on a single date). No create/edit page route —
 * store/update return `back()` redirects — so the form lives in the shared
 * AppModal on the Index page and submits JSON via Inertia `useForm`:
 *
 *   · create → POST /availability-exceptions
 *   · edit   → PUT  /availability-exceptions/{uuid}
 *
 * When "Open" is off (a closure) the hours are hidden and sent as null; when on,
 * both hours are required. Built from the reusable common/form kit (DateField,
 * TimeField, TextField). Zod mirrors the backend; the server stays authoritative
 * (including the "one active exception per date" rule).
 */
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DateField from '@/common/form/DateField.vue';
import TimeField from '@/common/form/TimeField.vue';
import TextField from '@/common/form/TextField.vue';
import AppModal from '@/common/ui/AppModal.vue';
import ToggleSwitch from '@/volt/ToggleSwitch.vue';
import { availabilityExceptionFormSchema } from '@/modules/availability/schemas/availabilityExceptionFormSchema';
import type { AvailabilityException } from '@/modules/availability/types';

const visible = defineModel<boolean>('visible', { default: false });

const props = withDefaults(
    defineProps<{
        mode?: 'create' | 'edit';
        exception?: AvailabilityException | null;
    }>(),
    { mode: 'create', exception: null },
);

const emit = defineEmits<{ saved: [] }>();

interface ExceptionForm {
    date: string | null;
    is_available: boolean;
    start_time: string | null;
    end_time: string | null;
    reason: string;
}

const form = useForm<ExceptionForm>({
    date: null,
    is_available: false,
    start_time: null,
    end_time: null,
    reason: '',
});

const isEdit = computed<boolean>(() => props.mode === 'edit');
const dialogTitle = computed<string>(() => (isEdit.value ? 'Edit date exception' : 'New date exception'));

/** Today as a local `YYYY-MM-DD` (no UTC drift). */
function isoToday(): string {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

/**
 * A new exception can only be scheduled from today onward — the picker disables
 * past days. Editing keeps the field open so an already-past exception (e.g. a
 * holiday that has since passed) can still have its reason/hours adjusted.
 */
const minDate = computed<string | undefined>(() => (isEdit.value ? undefined : isoToday()));

/** Re-seed the form each time the dialog opens (never carry stale state). */
watch(visible, (open) => {
    if (!open) {
        return;
    }
    form.clearErrors();
    form.date = props.exception?.date ? props.exception.date.slice(0, 10) : null;
    form.is_available = props.exception?.is_available ?? false;
    form.start_time = props.exception?.start_time?.slice(0, 5) ?? null;
    form.end_time = props.exception?.end_time?.slice(0, 5) ?? null;
    form.reason = props.exception?.reason ?? '';
});

/** Clearing "Open" drops any entered hours so a closure never submits stale times. */
watch(
    () => form.is_available,
    (open) => {
        if (!open) {
            form.start_time = null;
            form.end_time = null;
        }
    },
);

function close(): void {
    visible.value = false;
}

function submit(): void {
    const parsed = availabilityExceptionFormSchema.safeParse({
        date: form.date ?? '',
        is_available: form.is_available,
        start_time: form.start_time,
        end_time: form.end_time,
        reason: form.reason || null,
    });

    if (!parsed.success) {
        form.clearErrors();
        for (const issue of parsed.error.issues) {
            const key = issue.path[0];
            if (typeof key === 'string') {
                form.setError(key as keyof ExceptionForm, issue.message);
            }
        }
        return;
    }

    // A new exception may not be scheduled in the past (the backend enforces this too).
    if (!isEdit.value && form.date && form.date < isoToday()) {
        form.clearErrors();
        form.setError('date', 'The date cannot be in the past.');
        return;
    }

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            close();
        },
    };

    form.transform((data) => ({
        date: data.date,
        is_available: data.is_available,
        start_time: data.is_available ? data.start_time : null,
        end_time: data.is_available ? data.end_time : null,
        reason: data.reason ? data.reason : null,
    }));

    if (isEdit.value && props.exception) {
        form.put(`/availability-exceptions/${props.exception.uuid}`, options);
    } else {
        form.post('/availability-exceptions', options);
    }
}
</script>

<template>
    <AppModal
        v-model:visible="visible"
        :title="dialogTitle"
        :subtitle="isEdit ? 'Update this date override.' : 'Override the weekly template on a single date.'"
        icon="pi pi-calendar-times"
        :confirm-label="isEdit ? 'Save changes' : 'Create exception'"
        confirm-icon="pi pi-check"
        :loading="form.processing"
        :dismissable="!form.processing"
        width="32rem"
        @confirm="submit"
        @cancel="close"
    >
        <form class="exc-form" @submit.prevent="submit">
            <DateField
                v-model="form.date"
                name="date"
                label="Date"
                placeholder="Select a date"
                required
                :min-date="minDate"
                :error="form.errors.date"
            />

            <div class="exc-form__toggle">
                <div class="exc-form__toggle-copy">
                    <span class="exc-form__toggle-label">Open on this date</span>
                    <span class="exc-form__toggle-hint">
                        On = a forced-open window (set the hours) · Off = a full closure.
                    </span>
                </div>
                <ToggleSwitch
                    v-model="form.is_available"
                    input-id="is_available"
                    aria-label="Force this date open"
                />
            </div>

            <div v-if="form.is_available" class="exc-form__row">
                <TimeField
                    v-model="form.start_time"
                    name="start_time"
                    label="Start time"
                    placeholder="09:00"
                    required
                    :error="form.errors.start_time"
                />
                <TimeField
                    v-model="form.end_time"
                    name="end_time"
                    label="End time"
                    placeholder="17:00"
                    required
                    :error="form.errors.end_time"
                />
            </div>

            <TextField
                v-model="form.reason"
                name="reason"
                label="Reason"
                placeholder="e.g. Public holiday, staff training…"
                :maxlength="255"
                :error="form.errors.reason"
                hint="Optional — up to 255 characters."
            />

            <!-- Hidden submit lets Enter submit the form from any field. -->
            <button type="submit" class="exc-form__enter" tabindex="-1" aria-hidden="true" />
        </form>
    </AppModal>
</template>

<style scoped>
.exc-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.exc-form__row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-4);
}

.exc-form__toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
    padding: var(--space-4);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--bg-elevated) 40%, transparent);
}

.exc-form__toggle-copy {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
}

.exc-form__toggle-label {
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--text-primary);
}

.exc-form__toggle-hint {
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.exc-form__enter {
    display: none;
}

@media (max-width: 520px) {
    .exc-form__row {
        grid-template-columns: 1fr;
    }
}
</style>
