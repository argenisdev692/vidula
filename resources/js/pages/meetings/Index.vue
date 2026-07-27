<script setup lang="ts">
/**
 * Meetings — internal scheduling. The Calendar view (default) is the single
 * calendar surface: it renders Meeting's own events plus a read-only overlay
 * of Appointment events (see MeetingCalendar.vue / calendar-feed endpoint).
 * The List view reuses the project's standard CrudIndexShell/useResourceList/
 * useConfirmAction stack (mirrors appointments/Index.vue) for bulk delete /
 * restore, which a calendar surface can't offer. Both are gated by the same
 * VIEW_ANY_MEETINGS permission; each branch owns its own Head/AppHeader so
 * neither depends on changes to the shared CrudIndexShell (used by 12 other
 * modules) — a small, deliberate duplication over a one-off shell prop
 * (plan.md §6 / tasks.md T067 KISS simplification).
 */
import { computed, reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import { useAuthorization } from '@/modules/auth/composables/useAuthorization';
import Button from '@/volt/Button.vue';
import type { FilterCriteria, FilterField } from '@/common/data-table/AdvancedFilter.vue';
import CrudIndexShell from '@/common/data-table/CrudIndexShell.vue';
import ConfirmDialog from '@/common/data-table/ConfirmDialog.vue';
import { useResourceList } from '@/common/data-table/useResourceList';
import { useConfirmAction } from '@/common/data-table/useConfirmAction';
import { toLocalIsoDate } from '@/lib/date';
import MeetingsTable from './components/MeetingsTable.vue';
import MeetingCalendar from './components/MeetingCalendar.vue';
import MeetingFormDialog from './components/MeetingFormDialog.vue';
import { useMeetingEditMutation } from '@/modules/meeting/composables/useMeetingEditMutation';
import { buildMeetingExportUrl, buildMeetingQueryParams } from '@/modules/meeting/helpers/buildMeetingQueryParams';
import type {
    Meeting,
    MeetingEditData,
    MeetingFilters,
    MeetingPrefill,
    MeetingQuery,
    MeetingRowStatus,
    MeetingStatus,
    PaginatedResponse,
} from '@/modules/meeting/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    meetings: PaginatedResponse<Meeting>;
    filters: MeetingFilters;
}>();

const toast = useToast();
const { hasPermission } = useAuthorization();
const { mutateAsync: fetchMeetingEdit } = useMeetingEditMutation();

const canCreate = computed<boolean>(() => hasPermission('CREATE_MEETINGS'));
const canExport = computed<boolean>(() => hasPermission('EXPORT_MEETINGS'));
const canBulkDelete = computed<boolean>(() => hasPermission('BULK_DELETE_MEETINGS'));
const canBulkRestore = computed<boolean>(() => hasPermission('BULK_RESTORE_MEETINGS'));

const view = ref<'calendar' | 'list'>('calendar');
const calendarRef = ref<InstanceType<typeof MeetingCalendar> | null>(null);

const dialogVisible = ref<boolean>(false);
const dialogMode = ref<'create' | 'edit'>('create');
const dialogMeeting = ref<MeetingEditData | null>(null);
const dialogPrefill = ref<MeetingPrefill | null>(null);

const query = reactive<MeetingQuery>({
    search: props.filters.search,
    status: props.filters.status,
    meeting_status: props.filters.meeting_status,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    starts_from: props.filters.starts_from,
    starts_to: props.filters.starts_to,
    page: props.meetings.current_page,
    per_page: props.meetings.per_page,
});

function applyCriteria(target: MeetingQuery, criteria: FilterCriteria): void {
    target.search = criteria.search || null;
    target.status = (criteria.status as MeetingRowStatus | undefined) || null;
    target.meeting_status = (criteria.meetingStatus as MeetingStatus | undefined) || null;

    const createdRange = criteria.dateRange as Date[] | undefined;
    target.date_from = createdRange?.[0] ? toLocalIsoDate(createdRange[0]) : null;
    target.date_to = createdRange?.[1] ? toLocalIsoDate(createdRange[1]) : null;

    const startsRange = criteria.startsRange as Date[] | undefined;
    target.starts_from = startsRange?.[0] ? toLocalIsoDate(startsRange[0]) : null;
    target.starts_to = startsRange?.[1] ? toLocalIsoDate(startsRange[1]) : null;
}

