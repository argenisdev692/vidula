<script setup lang="ts">
/**
 * Enrollments — assign students to classrooms; soft-delete = Suspended.
 *
 * Shared list mechanics: useResourceList / useConfirmAction / CrudIndexShell.
 * Create & edit happen in a dialog. Attendance export lives on the sheet page.
 */
import { computed, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import { useAuthorization } from '@/modules/auth/composables/useAuthorization';
import type { FilterCriteria, FilterField } from '@/common/data-table/AdvancedFilter.vue';
import CrudIndexShell from '@/common/data-table/CrudIndexShell.vue';
import ConfirmDialog from '@/common/data-table/ConfirmDialog.vue';
import { useResourceList } from '@/common/data-table/useResourceList';
import { useConfirmAction } from '@/common/data-table/useConfirmAction';
import { useFormDialog } from '@/common/data-table/useFormDialog';
import { toLocalIsoDate } from '@/lib/date';
import EnrollmentsTable from './components/EnrollmentsTable.vue';
import EnrollmentFormDialog from './components/EnrollmentFormDialog.vue';
import type {
    Enrollment,
    EnrollmentClassroomOption,
    EnrollmentFilters,
    EnrollmentLifecycleStatus,
    EnrollmentQuery,
    EnrollmentSoftStatus,
    EnrollmentStudentOption,
    PaginatedResponse,
} from '@/modules/enrollments/types';
import { buildEnrollmentQueryParams } from '@/modules/enrollments/helpers/buildEnrollmentQueryParams';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    enrollments: PaginatedResponse<Enrollment>;
    filters: EnrollmentFilters;
    students: EnrollmentStudentOption[];
    classrooms: EnrollmentClassroomOption[];
}>();

const toast = useToast();
const { hasPermission } = useAuthorization();

const canCreate = computed<boolean>(() => hasPermission('CREATE_ENROLLMENTS'));
const canBulkDelete = computed<boolean>(() => hasPermission('BULK_DELETE_ENROLLMENTS'));
const canBulkRestore = computed<boolean>(() => hasPermission('BULK_RESTORE_ENROLLMENTS'));

const query = reactive<EnrollmentQuery>({
    search: props.filters.search,
    status: props.filters.status,
    enrollment_status: props.filters.enrollment_status,
    classroom_uuid: props.filters.classroom_uuid,
    student_uuid: props.filters.student_uuid,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    page: props.enrollments.current_page,
    per_page: props.enrollments.per_page,
});

function applyCriteria(target: EnrollmentQuery, criteria: FilterCriteria): void {
    target.search = criteria.search || null;
    target.status = (criteria.status as EnrollmentSoftStatus | undefined) || null;
    target.enrollment_status = (criteria.enrollment_status as EnrollmentLifecycleStatus | undefined) || null;
    target.classroom_uuid = (criteria.classroom_uuid as string | undefined) || null;
    target.student_uuid = (criteria.student_uuid as string | undefined) || null;
    const range = criteria.dateRange as Date[] | undefined;
    target.date_from = range?.[0] ? toLocalIsoDate(range[0]) : null;
    target.date_to = range?.[1] ? toLocalIsoDate(range[1]) : null;
}

const { loading, selection, firstRecord, recordLabel, isSuspendedView, resetSelection, onFilters, onPage } =
    useResourceList<Enrollment, EnrollmentQuery>({
        baseUrl: '/enrollments',
        propKey: 'enrollments',
        query,
        pagination: computed(() => props.enrollments),
        buildParams: buildEnrollmentQueryParams,
        applyCriteria,
    });

const canBulkAct = computed<boolean>(() => (isSuspendedView.value ? canBulkRestore.value : canBulkDelete.value));

const { visible: formVisible, mode: formMode, entity: formEnrollment, openCreate, openEdit } =
    useFormDialog<Enrollment>();

function onSaved(): void {
    toast.add({
        severity: 'success',
        summary: formMode.value === 'edit' ? 'Enrollment updated' : 'Enrollment created',
        life: 4000,
    });
}

type RowAction = { kind: 'delete' | 'restore'; enrollment: Enrollment };

const {
    visible: rowVisible,
    loading: rowLoading,
    confirm: rowConfirm,
    ask: askRow,
    run: runRow,
} = useConfirmAction<RowAction>((action) => {
    const name = action.enrollment.student?.name ?? 'this enrollment';
    if (action.kind === 'restore') {
        return {
            title: 'Restore enrollment',
            message: `Restore enrollment for “${name}”? It will become active again.`,
            confirmLabel: 'Restore',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend enrollment',
        message: `Suspend enrollment for “${name}”? It will be soft-deleted and hidden from the active list.`,
        confirmLabel: 'Suspend',
        confirmIcon: 'pi pi-trash',
        tone: 'danger',
    };
});

function confirmRowAction(): void {
    runRow((action, finish) => {
        const options = {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                resetSelection();
                toast.add({
                    severity: 'success',
                    summary: action.kind === 'restore' ? 'Enrollment restored' : 'Enrollment suspended',
                    life: 4000,
                });
            },
            onFinish: finish,
        };
        if (action.kind === 'delete') {
            router.delete(`/enrollments/${action.enrollment.uuid}`, options);
        } else {
            router.post(`/enrollments/${action.enrollment.uuid}/restore`, {}, options);
        }
    });
}

