<script setup lang="ts">
/**
 * Create / edit enrollment dialog.
 */
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import SelectField from '@/common/form/SelectField.vue';
import DateField from '@/common/form/DateField.vue';
import TextField from '@/common/form/TextField.vue';
import TextareaField from '@/common/form/TextareaField.vue';
import AppModal from '@/common/ui/AppModal.vue';
import { toLocalIsoDate } from '@/lib/date';
import {
    enrollmentFormSchema,
    type EnrollmentFormValues,
} from '@/modules/enrollments/schemas/enrollmentFormSchema';
import type {
    Enrollment,
    EnrollmentClassroomOption,
    EnrollmentStudentOption,
} from '@/modules/enrollments/types';
import type { SelectOption } from '@/common/form/types';

const visible = defineModel<boolean>('visible', { default: false });

const props = withDefaults(
    defineProps<{
        mode?: 'create' | 'edit';
        enrollment?: Enrollment | null;
        students: EnrollmentStudentOption[];
        classrooms: EnrollmentClassroomOption[];
    }>(),
    { mode: 'create', enrollment: null },
);

const emit = defineEmits<{ saved: [] }>();

const form = useForm<EnrollmentFormValues>({
    student_uuid: '',
    classroom_uuid: '',
    enrolled_at: toLocalIsoDate(new Date()) ?? '',
    enrollment_status: 'active',
    final_grade: null,
    notes: '',
});

const isEdit = computed<boolean>(() => props.mode === 'edit');
const dialogTitle = computed<string>(() => (isEdit.value ? 'Edit enrollment' : 'New enrollment'));

const studentOptions = computed<SelectOption[]>(() =>
    props.students.map((row) => ({ label: `${row.name}${row.email ? ` (${row.email})` : ''}`, value: row.uuid })),
);

const classroomOptions = computed<SelectOption[]>(() =>
    props.classrooms.map((row) => ({ label: row.title, value: row.uuid })),
);

const statusOptions: SelectOption[] = [
    { label: 'Active', value: 'active' },
    { label: 'Suspended', value: 'suspended' },
    { label: 'Completed', value: 'completed' },
    { label: 'Dropped', value: 'dropped' },
];

const studentModel = computed<string | null>({
    get: () => form.student_uuid || null,
    set: (value) => {
        form.student_uuid = value ?? '';
    },
});

const classroomModel = computed<string | null>({
    get: () => form.classroom_uuid || null,
    set: (value) => {
        form.classroom_uuid = value ?? '';
    },
});

const statusModel = computed<string | null>({
    get: () => form.enrollment_status,
    set: (value) => {
        form.enrollment_status = (value as EnrollmentFormValues['enrollment_status']) || 'active';
    },
});

const enrolledAtModel = computed<string | null>({
    get: () => form.enrolled_at || null,
    set: (value) => {
        form.enrolled_at = value ?? '';
    },
});

const finalGradeModel = computed<string>({
    get: () => (form.final_grade === null || form.final_grade === undefined ? '' : String(form.final_grade)),
    set: (value) => {
        const trimmed = value.trim();
        if (trimmed === '') {
            form.final_grade = null;
            return;
        }
        const parsed = Number(trimmed);
        form.final_grade = Number.isFinite(parsed) ? parsed : null;
    },
});

watch(visible, (open) => {
    if (!open) {
        return;
    }
    form.clearErrors();
    if (isEdit.value && props.enrollment) {
        form.student_uuid = props.enrollment.student?.uuid ?? '';
        form.classroom_uuid = props.enrollment.classroom?.uuid ?? '';
        form.enrolled_at = props.enrollment.enrolled_at?.slice(0, 10) ?? '';
        form.enrollment_status = props.enrollment.enrollment_status;
        form.final_grade =
            props.enrollment.final_grade !== null && props.enrollment.final_grade !== undefined
                ? Number(props.enrollment.final_grade)
                : null;
        form.notes = props.enrollment.notes ?? '';
        return;
    }
    form.student_uuid = '';
    form.classroom_uuid = '';
    form.enrolled_at = toLocalIsoDate(new Date()) ?? '';
    form.enrollment_status = 'active';
    form.final_grade = null;
    form.notes = '';
});

function close(): void {
    visible.value = false;
}

function submit(): void {
    const parsed = enrollmentFormSchema.safeParse(form.data());
    if (!parsed.success) {
        form.clearErrors();
        for (const issue of parsed.error.issues) {
            const path = issue.path.join('.');
            if (path) {
                form.setError(path as keyof EnrollmentFormValues, issue.message);
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
        student_uuid: data.student_uuid,
        classroom_uuid: data.classroom_uuid,
        enrolled_at: data.enrolled_at,
        enrollment_status: data.enrollment_status,
        final_grade: data.final_grade,
        notes: data.notes.trim() === '' ? null : data.notes.trim(),
    }));

    if (isEdit.value) {
        form.put(`/enrollments/${props.enrollment!.uuid}`, options);
    } else {
        form.post('/enrollments', options);
    }
}
</script>

<template>
    <AppModal
        v-model:visible="visible"
        :title="dialogTitle"
        :subtitle="isEdit ? 'Update enrollment details.' : 'Enroll a student into a classroom.'"
        icon="pi pi-users"
        :confirm-label="isEdit ? 'Save changes' : 'Enroll student'"
        confirm-icon="pi pi-check"
        :loading="form.processing"
        :dismissable="!form.processing"
        width="32rem"
        @confirm="submit"
        @cancel="close"
    >
        <form class="enrollment-form" @submit.prevent="submit">
            <SelectField
                v-model="studentModel"
                name="student_uuid"
                label="Student"
                required
                filter
                :options="studentOptions"
                placeholder="Select student"
                :error="form.errors.student_uuid"
            />
            <SelectField
                v-model="classroomModel"
                name="classroom_uuid"
                label="Classroom"
                required
                filter
                :options="classroomOptions"
                placeholder="Select classroom"
                :error="form.errors.classroom_uuid"
            />
            <DateField
                v-model="enrolledAtModel"
                name="enrolled_at"
                label="Enrolled at"
                required
                :error="form.errors.enrolled_at"
            />
            <SelectField
                v-model="statusModel"
                name="enrollment_status"
                label="Status"
                required
                :options="statusOptions"
                :error="form.errors.enrollment_status"
            />
            <TextField
                v-model="finalGradeModel"
                name="final_grade"
                label="Final grade"
                type="number"
                inputmode="decimal"
                placeholder="0–100 (optional)"
                :error="form.errors.final_grade"
            />
            <TextareaField
                v-model="form.notes"
                name="notes"
                label="Notes"
                :rows="3"
                :error="form.errors.notes"
            />
        </form>
    </AppModal>
</template>

<style scoped>
.enrollment-form {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    font-family: var(--font-sans);
}
</style>
