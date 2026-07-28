<script setup lang="ts">
/**
 * Lista Asistencia — mark attendance per product session for one classroom.
 * Exports reuse buildExportUrl; mutations go through Inertia PUT (session auth).
 */
import { computed, reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import SelectField from '@/common/form/SelectField.vue';
import Button from '@/volt/Button.vue';
import { buildExportUrl } from '@/lib/queryParams';
import type {
    AttendanceEnrollmentRow,
    AttendanceMark,
    AttendanceSession,
    AttendanceStatus,
} from '@/modules/enrollments/types';
import type { SelectOption } from '@/common/form/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    classroom: {
        uuid: string;
        product?: { title: string; type: string } | null;
    };
    sessions: AttendanceSession[];
    enrollments: AttendanceEnrollmentRow[];
    marks: AttendanceMark[];
}>();

const toast = useToast();
const saving = ref<boolean>(false);

const statusOptions: SelectOption[] = [
    { label: 'Present', value: 'present' },
    { label: 'Absent', value: 'absent' },
    { label: 'Late', value: 'late' },
    { label: 'Justified', value: 'justified' },
];

const grid = reactive<Record<string, AttendanceStatus>>({});

for (const enrollment of props.enrollments) {
    for (const session of props.sessions) {
        const key = `${enrollment.uuid}|${session.uuid}`;
        const existing = props.marks.find(
            (mark) => mark.enrollment_uuid === enrollment.uuid && mark.product_session_uuid === session.uuid,
        );
        grid[key] = existing?.attendance_status ?? 'present';
    }
}

const title = computed<string>(() => props.classroom.product?.title ?? 'Attendance sheet');

const exportCsvUrl = computed<string>(() =>
    buildExportUrl(`/enrollments/attendance/${props.classroom.uuid}/export`, {}, 'csv'),
);
const exportXlsxUrl = computed<string>(() =>
    buildExportUrl(`/enrollments/attendance/${props.classroom.uuid}/export`, {}, 'xlsx'),
);
const exportPdfUrl = computed<string>(() =>
    buildExportUrl(`/enrollments/attendance/${props.classroom.uuid}/export`, {}, 'pdf'),
);

function cellKey(enrollmentUuid: string, sessionUuid: string): string {
    return `${enrollmentUuid}|${sessionUuid}`;
}

function save(): void {
    const marks = props.enrollments.flatMap((enrollment) =>
        props.sessions.map((session) => ({
            enrollment_uuid: enrollment.uuid,
            product_session_uuid: session.uuid,
            attendance_status: grid[cellKey(enrollment.uuid, session.uuid)] ?? 'present',
            observation: null,
            date: session.session_date,
        })),
    );

    saving.value = true;
    router.put(
        `/enrollments/attendance/${props.classroom.uuid}`,
        { marks },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Attendance saved', life: 3000 });
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}
</script>

<template>
    <Head :title="title" />

    <AppHeader :title="title" subtitle="Mark attendance per session (Lista Asistencia)." />

    <div class="sheet">
        <div class="toolbar">
            <Link href="/enrollments" class="back-link" prefetch>
                <i class="pi pi-arrow-left" aria-hidden="true" />
                Back to enrollments
            </Link>
            <div class="toolbar__actions">
                <PermissionGuard permission="EXPORT_ENROLLMENTS">
                    <a :href="exportCsvUrl" class="export-link" aria-label="Export CSV">CSV</a>
                    <a :href="exportXlsxUrl" class="export-link" aria-label="Export Excel">Excel</a>
                    <a :href="exportPdfUrl" class="export-link" aria-label="Export PDF">PDF</a>
                </PermissionGuard>
                <PermissionGuard permission="UPDATE_ENROLLMENTS">
                    <Button
                        type="button"
                        label="Save attendance"
                        icon="pi pi-check"
                        :loading="saving"
                        :disabled="saving || enrollments.length === 0"
                        @click="save"
                    />
                </PermissionGuard>
            </div>
        </div>

        <div class="table-scroll">
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th scope="col">Student</th>
                        <th v-for="session in sessions" :key="session.uuid" scope="col">
                            S{{ session.session_number }}
                            <span v-if="session.session_date" class="session-date">{{ session.session_date }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="enrollment in enrollments" :key="enrollment.uuid">
                        <td>
                            <strong>{{ enrollment.student.name }}</strong>
                            <span v-if="enrollment.student.email" class="email">{{ enrollment.student.email }}</span>
                        </td>
                        <td v-for="session in sessions" :key="session.uuid">
                            <SelectField
                                :model-value="grid[cellKey(enrollment.uuid, session.uuid)] ?? 'present'"
                                :name="`mark-${enrollment.uuid}-${session.uuid}`"
                                :options="statusOptions"
                                @update:model-value="
                                    (v: string | null) =>
                                        (grid[cellKey(enrollment.uuid, session.uuid)] =
                                            (v as AttendanceStatus) || 'present')
                                "
                            />
                        </td>
                    </tr>
                    <tr v-if="enrollments.length === 0">
                        <td :colspan="sessions.length + 1" class="empty">No students enrolled in this classroom.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<style scoped>
.sheet {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    font-family: var(--font-sans);
}

.toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-3);
    align-items: center;
    justify-content: space-between;
}

.toolbar__actions {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
    align-items: center;
}

.back-link,
.export-link {
    text-decoration: none;
    color: var(--text-primary);
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-family: var(--font-sans);
}

.back-link:focus-visible,
.export-link:focus-visible {
    outline: 2px solid var(--accent-primary);
    outline-offset: 2px;
    border-radius: var(--radius-sm);
}

.export-link {
    padding: 0.35rem 0.65rem;
    border: 1px solid var(--border-default);
    border-radius: var(--radius-sm);
    color: var(--text-secondary);
    background: var(--bg-surface);
}

.export-link:hover {
    border-color: var(--border-hover);
    color: var(--text-primary);
}

.table-scroll {
    overflow-x: auto;
}

.attendance-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 40rem;
    background: var(--bg-surface);
}

.attendance-table th,
.attendance-table td {
    border-bottom: 1px solid var(--border-subtle);
    padding: 0.55rem 0.45rem;
    text-align: left;
    vertical-align: middle;
    color: var(--text-primary);
}

.attendance-table th {
    color: var(--text-secondary);
    font-size: var(--text-xs);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.session-date {
    display: block;
    font-size: 0.75rem;
    color: var(--text-muted);
    font-weight: 400;
    text-transform: none;
    letter-spacing: normal;
}

.email {
    display: block;
    font-size: 0.8125rem;
    color: var(--text-muted);
}

.empty {
    text-align: center;
    color: var(--text-muted);
    padding: 1.5rem !important;
}
</style>
