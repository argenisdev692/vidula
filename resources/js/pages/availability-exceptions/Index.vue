<script setup lang="ts">
/**
 * Availability Exceptions — full CRUD over per-date overrides (closures and
 * forced-open windows). Same Inertia-partial-reload + shared-modal pattern as
 * the Availability Rules screen. Holiday rows are system-materialised; they are
 * still viewable / editable per permission and flagged by a Source badge in the
 * table. Gated by VIEW_ANY_AVAILABILITY_EXCEPTIONS; each mutating control by its
 * own permission.
 */
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import type { DataTablePageEvent } from 'primevue/datatable';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import { useAuthorization } from '@/modules/auth/composables/useAuthorization';
import AdvancedFilter, { type FilterCriteria, type FilterField } from '@/common/data-table/AdvancedFilter.vue';
import { toLocalIsoDate } from '@/lib/date';
import ConfirmDialog from '@/common/data-table/ConfirmDialog.vue';
import Button from '@/volt/Button.vue';
import AvailabilityExceptionsTable from './components/AvailabilityExceptionsTable.vue';
import AvailabilityExceptionFormDialog from './components/AvailabilityExceptionFormDialog.vue';
import { formatDate } from '@/modules/availability/helpers/availabilityFormat';
import {
    buildAvailabilityExceptionQueryParams,
    buildAvailabilityExportUrl,
    type AvailabilityExportFormat,
} from '@/modules/availability/helpers/buildAvailabilityQueryParams';
import type { SharedProps } from '@/types/inertia';
import type {
    AvailabilityException,
    AvailabilityExceptionFilters,
    AvailabilityExceptionQuery,
    AvailabilityStatus,
    ExceptionAvailability,
    PaginatedResponse,
} from '@/modules/availability/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    availabilityExceptions: PaginatedResponse<AvailabilityException>;
    filters: AvailabilityExceptionFilters;
}>();

usePage<SharedProps>();
const toast = useToast();
const { hasPermission } = useAuthorization();

const canCreate = computed<boolean>(() => hasPermission('CREATE_AVAILABILITY_EXCEPTIONS'));
const canExport = computed<boolean>(() => hasPermission('EXPORT_AVAILABILITY_EXCEPTIONS'));
const canBulkDelete = computed<boolean>(() => hasPermission('BULK_DELETE_AVAILABILITY_EXCEPTIONS'));
const canBulkRestore = computed<boolean>(() => hasPermission('BULK_RESTORE_AVAILABILITY_EXCEPTIONS'));

const loading = ref<boolean>(false);
const selection = ref<AvailabilityException[]>([]);

/** The reactive request state — seeded once from the server-echoed props. */
const query = reactive<AvailabilityExceptionQuery>({
    availability: props.filters.availability,
    status: props.filters.status,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    page: props.availabilityExceptions.current_page,
    per_page: props.availabilityExceptions.per_page,
});

const firstRecord = computed<number>(
    () => (props.availabilityExceptions.current_page - 1) * props.availabilityExceptions.per_page,
);
const recordLabel = computed<string>(
    () => `${props.availabilityExceptions.total} ${props.availabilityExceptions.total === 1 ? 'record' : 'records'} found`,
);

const isSuspendedView = computed<boolean>(() => query.status === 'suspended');
const canBulkAct = computed<boolean>(() => (isSuspendedView.value ? canBulkRestore.value : canBulkDelete.value));

const filterFields: FilterField[] = [
    { key: 'dateRange', label: 'Date between', type: 'date-range', placeholder: 'Start — End' },
    {
        key: 'availability',
        label: 'State',
        type: 'select',
        placeholder: 'Any',
        options: [
            { label: 'Open', value: 'open' },
            { label: 'Closed', value: 'closed' },
        ],
    },
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
];

function reload(): void {
    loading.value = true;
    router.get('/availability-exceptions', buildAvailabilityExceptionQueryParams(query), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['availabilityExceptions', 'filters'],
        onFinish: () => {
            loading.value = false;
        },
    });
}

function onFilters(criteria: FilterCriteria): void {
    query.availability = (criteria.availability as ExceptionAvailability | undefined) || null;
    query.status = (criteria.status as AvailabilityStatus | undefined) || null;

    const range = criteria.dateRange as Date[] | undefined;
    query.date_from = range?.[0] ? toLocalIsoDate(range[0]) : null;
    query.date_to = range?.[1] ? toLocalIsoDate(range[1]) : null;

    query.page = 1;
    selection.value = [];
    reload();
}

