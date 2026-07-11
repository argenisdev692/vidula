<script setup lang="ts">
/**
 * Services — full CRUD over a soft-deletable catalog entity (the offerings
 * shown in the landing page's service `<select>`).
 *
 * The shared list mechanics (Inertia partial reloads, selection, filter / page
 * plumbing) live in {@see useResourceList}; the confirm-dialog state in
 * {@see useConfirmAction}; the page chrome in {@see CrudIndexShell}. This file
 * keeps only what is specific to services: its query shape, filter fields,
 * confirm copy and toasts. There are no create/edit page routes — store/update
 * return `back()` redirects — so create & edit happen in a Volt Dialog. Gated
 * by VIEW_ANY_SERVICES; every mutating control by its own permission. Services
 * has no export endpoint (RolePermissionSeeder::NO_EXPORT_MODULES), so unlike
 * blog-categories there is no export wiring here.
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
import ServicesTable from './components/ServicesTable.vue';
import ServiceFormDialog from './components/ServiceFormDialog.vue';
import type { PaginatedResponse, Service, ServiceFilters, ServiceQuery, ServiceStatus } from '@/modules/services/types';
import { buildServiceQueryParams } from '@/modules/services/helpers/buildServiceQueryParams';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    services: PaginatedResponse<Service>;
    filters: ServiceFilters;
}>();

const toast = useToast();
const { hasPermission } = useAuthorization();

const canCreate = computed<boolean>(() => hasPermission('CREATE_SERVICES'));
const canBulkDelete = computed<boolean>(() => hasPermission('BULK_DELETE_SERVICES'));
const canBulkRestore = computed<boolean>(() => hasPermission('BULK_RESTORE_SERVICES'));

/** The reactive request state — seeded once from the server-echoed props. */
const query = reactive<ServiceQuery>({
    search: props.filters.search,
    status: props.filters.status,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    page: props.services.current_page,
    per_page: props.services.per_page,
});

function applyCriteria(target: ServiceQuery, criteria: FilterCriteria): void {
    target.search = criteria.search || null;
    target.status = (criteria.status as ServiceStatus | undefined) || null;

    const range = criteria.dateRange as Date[] | undefined;
    target.date_from = range?.[0] ? toLocalIsoDate(range[0]) : null;
    target.date_to = range?.[1] ? toLocalIsoDate(range[1]) : null;
}

const { loading, selection, firstRecord, recordLabel, isSuspendedView, resetSelection, onFilters, onPage } =
    useResourceList<Service, ServiceQuery>({
        baseUrl: '/services',
        propKey: 'services',
        query,
        pagination: computed(() => props.services),
        buildParams: buildServiceQueryParams,
        applyCriteria,
    });

const canBulkAct = computed<boolean>(() => (isSuspendedView.value ? canBulkRestore.value : canBulkDelete.value));

/* ── Create / edit ────────────────────────────────────────────────────── */
const { visible: formVisible, mode: formMode, entity: formService, openCreate, openEdit } = useFormDialog<Service>();

function onSaved(): void {
    toast.add({
        severity: 'success',
        summary: formMode.value === 'edit' ? 'Service updated' : 'Service created',
        life: 4000,
    });
}

/* ── Single-row suspend / restore ─────────────────────────────────────── */
type RowAction = { kind: 'delete' | 'restore'; service: Service };

const {
    visible: rowVisible,
    loading: rowLoading,
    confirm: rowConfirm,
    ask: askRow,
    run: runRow,
} = useConfirmAction<RowAction>((action) => {
    const name = action.service.name ?? 'this service';
    if (action.kind === 'restore') {
        return {
            title: 'Restore service',
            message: `Restore “${name}”? It will become active again.`,
            confirmLabel: 'Restore',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend service',
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
                    summary: action.kind === 'restore' ? 'Service restored' : 'Service suspended',
                    life: 4000,
                });
            },
            onFinish: finish,
        };
        if (action.kind === 'delete') {
            router.delete(`/services/${action.service.uuid}`, options);
        } else {
            router.post(`/services/${action.service.uuid}/restore`, {}, options);
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
            message: `Restore ${action.count} ${action.count === 1 ? 'service' : 'services'}? They will become active again.`,
            confirmLabel: 'Restore all',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend selected',
        message: `Suspend ${action.count} ${action.count === 1 ? 'service' : 'services'}? They will be soft-deleted and hidden from the active list.`,
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
        const uuids = selection.value.map((service) => service.uuid);
        if (uuids.length === 0) {
            finish();
            return;
        }
        const url = isSuspendedView.value ? '/services/bulk-restore' : '/services/bulk-delete';
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
                        summary: isSuspendedView.value ? 'Selected services restored' : 'Selected services suspended',
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
];
</script>

<template>
    <CrudIndexShell
        title="Services"
        subtitle="Manage the service catalog offered on the landing page"
        permission="VIEW_ANY_SERVICES"
        fallback-text="You don't have permission to view services."
        search-placeholder="Search name or slug…"
        :fields="filterFields"
        :can-export="false"
        :can-create="canCreate"
        create-label="New service"
        :record-label="recordLabel"
        :selection-count="selection.length"
        :can-bulk-act="canBulkAct"
        :is-suspended-view="isSuspendedView"
        @filters-change="onFilters"
        @create="openCreate"
        @bulk="askBulk"
    >
        <template #table>
            <ServicesTable
                v-model:selection="selection"
                :data="services.data"
                :total="services.total"
                :per-page="services.per_page"
                :first="firstRecord"
                :loading="loading"
                @page="onPage"
                @edit="openEdit"
                @delete="(service: Service) => askRow({ kind: 'delete', service })"
                @restore="(service: Service) => askRow({ kind: 'restore', service })"
            />
        </template>

        <template #dialogs>
            <ServiceFormDialog
                v-model:visible="formVisible"
                :mode="formMode"
                :service="formService"
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
