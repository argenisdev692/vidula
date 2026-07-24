<script setup lang="ts">
/**
 * Create / edit modal for an LMS student. No dedicated create/edit routes —
 * store/update return back(), so the form lives in an AppModal on Index.
 *
 *   · create → POST /students
 *   · edit   → PUT  /students/{uuid}
 *
 * PhoneField emits E.164 (optional). Empty optional strings map to null on submit.
 */
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import TextField from '@/common/form/TextField.vue';
import TextareaField from '@/common/form/TextareaField.vue';
import PhoneField from '@/common/form/PhoneField.vue';
import SelectField from '@/common/form/SelectField.vue';
import ToggleSwitch from '@/volt/ToggleSwitch.vue';
import AppModal from '@/common/ui/AppModal.vue';
import { studentFormSchema, type StudentFormValues } from '@/modules/students/schemas/studentFormSchema';
import type { Student } from '@/modules/students/types';
import type { SelectOption } from '@/common/form/types';

const visible = defineModel<boolean>('visible', { default: false });

const props = withDefaults(
    defineProps<{
        mode?: 'create' | 'edit';
        student?: Student | null;
    }>(),
    { mode: 'create', student: null },
);

const emit = defineEmits<{ saved: [] }>();

const form = useForm<StudentFormValues>({
    name: '',
    email: '',
    phone: '',
    dni: '',
    address: '',
    avatar: '',
    notes: '',
    status: 'DRAFT',
    active: true,
});

const isEdit = computed<boolean>(() => props.mode === 'edit');
const dialogTitle = computed<string>(() => (isEdit.value ? 'Edit student' : 'New student'));

const phoneModel = computed<string | null>({
    get: () => form.phone || null,
    set: (value) => {
        form.phone = value ?? '';
    },
});

const statusModel = computed<string | null>({
    get: () => form.status,
    set: (value) => {
        form.status = (value as StudentFormValues['status']) || 'DRAFT';
    },
});

const lifecycleOptions: SelectOption[] = [
    { label: 'Draft', value: 'DRAFT' },
    { label: 'Active', value: 'ACTIVE' },
    { label: 'Archived', value: 'ARCHIVED' },
];

watch(visible, (open) => {
    if (!open) {
        return;
    }
    form.clearErrors();
    form.name = props.student?.name ?? '';
    form.email = props.student?.email ?? '';
    form.phone = props.student?.phone ?? '';
    form.dni = props.student?.dni ?? '';
    form.address = props.student?.address ?? '';
    form.avatar = props.student?.avatar ?? '';
    form.notes = props.student?.notes ?? '';
    form.status = props.student?.status ?? 'DRAFT';
    form.active = props.student?.active ?? true;
});

function close(): void {
    visible.value = false;
}

function emptyToNull(value: string): string | null {
    const trimmed = value.trim();
    return trimmed === '' ? null : trimmed;
}

function submit(): void {
    const parsed = studentFormSchema.safeParse({
        name: form.name,
        email: form.email,
        phone: form.phone,
        dni: form.dni,
        address: form.address,
        avatar: form.avatar,
        notes: form.notes,
        status: form.status,
        active: form.active,
    });

    if (!parsed.success) {
        form.clearErrors();
        for (const issue of parsed.error.issues) {
            const key = issue.path[0];
            if (typeof key === 'string') {
                form.setError(key as keyof StudentFormValues, issue.message);
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
        name: data.name.trim(),
        email: emptyToNull(data.email),
        phone: emptyToNull(data.phone),
        dni: emptyToNull(data.dni),
        address: emptyToNull(data.address),
        avatar: emptyToNull(data.avatar),
        notes: emptyToNull(data.notes),
        status: data.status,
        active: data.active,
    }));

    if (isEdit.value) {
        form.put(`/students/${props.student!.uuid}`, options);
    } else {
        form.post('/students', options);
    }
}
</script>

<template>
    <AppModal
        v-model:visible="visible"
        :title="dialogTitle"
        :subtitle="isEdit ? 'Update this learner’s profile.' : 'Add a learner to the academy catalog.'"
        icon="pi pi-graduation-cap"
        :confirm-label="isEdit ? 'Save changes' : 'Create student'"
        confirm-icon="pi pi-check"
        :loading="form.processing"
        :dismissable="!form.processing"
        width="40rem"
        @confirm="submit"
        @cancel="close"
    >
        <form class="student-form" @submit.prevent="submit">
            <TextField
                v-model="form.name"
                name="name"
                label="Name"
                placeholder="e.g. Ada Lovelace"
                required
                :maxlength="255"
                :error="form.errors.name"
            />

            <div class="student-form__row">
                <SelectField
                    v-model="statusModel"
                    name="status"
                    label="Lifecycle"
                    required
                    :options="lifecycleOptions"
                    :error="form.errors.status"
                />

                <PhoneField
                    v-model="phoneModel"
                    name="phone"
                    label="Phone"
                    :error="form.errors.phone"
                />
            </div>

            <TextField
                v-model="form.email"
                name="email"
                label="Email"
                type="email"
                placeholder="ada@academy.test"
                :maxlength="255"
                :error="form.errors.email"
            />

            <TextField
                v-model="form.dni"
                name="dni"
                label="DNI"
                :maxlength="50"
                :error="form.errors.dni"
            />

            <TextField
                v-model="form.address"
                name="address"
                label="Address"
                placeholder="Street address"
                :maxlength="255"
                :error="form.errors.address"
            />

            <TextField
                v-model="form.avatar"
                name="avatar"
                label="Avatar URL"
                placeholder="https://"
                :maxlength="2048"
                :error="form.errors.avatar"
            />

            <TextareaField
                v-model="form.notes"
                name="notes"
                label="Notes"
                placeholder="Internal notes…"
                :rows="3"
                :maxlength="5000"
                :error="form.errors.notes"
            />

            <div class="student-form__toggle">
                <ToggleSwitch v-model="form.active" input-id="active" aria-label="Active catalog flag" />
                <label class="student-form__toggle-label" for="active">
                    Active in the learner catalog.
                </label>
            </div>

            <button type="submit" class="student-form__enter" tabindex="-1" aria-hidden="true" />
        </form>
    </AppModal>
</template>

<style scoped>
.student-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.student-form__row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-4);
}

@media (max-width: 640px) {
    .student-form__row {
        grid-template-columns: 1fr;
    }
}

.student-form__toggle {
    display: flex;
    align-items: center;
    gap: var(--space-3);
}

.student-form__toggle-label {
    color: var(--text-secondary);
    font-size: var(--text-sm);
}

.student-form__enter {
    display: none;
}
</style>