function onPage(event: DataTablePageEvent): void {
    query.page = event.page + 1;
    query.per_page = event.rows;
    reload();
}

function openExport(format: AvailabilityExportFormat): void {
    window.location.href = buildAvailabilityExportUrl(
        '/availability-exceptions/export',
        buildAvailabilityExceptionQueryParams(query),
        format,
    );
}

/* ── Create / edit ────────────────────────────────────────────────────── */
const formVisible = ref<boolean>(false);
const formMode = ref<'create' | 'edit'>('create');
const formException = ref<AvailabilityException | null>(null);

function openCreate(): void {
    formMode.value = 'create';
    formException.value = null;
    formVisible.value = true;
}

function openEdit(exception: AvailabilityException): void {
    formMode.value = 'edit';
    formException.value = exception;
    formVisible.value = true;
}

function onSaved(): void {
    toast.add({
        severity: 'success',
        summary: formMode.value === 'edit' ? 'Date exception updated' : 'Date exception created',
        life: 4000,
    });
}

/* ── Single-row delete / restore ──────────────────────────────────────── */
const rowAction = ref<{ kind: 'delete' | 'restore'; exception: AvailabilityException } | null>(null);
const rowActionVisible = ref<boolean>(false);
const rowActionLoading = ref<boolean>(false);

const rowConfirm = computed(() => {
    const name = rowAction.value ? formatDate(rowAction.value.exception.date) : 'this exception';
    if (rowAction.value?.kind === 'restore') {
        return {
            title: 'Restore date exception',
            message: `Restore the exception for ${name}? It will become active again.`,
            confirmLabel: 'Restore',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success' as const,
        };
    }
    return {
        title: 'Suspend date exception',
        message: `Suspend the exception for ${name}? It will be soft-deleted and stop overriding the weekly template. You can restore it later.`,
        confirmLabel: 'Suspend',
        confirmIcon: 'pi pi-trash',
        tone: 'danger' as const,
    };
});

function askDelete(exception: AvailabilityException): void {
    rowAction.value = { kind: 'delete', exception };
    rowActionVisible.value = true;
}

function askRestore(exception: AvailabilityException): void {
    rowAction.value = { kind: 'restore', exception };
    rowActionVisible.value = true;
}

function confirmRowAction(): void {
    const action = rowAction.value;
    if (!action) {
        return;
    }
    rowActionLoading.value = true;

    const options = {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            selection.value = [];
            toast.add({
                severity: 'success',
                summary: action.kind === 'restore' ? 'Date exception restored' : 'Date exception suspended',
                life: 4000,
            });
        },
        onFinish: () => {
            rowActionLoading.value = false;
            rowActionVisible.value = false;
        },
    };

    if (action.kind === 'delete') {
        router.delete(`/availability-exceptions/${action.exception.uuid}`, options);
    } else {
        router.post(`/availability-exceptions/${action.exception.uuid}/restore`, {}, options);
    }
}

/* ── Bulk suspend / restore ───────────────────────────────────────────── */
const bulkVisible = ref<boolean>(false);
const bulkLoading = ref<boolean>(false);

const bulkConfirm = computed(() => {
    const count = selection.value.length;
    if (isSuspendedView.value) {
        return {
            title: 'Restore selected',
            message: `Restore ${count} date ${count === 1 ? 'exception' : 'exceptions'}? They will become active again.`,
            confirmLabel: 'Restore all',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success' as const,
        };
    }
    return {
        title: 'Suspend selected',
        message: `Suspend ${count} date ${count === 1 ? 'exception' : 'exceptions'}? They will be soft-deleted and stop overriding the weekly template.`,
        confirmLabel: 'Suspend all',
        confirmIcon: 'pi pi-trash',
        tone: 'danger' as const,
    };
});

function askBulk(): void {
    if (selection.value.length > 0) {
        bulkVisible.value = true;
    }
}