const { loading, selection, firstRecord, recordLabel, isSuspendedView, resetSelection, reload, onFilters, onPage, openExport } =
    useResourceList<Meeting, MeetingQuery>({
        baseUrl: '/meetings',
        propKey: 'meetings',
        query,
        pagination: computed(() => props.meetings),
        buildParams: buildMeetingQueryParams,
        applyCriteria,
        exportUrl: buildMeetingExportUrl,
    });

const canBulkAct = computed<boolean>(() => (isSuspendedView.value ? canBulkRestore.value : canBulkDelete.value));

function openCreate(prefill: MeetingPrefill | null = null): void {
    dialogMode.value = 'create';
    dialogMeeting.value = null;
    dialogPrefill.value = prefill;
    dialogVisible.value = true;
}

function onCalendarSchedule(prefill: { starts_at: string }): void {
    openCreate({ starts_at: prefill.starts_at });
}

async function openEditByUuid(uuid: string): Promise<void> {
    try {
        const response = await fetchMeetingEdit(uuid);
        dialogMode.value = 'edit';
        dialogMeeting.value = response.data;
        dialogPrefill.value = null;
        dialogVisible.value = true;
    } catch {
        toast.add({ severity: 'error', summary: 'Could not load meeting', life: 3000 });
    }
}

function openEdit(meeting: Meeting): void {
    void openEditByUuid(meeting.uuid);
}

function onDialogSaved(): void {
    toast.add({
        severity: 'success',
        summary: dialogMode.value === 'edit' ? 'Meeting updated' : 'Meeting created',
        life: 3000,
    });
    calendarRef.value?.refresh();
    if (view.value === 'list') {
        reload();
    }
}

/* ── Cancel (distinct from delete — flips status, doesn't soft-delete) ──── */
function cancelMeeting(meeting: Meeting): void {
    router.patch(`/meetings/${meeting.uuid}/cancel`, {}, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Meeting cancelled', life: 3000 });
            reload();
        },
    });
}

/* ── Single-row delete / restore ─────────────────────────────────────── */
type RowAction = { kind: 'delete' | 'restore'; meeting: Meeting };

const {
    visible: rowVisible,
    loading: rowLoading,
    confirm: rowConfirm,
    ask: askRow,
    run: runRow,
} = useConfirmAction<RowAction>((action) => {
    if (action.kind === 'restore') {
        return {
            title: 'Restore meeting',
            message: `Restore “${action.meeting.title}”? It becomes active again.`,
            confirmLabel: 'Restore',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Delete meeting',
        message: `Delete “${action.meeting.title}”? It will be soft-deleted and hidden from the active list.`,
        confirmLabel: 'Delete',
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
                    summary: action.kind === 'restore' ? 'Meeting restored' : 'Meeting deleted',
                    life: 4000,
                });
            },
            onFinish: finish,
        };
        if (action.kind === 'delete') {
            router.delete(`/meetings/${action.meeting.uuid}`, options);
        } else {
            router.post(`/meetings/${action.meeting.uuid}/restore`, {}, options);
        }
    });
}

/* ── Bulk delete / restore ───────────────────────────────────────────── */
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
            message: `Restore ${action.count} ${action.count === 1 ? 'meeting' : 'meetings'}? They become active again.`,
            confirmLabel: 'Restore all',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Delete selected',
        message: `Delete ${action.count} ${action.count === 1 ? 'meeting' : 'meetings'}? They will be soft-deleted.`,
        confirmLabel: 'Delete all',
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
        const uuids = selection.value.map((meeting) => meeting.uuid);
        if (uuids.length === 0) {
            finish();
            return;
        }
        const url = isSuspendedView.value ? '/meetings/bulk-restore' : '/meetings/bulk-delete';
        router.post(url, { uuids }, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                resetSelection();
                toast.add({
                    severity: 'success',
                    summary: isSuspendedView.value ? 'Selected meetings restored' : 'Selected meetings deleted',
                    life: 4000,
                });
            },
            onFinish: finish,
        });
    });
}

