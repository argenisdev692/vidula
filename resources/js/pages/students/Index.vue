<script setup lang="ts">
/**
 * Students — LMS learner CRUD over soft-deletable global catalog profiles.
 *
 * Shared list mechanics live in useResourceList / useConfirmAction /
 * CrudIndexShell. Create & edit happen in a dialog (store/update return back()).
 * Export is wired (STUDENTS includes EXPORT in RolePermissionSeeder).
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
import StudentsTable from './components/StudentsTable.vue';
import StudentFormDialog from './components/StudentFormDialog.vue';
import type {
    Student,
    StudentFilters,
    StudentLifecycleStatus,
    StudentQuery,
    StudentSoftStatus,
    PaginatedResponse,
} from '@/modules/students/types';
import { buildStudentExportUrl, buildStudentQueryParams } from '@/modules/students/helpers/buildStudentQueryParams';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    students: PaginatedResponse<Student>;
    filters: StudentFilters;
}>();

const toast = useToast();
const { hasPermission } = useAuthorization();

const canCreate = computed<boolean>(() => hasPermission('CREATE_STUDENTS'));
const canExport = computed<boolean>(() => hasPermission('EXPORT_STUDENTS'));
const canBulkDelete = computed<boolean>(() => hasPermission('BULK_DELETE_STUDENTS'));
const canBulkRestore = computed<boolean>(() => hasPermission('BULK_RESTORE_STUDENTS'));

const query = reactive<StudentQuery>({
    search: props.filters.search,
    status: props.filters.status,
    student_status: props.filters.student_status,
    active: props.filters.active ?? null,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    page: props.students.current_page,
    per_page: props.students.per_page,
});

function applyCriteria(target: StudentQuery, criteria: FilterCriteria): void {
    target.search = criteria.search || null;
    target.status = (criteria.status as StudentSoftStatus | undefined) || null;
    target.student_status = (criteria.student_status as StudentLifecycleStatus | undefined) || null;

    const activeRaw = criteria.active as string | undefined;
    target.active = activeRaw === '1' ? true : activeRaw === '0' ? false : null;

    const range = criteria.dateRange as Date[] | undefined;
    target.date_from = range?.[0] ? toLocalIsoDate(range[0]) : null;
    target.date_to = range?.[1] ? toLocalIsoDate(range[1]) : null;
}

const { loading, selection, firstRecord, recordLabel, isSuspendedView, resetSelection, onFilters, onPage, openExport } =
    useResourceList<Student, StudentQuery>({
        baseUrl: '/students',
        propKey: 'students',
        query,
        pagination: computed(() => props.students),
        buildParams: buildStudentQueryParams,
        applyCriteria,
        exportUrl: buildStudentExportUrl,
    });

const canBulkAct = computed<boolean>(() => (isSuspendedView.value ? canBulkRestore.value : canBulkDelete.value));

const { visible: formVisible, mode: formMode, entity: formStudent, openCreate, openEdit } = useFormDialog<Student>();

function onSaved(): void {
    toast.add({
        severity: 'success',
        summary: formMode.value === 'edit' ? 'Student updated' : 'Student created',
        life: 4000,
    });
}

type RowAction = { kind: 'delete' | 'restore'; student: Student };

const {
    visible: rowVisible,
    loading: rowLoading,
    confirm: rowConfirm,
    ask: askRow,
    run: runRow,
} = useConfirmAction<RowAction>((action) => {
    const name = action.student.name ?? 'this student';
    if (action.kind === 'restore') {
        return {
            title: 'Restore student',
            message: `Restore “${name}”? It will become active again.`,
            confirmLabel: 'Restore',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend student',
        message: `Suspend “${name}”? It will be soft-deleted and hidden from the active list. You can restore it later.`,
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
                    summary: action.kind === 'restore' ? 'Student restored' : 'Student suspended',
                    life: 4000,
                });
            },
            onFinish: finish,
        };
        if (action.kind === 'delete') {
            router.delete(`/students/${action.student.uuid}`, options);
        } else {
            router.post(`/students/${action.student.uuid}/restore`, {}, options);
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
            message: `Restore ${action.count} ${action.count === 1 ? 'student' : 'students'}? They will become active again.`,
            confirmLabel: 'Restore all',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend selected',
        message: `Suspend ${action.count} ${action.count === 1 ? 'student' : 'students'}? They will be soft-deleted and hidden from the active list.`,
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
        const uuids = selection.value.map((student) => student.uuid);
        if (uuids.length === 0) {
            finish();
            return;
        }
        const url = isSuspendedView.value ? '/students/bulk-restore' : '/students/bulk-delete';
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
                        summary: isSuspendedView.value ? 'Selected students restored' : 'Selected students suspended',
                        life: 4000,
                    });
                },
                onFinish: finish,
            },
        );
    });
}

const filterFields: FilterField[] = [
    { key: 'dateRange', label: 'Created between', type: 'date-range', placeholder: 'Start — End' },
    {
        key: 'status',
        label: 'Status',
        type: 'select',
        placeholder: 'Active',
        options: [
            { label: 'Active', value: 'active' },
            { label: 'Suspended', value: 'suspended' },
        ],
    },
    {
        key: 'student_status',
        label: 'Lifecycle',
        type: 'select',
        placeholder: 'All lifecycles',
        options: [
            { label: 'Draft', value: 'DRAFT' },
            { label: 'Active', value: 'ACTIVE' },
            { label: 'Archived', value: 'ARCHIVED' },
        ],
    },
    {
        key: 'active',
        label: 'Catalog flag',
        type: 'select',
        placeholder: 'Any',
        options: [
            { label: 'Active flag', value: '1' },
            { label: 'Inactive flag', value: '0' },
        ],
    },
];
</script>

<template>
    <CrudIndexShell
        title="Students"
        subtitle="Manage LMS learner profiles shared across the academy"
        permission="VIEW_ANY_STUDENTS"
        fallback-text="You don't have permission to view students."
        search-placeholder="Search name, email, phone, DNI…"
        :fields="filterFields"
        :can-export="canExport"
        :can-create="canCreate"
        create-label="New student"
        :record-label="recordLabel"
        :selection-count="selection.length"
        :can-bulk-act="canBulkAct"
        :is-suspended-view="isSuspendedView"
        @filters-change="onFilters"
        @create="openCreate"
        @bulk="askBulk"
        @export-pdf="openExport('pdf')"
        @export-excel="openExport('xlsx')"
        @export-csv="openExport('csv')"
    >
        <template #table>
            <StudentsTable
                v-model:selection="selection"
                :data="students.data"
                :total="students.total"
                :per-page="students.per_page"
                :first="firstRecord"
                :loading="loading"
                @page="onPage"
                @edit="openEdit"
                @delete="(student: Student) => askRow({ kind: 'delete', student })"
                @restore="(student: Student) => askRow({ kind: 'restore', student })"
            />
        </template>

        <template #dialogs>
            <StudentFormDialog
                v-model:visible="formVisible"
                :mode="formMode"
                :student="formStudent"
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
