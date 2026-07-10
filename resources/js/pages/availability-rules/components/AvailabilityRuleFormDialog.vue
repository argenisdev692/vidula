<script setup lang="ts">
/**
 * Create / edit modal for a weekly availability rule. There is no `GET /create`
 * or `GET /{uuid}/edit` route — the backend store/update return `back()`
 * redirects — so the form lives in the shared AppModal on the Index page and
 * submits JSON via Inertia `useForm`:
 *
 *   · create → POST /availability-rules
 *   · edit   → PUT  /availability-rules/{uuid}
 *
 * Built from the reusable common/form kit (SelectField, TimeField). Client-side
 * Zod validation mirrors the backend but the server stays authoritative (it also
 * enforces the cross-row no-overlap rule); server errors surface via `form.errors`.
 */
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import SelectField from '@/common/form/SelectField.vue';
import TimeField from '@/common/form/TimeField.vue';
import AppModal from '@/common/ui/AppModal.vue';
import ToggleSwitch from '@/volt/ToggleSwitch.vue';
import { DAY_OPTIONS } from '@/modules/availability/helpers/availabilityFormat';
import { availabilityRuleFormSchema } from '@/modules/availability/schemas/availabilityRuleFormSchema';
import type { AvailabilityRule } from '@/modules/availability/types';

const visible = defineModel<boolean>('visible', { default: false });

const props = withDefaults(
    defineProps<{
        mode?: 'create' | 'edit';
        rule?: AvailabilityRule | null;
    }>(),
    { mode: 'create', rule: null },
);

const emit = defineEmits<{ saved: [] }>();

interface RuleForm {
    day_of_week: string | null;
    start_time: string | null;
    end_time: string | null;
    is_available: boolean;
}

const form = useForm<RuleForm>({
    day_of_week: null,
    start_time: null,
    end_time: null,
    is_available: true,
});

const isEdit = computed<boolean>(() => props.mode === 'edit');
const dialogTitle = computed<string>(() => (isEdit.value ? 'Edit weekly rule' : 'New weekly rule'));

/** Re-seed the form each time the dialog opens (never carry stale state). */
watch(visible, (open) => {
    if (!open) {
        return;
    }
    form.clearErrors();
    form.day_of_week = props.rule ? String(props.rule.day_of_week) : null;
    form.start_time = props.rule?.start_time?.slice(0, 5) ?? null;
    form.end_time = props.rule?.end_time?.slice(0, 5) ?? null;
    form.is_available = props.rule?.is_available ?? true;
});

function close(): void {
    visible.value = false;
}

function submit(): void {
    const parsed = availabilityRuleFormSchema.safeParse({
        day_of_week: form.day_of_week ?? '',
        start_time: form.start_time ?? '',
        end_time: form.end_time ?? '',
        is_available: form.is_available,
    });

    if (!parsed.success) {
        form.clearErrors();
        for (const issue of parsed.error.issues) {
            const key = issue.path[0];
            if (typeof key === 'string') {
                form.setError(key as keyof RuleForm, issue.message);
            }
        }
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
        day_of_week: Number(data.day_of_week),
        start_time: data.start_time,
        end_time: data.end_time,
        is_available: data.is_available,
    }));

    if (isEdit.value && props.rule) {
        form.put(`/availability-rules/${props.rule.uuid}`, options);
    } else {
        form.post('/availability-rules', options);
    }
}
</script>

<template>
    <AppModal
        v-model:visible="visible"
        :title="dialogTitle"
        :subtitle="isEdit ? 'Update this recurring weekly window.' : 'Add a recurring window to the weekly template.'"
        icon="pi pi-clock"
        :confirm-label="isEdit ? 'Save changes' : 'Create rule'"
        confirm-icon="pi pi-check"
        :loading="form.processing"
        :dismissable="!form.processing"
        width="32rem"
        @confirm="submit"
        @cancel="close"
    >
        <form class="rule-form" @submit.prevent="submit">
            <SelectField
                v-model="form.day_of_week"
                name="day_of_week"
                label="Weekday"
                placeholder="Select a day"
                :options="DAY_OPTIONS"
                required
                :error="form.errors.day_of_week"
            />

            <div class="rule-form__row">
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

            <div class="rule-form__toggle">
                <div class="rule-form__toggle-copy">
                    <span class="rule-form__toggle-label">Available</span>
                    <span class="rule-form__toggle-hint">
                        On = a bookable window · Off = an explicit blocked window on that day.
                    </span>
                </div>
                <ToggleSwitch
                    v-model="form.is_available"
                    input-id="is_available"
                    aria-label="Mark this window as available"
                />
            </div>

            <!-- Hidden submit lets Enter submit the form from any field. -->
            <button type="submit" class="rule-form__enter" tabindex="-1" aria-hidden="true" />
        </form>
    </AppModal>
</template>

<style scoped>
.rule-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.rule-form__row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-4);
}

.rule-form__toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
    padding: var(--space-4);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--bg-elevated) 40%, transparent);
}

.rule-form__toggle-copy {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
}

.rule-form__toggle-label {
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--text-primary);
}

.rule-form__toggle-hint {
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.rule-form__enter {
    display: none;
}

@media (max-width: 520px) {
    .rule-form__row {
        grid-template-columns: 1fr;
    }
}
</style>