const filterFields: FilterField[] = [
    { key: 'startsRange', label: 'Scheduled between', type: 'date-range', placeholder: 'Start — End' },
    { key: 'dateRange', label: 'Created between', type: 'date-range', placeholder: 'Start — End' },
    {
        key: 'status',
        label: 'Status',
        type: 'select',
        placeholder: 'Active',
        options: [
            { label: 'Active', value: 'active' },
            { label: 'Deleted', value: 'suspended' },
        ],
    },
    {
        key: 'meetingStatus',
        label: 'Meeting status',
        type: 'select',
        placeholder: 'All',
        options: [
            { label: 'Scheduled', value: 'Scheduled' },
            { label: 'Cancelled', value: 'Cancelled' },
        ],
    },
];
</script>

<template>
    <!-- ── Calendar view (default) ─────────────────────────────────────── -->
    <template v-if="view === 'calendar'">
        <Head title="Meetings" />

        <AppHeader title="Meetings" subtitle="Internal scheduling — calendar and mixed-attendee meetings" />

        <PermissionGuard permission="VIEW_ANY_MEETINGS">
            <template #fallback>
                <div class="empty">
                    <i class="pi pi-lock" aria-hidden="true" />
                    <p>You don't have permission to view meetings.</p>
                </div>
            </template>

            <div class="meetings-calendar-page">
                <div class="view-toggle">
                    <Button label="Calendar" icon="pi pi-calendar" size="small" :aria-pressed="true" @click="view = 'calendar'" />
                    <Button label="List" icon="pi pi-list" size="small" outlined :aria-pressed="false" @click="view = 'list'" />
                    <Button
                        v-if="canCreate"
                        class="view-toggle__create"
                        label="New meeting"
                        icon="pi pi-plus"
                        size="small"
                        @click="openCreate()"
                    />
                </div>

                <MeetingCalendar ref="calendarRef" @schedule="onCalendarSchedule" @edit="openEditByUuid" />

                <MeetingFormDialog
                    v-model:visible="dialogVisible"
                    :mode="dialogMode"
                    :meeting="dialogMeeting"
                    :prefill="dialogPrefill"
                    @saved="onDialogSaved"
                />
            </div>
        </PermissionGuard>
    </template>

    <!-- ── List view (bulk management) ─────────────────────────────────── -->
    <template v-else>
        <CrudIndexShell
            title="Meetings"
            subtitle="Internal scheduling — calendar and mixed-attendee meetings"
            permission="VIEW_ANY_MEETINGS"
            fallback-text="You don't have permission to view meetings."
            search-placeholder="Search title or description…"
            :fields="filterFields"
            :can-export="canExport"
            :can-create="canCreate"
            create-label="New meeting"
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
                <div class="view-toggle">
                    <Button label="Calendar" icon="pi pi-calendar" size="small" outlined :aria-pressed="false" @click="view = 'calendar'" />
                    <Button label="List" icon="pi pi-list" size="small" :aria-pressed="true" @click="view = 'list'" />
                </div>

                <MeetingsTable
                    v-model:selection="selection"
                    :data="meetings.data"
                    :total="meetings.total"
                    :per-page="meetings.per_page"
                    :first="firstRecord"
                    :loading="loading"
                    @page="onPage"
                    @edit="openEdit"
                    @cancel="cancelMeeting"
                    @delete="(meeting: Meeting) => askRow({ kind: 'delete', meeting })"
                    @restore="(meeting: Meeting) => askRow({ kind: 'restore', meeting })"
                />
            </template>

            <template #dialogs>
                <MeetingFormDialog
                    v-model:visible="dialogVisible"
                    :mode="dialogMode"
                    :meeting="dialogMeeting"
                    :prefill="dialogPrefill"
                    @saved="onDialogSaved"
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
</template>

<style scoped>
.meetings-calendar-page {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}

.view-toggle {
    display: flex;
    gap: var(--space-2);
    margin-bottom: var(--space-3);
    align-items: center;
}

.view-toggle__create {
    margin-left: auto;
}

.empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-16) var(--space-6);
    color: var(--text-muted);
}

.empty .pi {
    font-size: var(--text-3xl);
}
</style>