const {
    visible: bulkVisible,
    loading: bulkLoading,
    confirm: bulkConfirm,
    ask: askBulkConfirm,
    run: runBulk,
} = useConfirmAction<{ count: number }>((action) => {
    if (isSuspendedView.value) {
        return {
            title: 'Restore selected',
            message: `Restore ${action.count} ${action.count === 1 ? 'enrollment' : 'enrollments'}? They will become active again.`,
            confirmLabel: 'Restore all',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend selected',
        message: `Suspend ${action.count} ${action.count === 1 ? 'enrollment' : 'enrollments'}? They will be soft-deleted and hidden from the active list.`,
        confirmLabel: 'Suspend all',
        confirmIcon: 'pi pi-trash',
        tone: 'danger',
    };
});

function askBulk(): void {
    if (selection.value.length > 0) {
        askBulkConfirm({ count: selection.value.length });
    }
}

function confirmBulk(): void {
    runBulk((_action, finish) => {
        const uuids = selection.value.map((row) => row.uuid);
        if (uuids.length === 0) {
            finish();
            return;
        }
        const url = isSuspendedView.value ? '/enrollments/bulk-restore' : '/enrollments/bulk-delete';
        router.post(
            url,
            { uuids },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    resetSelection();
                    toast.add({
                        severity: 'success',
                        summary: isSuspendedView.value
                            ? 'Selected enrollments restored'
                            : 'Selected enrollments suspended',
                        life: 4000,
                    });
                },
                onFinish: finish,
            },
        );
    });
}

function openAttendance(enrollment: Enrollment): void {
    const classroomUuid = enrollment.classroom?.uuid;
    if (!classroomUuid) {
        return;
    }
    router.visit(`/enrollments/attendance/${classroomUuid}`);
}

const filterFields = computed<FilterField[]>(() => [
    { key: 'dateRange', label: 'Enrolled between', type: 'date-range', placeholder: 'Start — End' },
    {
        key: 'status',
        label: 'List',
        type: 'select',
        placeholder: 'Active',
        options: [
            { label: 'Active', value: 'active' },
            { label: 'Suspended', value: 'suspended' },
        ],
    },
    {
        key: 'enrollment_status',
        label: 'Enrollment status',
        type: 'select',
        placeholder: 'All statuses',
        options: [
            { label: 'Active', value: 'active' },
            { label: 'Suspended', value: 'suspended' },
            { label: 'Completed', value: 'completed' },
            { label: 'Dropped', value: 'dropped' },
        ],
    },
    {
        key: 'classroom_uuid',
        label: 'Classroom',
        type: 'select',
        placeholder: 'All classrooms',
        options: props.classrooms.map((row) => ({ label: row.title, value: row.uuid })),
    },
]);
</script>

<template>
    <CrudIndexShell
        title="Enrollments"
        subtitle="Assign students to classrooms and track attendance"
        permission="VIEW_ANY_ENROLLMENTS"
        fallback-text="You don't have permission to view enrollments."
        search-placeholder="Search student, email, DNI, or classroom…"
        :fields="filterFields"
        :can-export="false"
        :can-create="canCreate"
        create-label="Enroll student"
        :record-label="recordLabel"
        :selection-count="selection.length"
        :can-bulk-act="canBulkAct"
        :is-suspended-view="isSuspendedView"
        @filters-change="onFilters"
        @create="openCreate"
        @bulk="askBulk"
    >
        <template #table>
            <EnrollmentsTable
                v-model:selection="selection"
                :data="enrollments.data"
                :total="enrollments.total"
                :per-page="enrollments.per_page"
                :first="firstRecord"
                :loading="loading"
                @page="onPage"
                @edit="openEdit"
                @attendance="openAttendance"
                @delete="(enrollment: Enrollment) => askRow({ kind: 'delete', enrollment })"
                @restore="(enrollment: Enrollment) => askRow({ kind: 'restore', enrollment })"
            />
        </template>

        <template #dialogs>
            <EnrollmentFormDialog
                v-model:visible="formVisible"
                :mode="formMode"
                :enrollment="formEnrollment"
                :students="students"
                :classrooms="classrooms"
                @saved="onSaved"
            />

            <ConfirmDialog
                v-model:visible="rowVisible"
                :title="rowConfirm.title"
                :message="rowConfirm.message"
                :confirm-label="rowConfirm.confirmLabel"
                :confirm-icon="rowConfirm.confirmIcon"
                :tone="rowConfirm.tone"
                :loading="rowLoading"
                @confirm="confirmRowAction"
            />

            <ConfirmDialog
                v-model:visible="bulkVisible"
                :title="bulkConfirm.title"
                :message="bulkConfirm.message"
                :confirm-label="bulkConfirm.confirmLabel"
                :confirm-icon="bulkConfirm.confirmIcon"
                :tone="bulkConfirm.tone"
                :loading="bulkLoading"
                @confirm="confirmBulk"
            />
        </template>
    </CrudIndexShell>
</template>
