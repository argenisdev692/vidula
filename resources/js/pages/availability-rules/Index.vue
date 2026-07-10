<script setup lang="ts">
/**
 * Availability Rules — full CRUD over the recurring weekly template.
 *
 * Like the sibling Blog Categories screen, the list is driven by Inertia partial
 * reloads (`router.get` with `only: ['availabilityRules','filters']` on every
 * filter / page change) rather than a JSON API. There are no create/edit page
 * routes — store/update return `back()` redirects — so create & edit happen in a
 * shared AppModal, and delete / restore / bulk go through the reusable
 * ConfirmDialog. The whole page is gated by VIEW_ANY_AVAILABILITY_RULES; every
 * mutating control by its own permission.
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
import ConfirmDialog from '@/common/data-table/ConfirmDialog.vue';
import Button from '@/volt/Button.vue';
import AvailabilityRulesTable from './components/AvailabilityRulesTable.vue';
import AvailabilityRuleFormDialog from './components/AvailabilityRuleFormDialog.vue';
import { DAY_OPTIONS, dayLabel } from '@/modules/availability/helpers/availabilityFormat';
import {
    buildAvailabilityExportUrl,
    buildAvailabilityRuleQueryParams,
    type AvailabilityExportFormat,
} from '@/modules/availability/helpers/buildAvailabilityQueryParams';
import type { SharedProps } from '@/types/inertia';
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

usePage<SharedProps>();
const toast = useToast();
const { hasPermission } = useAuthorization();

const canCreate = computed<boolean>(() => hasPermission('CREATE_AVAILABILITY_RULES'));
const canExport = computed<boolean>(() => hasPermission('EXPORT_AVAILABILITY_RULES'));
const canBulkDelete = computed<boolean>(() => hasPermission('BULK_DELETE_AVAILABILITY_RULES'));
const canBulkRestore = computed<boolean>(() => hasPermission('BULK_RESTORE_AVAILABILITY_RULES'));

const loading = ref<boolean>(false);
const selection = ref<AvailabilityRule[]>([]);

/** The reactive request state — seeded once from the server-echoed props. */
const query = reactive<AvailabilityRuleQuery>({
    day_of_week: props.filters.day_of_week,
    availability: props.filters.availability,
    status: props.filters.status,
    page: props.availabilityRules.current_page,
    per_page: props.availabilityRules.per_page,
});

const firstRecord = computed<number>(
    () => (props.availabilityRules.current_page - 1) * props.availabilityRules.per_page,
);
const recordLabel = computed<string>(
    () => `${props.availabilityRules.total} ${props.availabilityRules.total === 1 ? 'record' : 'records'} found`,
);

const isSuspendedView = computed<boolean>(() => query.status === 'suspended');
const canBulkAct = computed<boolean>(() => (isSuspendedView.value ? canBulkRestore.value : canBulkDelete.value));

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

function reload(): void {
    loading.value = true;
    router.get('/availability-rules', buildAvailabilityRuleQueryParams(query), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['availabilityRules', 'filters'],
        onFinish: () => {
            loading.value = false;
        },
    });
}

function onFilters(criteria: FilterCriteria): void {
    const day = criteria.day_of_week as string | undefined;
    query.day_of_week = day ? Number(day) : null;
    query.availability = (criteria.availability as RuleAvailability | undefined) || null;
    query.status = (criteria.status as AvailabilityStatus | undefined) || null;

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
        '/availability-rules/export',
        buildAvailabilityRuleQueryParams(query),
        format,
    );
}

/* ── Create / edit ────────────────────────────────────────────────────── */
const formVisible = ref<boolean>(false);
const formMode = ref<'create' | 'edit'>('create');
const formRule = ref<AvailabilityRule | null>(null);

function openCreate(): void {
    formMode.value = 'create';
    formRule.value = null;
    formVisible.value = true;
}

function openEdit(rule: AvailabilityRule): void {
    formMode.value = 'edit';
    formRule.value = rule;
    formVisible.value = true;
}

function onSaved(): void {
    toast.add({
        severity: 'success',
        summary: formMode.value === 'edit' ? 'Availability rule updated' : 'Availability rule created',
        life: 4000,
    });
}

/* ── Single-row delete / restore ──────────────────────────────────────── */
const rowAction = ref<{ kind: 'delete' | 'restore'; rule: AvailabilityRule } | null>(null);
const rowActionVisible = ref<boolean>(false);
const rowActionLoading = ref<boolean>(false);

const rowConfirm = computed(() => {
    const rule = rowAction.value?.rule;
    const name = rule ? `${dayLabel(rule.day_of_week)} ${rule.start_time.slice(0, 5)}–${rule.end_time.slice(0, 5)}` : 'this rule';
    if (rowAction.value?.kind === 'restore') {
        return {
            title: 'Restore availability rule',
            message: `Restore “${name}”? It will become active again.`,
            confirmLabel: 'Restore',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success' as const,
        };
    }
    return {
        title: 'Suspend availability rule',
        message: `Suspend “${name}”? It will be soft-deleted and excluded from availability. You can restore it later.`,
        confirmLabel: 'Suspend',
        confirmIcon: 'pi pi-trash',
        tone: 'danger' as const,
    };
});

function askDelete(rule: AvailabilityRule): void {
    rowAction.value = { kind: 'delete', rule };
    rowActionVisible.value = true;
}

function askRestore(rule: AvailabilityRule): void {
    rowAction.value = { kind: 'restore', rule };
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
                summary: action.kind === 'restore' ? 'Availability rule restored' : 'Availability rule suspended',
                life: 4000,
            });
        },
        onFinish: () => {
            rowActionLoading.value = false;
            rowActionVisible.value = false;
        },
    };

    if (action.kind === 'delete') {
        router.delete(`/availability-rules/${action.rule.uuid}`, options);
    } else {
        router.post(`/availability-rules/${action.rule.uuid}/restore`, {}, options);
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
            message: `Restore ${count} availability ${count === 1 ? 'rule' : 'rules'}? They will become active again.`,
            confirmLabel: 'Restore all',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success' as const,
        };
    }
    return {
        title: 'Suspend selected',
        message: `Suspend ${count} availability ${count === 1 ? 'rule' : 'rules'}? They will be soft-deleted and excluded from availability.`,
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
    const uuids = selection.value.map((r) => r.uuid);
    if (uuids.length === 0) {
        return;
    }
    bulkLoading.value = true;

    const url = isSuspendedView.value ? '/availability-rules/bulk-restore' : '/availability-rules/bulk-delete';
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
                    summary: isSuspendedView.value ? 'Selected rules restored' : 'Selected rules suspended',
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
    <Head title="Availability Rules" />

    <AppHeader title="Availability Rules" subtitle="The recurring weekly template that drives bookable hours" />

    <PermissionGuard permission="VIEW_ANY_AVAILABILITY_RULES">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to view availability rules.</p>
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
                create-label="New rule"
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

            <AvailabilityRulesTable
                v-model:selection="selection"
                :data="availabilityRules.data"
                :total="availabilityRules.total"
                :per-page="availabilityRules.per_page"
                :first="firstRecord"
                :loading="loading"
                @page="onPage"
                @edit="openEdit"
                @delete="askDelete"
                @restore="askRestore"
            />
        </div>
    </PermissionGuard>

    <AvailabilityRuleFormDialog
        v-model:visible="formVisible"
        :mode="formMode"
        :rule="formRule"
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
