<script setup lang="ts">
/**
 * Appointments — sales-lead pipeline management over a soft-deletable
 * capture. A lead progresses through `status_lead` while its booked meeting
 * progresses independently through `meeting_status` (see the migration
 * comment on `AppointmentEloquentModel`).
 *
 * The shared list mechanics live in {@see useResourceList}, the confirm
 * dialogs in {@see useConfirmAction}, and the page chrome in
 * {@see CrudIndexShell}. Create / edit are dedicated pages (no modal), so they
 * navigate via `router.visit`. Gated by VIEW_ANY_APPOINTMENTS; every mutating
 * control by its own permission.
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
import { toLocalIsoDate } from '@/lib/date';
import AppointmentsTable from './components/AppointmentsTable.vue';
import {
    CLIENT_TYPE_OPTIONS,
    MEETING_STATUS_OPTIONS,
    PROJECT_TYPE_OPTIONS,
    STATUS_LEAD_OPTIONS,
} from '@/modules/appointments/helpers/options';
import { buildAppointmentExportUrl, buildAppointmentQueryParams } from '@/modules/appointments/helpers/buildAppointmentQueryParams';
import type {
    Appointment,
    AppointmentFilters,
    AppointmentQuery,
    AppointmentRead,
    AppointmentSpam,
    AppointmentStatus,
    ClientType,
    MeetingStatus,
    PaginatedResponse,
    ProjectType,
    StatusLead,
} from '@/modules/appointments/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    appointments: PaginatedResponse<Appointment>;
    filters: AppointmentFilters;
}>();

const toast = useToast();
const { hasPermission } = useAuthorization();

const canCreate = computed<boolean>(() => hasPermission('CREATE_APPOINTMENTS'));
const canExport = computed<boolean>(() => hasPermission('EXPORT_APPOINTMENTS'));
const canBulkDelete = computed<boolean>(() => hasPermission('BULK_DELETE_APPOINTMENTS'));
const canBulkRestore = computed<boolean>(() => hasPermission('BULK_RESTORE_APPOINTMENTS'));

/** The reactive request state — seeded once from the server-echoed props. */
const query = reactive<AppointmentQuery>({
    search: props.filters.search,
    status: props.filters.status,
    status_lead: props.filters.status_lead,
    meeting_status: props.filters.meeting_status,
    client_type: props.filters.client_type,
    project_type: props.filters.project_type,
    read: props.filters.read,
    spam: props.filters.spam,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    scheduled_from: props.filters.scheduled_from,
    scheduled_to: props.filters.scheduled_to,
    page: props.appointments.current_page,
    per_page: props.appointments.per_page,
});

function applyCriteria(target: AppointmentQuery, criteria: FilterCriteria): void {
    target.search = criteria.search || null;
    target.status = (criteria.status as AppointmentStatus | undefined) || null;
    target.status_lead = (criteria.statusLead as StatusLead | undefined) || null;
    target.meeting_status = (criteria.meetingStatus as Exclude<MeetingStatus, null> | undefined) || null;
    target.client_type = (criteria.clientType as ClientType | undefined) || null;
    target.project_type = (criteria.projectType as ProjectType | undefined) || null;
    target.read = (criteria.read as AppointmentRead | undefined) || null;
    target.spam = (criteria.spam as AppointmentSpam | undefined) || null;

    const createdRange = criteria.dateRange as Date[] | undefined;
    target.date_from = createdRange?.[0] ? toLocalIsoDate(createdRange[0]) : null;
    target.date_to = createdRange?.[1] ? toLocalIsoDate(createdRange[1]) : null;

    const scheduledRange = criteria.scheduledRange as Date[] | undefined;
    target.scheduled_from = scheduledRange?.[0] ? toLocalIsoDate(scheduledRange[0]) : null;
    target.scheduled_to = scheduledRange?.[1] ? toLocalIsoDate(scheduledRange[1]) : null;
}

const { loading, selection, firstRecord, recordLabel, isSuspendedView, resetSelection, reload, onFilters, onPage, openExport } =
    useResourceList<Appointment, AppointmentQuery>({
        baseUrl: '/appointments',
        propKey: 'appointments',
        query,
        pagination: computed(() => props.appointments),
        buildParams: buildAppointmentQueryParams,
        applyCriteria,
        exportUrl: buildAppointmentExportUrl,
    });

const canBulkAct = computed<boolean>(() => (isSuspendedView.value ? canBulkRestore.value : canBulkDelete.value));

/* ── Create / edit — dedicated pages (no modal) ───────────────────────── */
function openCreate(): void {
    router.visit('/appointments/create');
}

function openEdit(appointment: Appointment): void {
    router.visit(`/appointments/${appointment.uuid}/edit`);
}