function confirmBulk(): void {
    const uuids = selection.value.map((e) => e.uuid);
    if (uuids.length === 0) {
        return;
    }
    bulkLoading.value = true;

    const url = isSuspendedView.value ? '/availability-exceptions/bulk-restore' : '/availability-exceptions/bulk-delete';
    router.post(
        url,
        { uuids },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                selection.value = [];
                toast.add({
                    severity: 'success',
                    summary: isSuspendedView.value ? 'Selected exceptions restored' : 'Selected exceptions suspended',
                    life: 4000,
                });
            },
            onFinish: () => {
                bulkLoading.value = false;
                bulkVisible.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Availability Exceptions" />

    <AppHeader title="Availability Exceptions" subtitle="Per-date closures and forced-open overrides" />

    <PermissionGuard permission="VIEW_ANY_AVAILABILITY_EXCEPTIONS">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to view availability exceptions.</p>
            </div>
        </template>

        <div class="page">
            <AdvancedFilter
                search-placeholder="Search…"
                :fields="filterFields"
                :show-export-pdf="canExport"
                :show-export-excel="canExport"
                :show-export-csv="canExport"
                :show-create="canCreate"
                create-label="New exception"
                @filters-change="onFilters"
                @create="openCreate"
                @export-pdf="openExport('pdf')"
                @export-excel="openExport('xlsx')"
                @export-csv="openExport('csv')"
            />

            <div class="toolbar">
                <p class="counter">{{ recordLabel }}</p>

                <Transition name="fade">
                    <div v-if="selection.length > 0 && canBulkAct" class="bulk-bar">
                        <span class="bulk-bar__count">{{ selection.length }} selected</span>
                        <Button
                            size="small"
                            :label="isSuspendedView ? 'Restore selected' : 'Suspend selected'"
                            :icon="isSuspendedView ? 'pi pi-check-circle' : 'pi pi-trash'"
                            outlined
                            :class="['bulk-btn', isSuspendedView ? 'bulk-btn--restore' : 'bulk-btn--suspend']"
                            @click="askBulk"
                        />
                    </div>
                </Transition>
            </div>

            <AvailabilityExceptionsTable
                v-model:selection="selection"
                :data="availabilityExceptions.data"
                :total="availabilityExceptions.total"
                :per-page="availabilityExceptions.per_page"
                :first="firstRecord"
                :loading="loading"
                @page="onPage"
                @edit="openEdit"
                @delete="askDelete"
                @restore="askRestore"
            />
        </div>
    </PermissionGuard>

    <AvailabilityExceptionFormDialog
        v-model:visible="formVisible"
        :mode="formMode"
        :exception="formException"
        @saved="onSaved"
    />

    <ConfirmDialog
        v-model:visible="rowActionVisible"
        :title="rowConfirm.title"
        :message="rowConfirm.message"
        :confirm-label="rowConfirm.confirmLabel"
        :confirm-icon="rowConfirm.confirmIcon"
        :tone="rowConfirm.tone"
        :loading="rowActionLoading"
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

<style scoped>
.page {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}

.toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
    flex-wrap: wrap;
}

.counter {
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-muted);
}

.bulk-bar {
    display: flex;
    align-items: center;
    gap: var(--space-3);
}

.bulk-bar__count {
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--text-secondary);
}

/* Themed bulk action — subtle accent tint at rest, fills + lifts + glows on hover. */
.bulk-bar :deep(.bulk-btn) {
    border-radius: var(--radius-md);
    font-weight: var(--font-semibold);
    transition:
        transform var(--transition),
        box-shadow var(--transition),
        background-color var(--transition),
        border-color var(--transition),
        color var(--transition);
}

.bulk-bar :deep(.bulk-btn--suspend) {
    color: var(--accent-error) !important;
    border-color: color-mix(in srgb, var(--accent-error) 40%, transparent) !important;
    background: color-mix(in srgb, var(--accent-error) 10%, transparent) !important;
}

.bulk-bar :deep(.bulk-btn--suspend:hover) {
    color: var(--on-accent, #fff) !important;
    border-color: var(--accent-error) !important;
    background: var(--accent-error) !important;
    transform: translateY(-1px);
    box-shadow: 0 6px 18px color-mix(in srgb, var(--accent-error) 35%, transparent);
}

.bulk-bar :deep(.bulk-btn--restore) {
    color: var(--accent-success) !important;
    border-color: color-mix(in srgb, var(--accent-success) 40%, transparent) !important;
    background: color-mix(in srgb, var(--accent-success) 10%, transparent) !important;
}

.bulk-bar :deep(.bulk-btn--restore:hover) {
    color: var(--on-accent, #fff) !important;
    border-color: var(--accent-success) !important;
    background: var(--accent-success) !important;
    transform: translateY(-1px);
    box-shadow: 0 6px 18px color-mix(in srgb, var(--accent-success) 35%, transparent);
}

.bulk-bar :deep(.bulk-btn:active) {
    transform: translateY(0) scale(0.97);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity var(--transition), transform var(--transition);
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
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
