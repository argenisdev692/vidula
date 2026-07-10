<script setup lang="ts">
/**
 * Availability Rules — full CRUD over the recurring weekly template.
 *
 * The shared list mechanics live in {@see useResourceList}, the confirm dialogs
 * in {@see useConfirmAction}, and the page chrome in {@see CrudIndexShell}. This
 * file keeps only what is specific to rules: its weekday / availability filters
 * (the query has no free-text search or date range), confirm copy and toasts.
 * Gated by VIEW_ANY_AVAILABILITY_RULES; every mutating control by its own permission.
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
import AvailabilityRulesTable from './components/AvailabilityRulesTable.vue';
import AvailabilityRuleFormDialog from './components/AvailabilityRuleFormDialog.vue';
import { DAY_OPTIONS, dayLabel } from '@/modules/availability/helpers/availabilityFormat';
import { buildAvailabilityExportUrl, buildAvailabilityRuleQueryParams } from '@/modules/availability/helpers/buildAvailabilityQueryParams';
import type { ExportFormat } from '@/lib/queryParams';
import type {
    AvailabilityRule,
    AvailabilityRuleFilters,
    AvailabilityRuleQuery,
    AvailabilityStatus,
    PaginatedResponse,
    RuleAvailability,
} from '@/modules/availability/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    availabilityRules: PaginatedResponse<AvailabilityRule>;
    filters: AvailabilityRuleFilters;
}>();

const toast = useToast();
const { hasPermission } = useAuthorization();

const canCreate = computed<boolean>(() => hasPermission('CREATE_AVAILABILITY_RULES'));
const canExport = computed<boolean>(() => hasPermission('EXPORT_AVAILABILITY_RULES'));
const canBulkDelete = computed<boolean>(() => hasPermission('BULK_DELETE_AVAILABILITY_RULES'));
const canBulkRestore = computed<boolean>(() => hasPermission('BULK_RESTORE_AVAILABILITY_RULES'));

/** The reactive request state — seeded once from the server-echoed props. */
const query = reactive<AvailabilityRuleQuery>({
    day_of_week: props.filters.day_of_week,
    availability: props.filters.availability,
    status: props.filters.status,
    page: props.availabilityRules.current_page,
    per_page: props.availabilityRules.per_page,
});

function applyCriteria(target: AvailabilityRuleQuery, criteria: FilterCriteria): void {
    const day = criteria.day_of_week as string | undefined;
    target.day_of_week = day ? Number(day) : null;
    target.availability = (criteria.availability as RuleAvailability | undefined) || null;
    target.status = (criteria.status as AvailabilityStatus | undefined) || null;
}

function exportUrl(current: AvailabilityRuleQuery, format: ExportFormat): string {
    return buildAvailabilityExportUrl('/availability-rules/export', buildAvailabilityRuleQueryParams(current), format);
}

const { loading, selection, firstRecord, recordLabel, isSuspendedView, resetSelection, onFilters, onPage, openExport } =
    useResourceList<AvailabilityRule, AvailabilityRuleQuery>({
        baseUrl: '/availability-rules',
        propKey: 'availabilityRules',
        query,
        pagination: computed(() => props.availabilityRules),
        buildParams: buildAvailabilityRuleQueryParams,
        applyCriteria,
        exportUrl,
    });

const canBulkAct = computed<boolean>(() => (isSuspendedView.value ? canBulkRestore.value : canBulkDelete.value));

/* ── Create / edit ────────────────────────────────────────────────────── */
const { visible: formVisible, mode: formMode, entity: formRule, openCreate, openEdit } = useFormDialog<AvailabilityRule>();

function onSaved(): void {
    toast.add({
        severity: 'success',
        summary: formMode.value === 'edit' ? 'Availability rule updated' : 'Availability rule created',
        life: 4000,
    });
}

/* ── Single-row suspend / restore ─────────────────────────────────────── */
type RowAction = { kind: 'delete' | 'restore'; rule: AvailabilityRule };

const {
    visible: rowVisible,
    loading: rowLoading,
    confirm: rowConfirm,
    ask: askRow,
    run: runRow,
} = useConfirmAction<RowAction>((action) => {
    const rule = action.rule;
    const name = `${dayLabel(rule.day_of_week)} ${rule.start_time.slice(0, 5)}–${rule.end_time.slice(0, 5)}`;
    if (action.kind === 'restore') {
        return {
            title: 'Restore availability rule',
            message: `Restore “${name}”? It will become active again.`,
            confirmLabel: 'Restore',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend availability rule',
        message: `Suspend “${name}”? It will be soft-deleted and excluded from availability. You can restore it later.`,
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
                    summary: action.kind === 'restore' ? 'Availability rule restored' : 'Availability rule suspended',
                    life: 4000,
                });
            },
            onFinish: finish,
        };
        if (action.kind === 'delete') {
            router.delete(`/availability-rules/${action.rule.uuid}`, options);
        } else {
            router.post(`/availability-rules/${action.rule.uuid}/restore`, {}, options);
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
            message: `Restore ${action.count} availability ${action.count === 1 ? 'rule' : 'rules'}? They will become active again.`,
            confirmLabel: 'Restore all',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend selected',
        message: `Suspend ${action.count} availability ${action.count === 1 ? 'rule' : 'rules'}? They will be soft-deleted and excluded from availability.`,
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
        const uuids = selection.value.map((rule) => rule.uuid);
        if (uuids.length === 0) {
            finish();
            return;
        }
        const url = isSuspendedView.value ? '/availability-rules/bulk-restore' : '/availability-rules/bulk-delete';
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
                        summary: isSuspendedView.value ? 'Selected rules restored' : 'Selected rules suspended',
                        life: 4000,
                    });
                },
                onFinish: finish,
            },
        );
    });
}

const filterFields: FilterField[] = [
    { key: 'day_of_week', label: 'Weekday', type: 'select', placeholder: 'Any day', options: DAY_OPTIONS },
    {
        key: 'availability',
        label: 'Availability',
        type: 'select',
        placeholder: 'Any',
        options: [
            { label: 'Available', value: 'available' },
            { label: 'Unavailable', value: 'unavailable' },
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
</script>

<template>
    <CrudIndexShell
        title="Availability Rules"
        subtitle="The recurring weekly template that drives bookable hours"
        permission="VIEW_ANY_AVAILABILITY_RULES"
        fallback-text="You don't have permission to view availability rules."
        search-placeholder="Search…"
        :fields="filterFields"
        :can-export="canExport"
        :can-create="canCreate"
        create-label="New rule"
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
            <AvailabilityRulesTable
                v-model:selection="selection"
                :data="availabilityRules.data"
                :total="availabilityRules.total"
                :per-page="availabilityRules.per_page"
                :first="firstRecord"
                :loading="loading"
                @page="onPage"
                @edit="openEdit"
                @delete="(rule: AvailabilityRule) => askRow({ kind: 'delete', rule })"
                @restore="(rule: AvailabilityRule) => askRow({ kind: 'restore', rule })"
            />
        </template>

        <template #dialogs>
            <AvailabilityRuleFormDialog
                v-model:visible="formVisible"
                :mode="formMode"
                :rule="formRule"
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