/* ── Mark as read ─────────────────────────────────────────────────────── */
function markRead(appointment: Appointment): void {
    router.patch(`/appointments/${appointment.uuid}/read`, {}, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Marked as read', life: 3000 });
            reload();
        },
    });
}

/* ── Single-row suspend / restore ─────────────────────────────────────── */
type RowAction = { kind: 'delete' | 'restore'; appointment: Appointment };

function displayName(appointment: Appointment): string {
    return `${appointment.first_name} ${appointment.last_name}`.trim() || appointment.email;
}

const {
    visible: rowVisible,
    loading: rowLoading,
    confirm: rowConfirm,
    ask: askRow,
    run: runRow,
} = useConfirmAction<RowAction>((action) => {
    const name = displayName(action.appointment);
    if (action.kind === 'restore') {
        return {
            title: 'Restore lead',
            message: `Restore “${name}”? The lead becomes active again.`,
            confirmLabel: 'Restore',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend lead',
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
                    summary: action.kind === 'restore' ? 'Lead restored' : 'Lead suspended',
                    life: 4000,
                });
            },
            onFinish: finish,
        };
        if (action.kind === 'delete') {
            router.delete(`/appointments/${action.appointment.uuid}`, options);
        } else {
            router.post(`/appointments/${action.appointment.uuid}/restore`, {}, options);
        }
    });
}

/* ── Bulk suspend / restore ───────────────────────────────────────────── */
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
            message: `Restore ${action.count} ${action.count === 1 ? 'lead' : 'leads'}? They become active again.`,
            confirmLabel: 'Restore all',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend selected',
        message: `Suspend ${action.count} ${action.count === 1 ? 'lead' : 'leads'}? They will be soft-deleted and hidden from the active list.`,
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
        const uuids = selection.value.map((appointment) => appointment.uuid);
        if (uuids.length === 0) {
            finish();
            return;
        }
        const url = isSuspendedView.value ? '/appointments/bulk-restore' : '/appointments/bulk-delete';
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
                        summary: isSuspendedView.value ? 'Selected leads restored' : 'Selected leads suspended',
                        life: 4000,
                    });
                },
                onFinish: finish,
            },
        );
    });
}

const filterFields: FilterField[] = [
    { key: 'dateRange', label: 'Captured between', type: 'date-range', placeholder: 'Start — End' },
    { key: 'scheduledRange', label: 'Meeting scheduled between', type: 'date-range', placeholder: 'Start — End' },
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
    { key: 'statusLead', label: 'Lead status', type: 'select', placeholder: 'All', options: STATUS_LEAD_OPTIONS },
    { key: 'meetingStatus', label: 'Meeting status', type: 'select', placeholder: 'All', options: MEETING_STATUS_OPTIONS },
    { key: 'clientType', label: 'Client type', type: 'select', placeholder: 'All', options: CLIENT_TYPE_OPTIONS },
    { key: 'projectType', label: 'Project type', type: 'select', placeholder: 'All', options: PROJECT_TYPE_OPTIONS },
    {
        key: 'read',
        label: 'Read state',
        type: 'select',
        placeholder: 'All',
        options: [
            { label: 'Read', value: 'read' },
            { label: 'Unread', value: 'unread' },
        ],
    },
    {
        key: 'spam',
        label: 'Spam',
        type: 'select',
        placeholder: 'All',
        options: [
            { label: 'Legitimate', value: 'ham' },
            { label: 'Spam', value: 'spam' },
        ],
    },
];
</script>

<template>
    <CrudIndexShell
        title="Appointments"
        subtitle="Manage sales leads and their booked meetings"
        permission="VIEW_ANY_APPOINTMENTS"
        fallback-text="You don't have permission to view appointments."
        search-placeholder="Search name, email or company…"
        :fields="filterFields"
        :can-export="canExport"
        :can-create="canCreate"
        create-label="New lead"
        :record-label="recordLabel"
        :selection-count="selection.length"
        :can-bulk-act="canBulkAct"
        :is-suspended-view="isSuspendedView"
        @filters-change="onFilters"
        @create="openCreate"
        @export-pdf="openExport('pdf')"
        @export-excel="openExport('xlsx')"
        @export-csv="openExport('csv')"
        @bulk="askBulk"
    >
        <template #table>
            <AppointmentsTable
                v-model:selection="selection"
                :data="appointments.data"
                :total="appointments.total"
                :per-page="appointments.per_page"
                :first="firstRecord"
                :loading="loading"
                @page="onPage"
                @edit="openEdit"
                @delete="(appointment: Appointment) => askRow({ kind: 'delete', appointment })"
                @restore="(appointment: Appointment) => askRow({ kind: 'restore', appointment })"
                @mark-read="markRead"
            />
        </template>

        <template #dialogs>
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
