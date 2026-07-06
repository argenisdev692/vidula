<script setup lang="ts">
/**
 * Activity Log — read-only audit trail.
 *
 * This is the project's first server-side DataTable. Because the app is
 * Inertia-SSR-driven (list data arrives as the `logs` prop, not from a separate
 * JSON API), the table is fed by Inertia partial reloads — `router.get` with
 * `only: ['logs','filters']` on every page / sort / filter change — rather than
 * Pinia Colada `useQuery` (which would bypass Inertia). This mirrors the sibling
 * `users` / `company-data` backends and is the documented project convention.
 *
 * The trail is immutable: only View + Export actions exist — no create / edit /
 * delete / bulk. Retention is handled server-side by `activity-log:archive`.
 */
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import type { DataTablePageEvent, DataTableSortEvent } from 'primevue/datatable';
import Column from 'primevue/column';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import { useAuthorization } from '@/modules/auth/composables/useAuthorization';
import AdvancedFilter, { type FilterCriteria, type FilterField } from '@/common/data-table/AdvancedFilter.vue';
import DataTable from '@/volt/DataTable.vue';
import type { SharedProps } from '@/types/inertia';
import type { PaginatedResponse } from '@/modules/company/types';
import type { ActivityLog, ActivityLogFilters, ActivityLogQuery } from '@/modules/activity-log/types';
import { formatDateTime } from '@/modules/activity-log/helpers/formatDateTime';
import {
    buildActivityLogExportUrl,
    buildActivityLogQueryParams,
} from '@/modules/activity-log/helpers/buildActivityLogQueryParams';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    logs: PaginatedResponse<ActivityLog>;
    filters: ActivityLogFilters;
}>();

usePage<SharedProps>();
const { hasPermission } = useAuthorization();
const canExport = computed<boolean>(() => hasPermission('EXPORT_ACTIVITY_LOGS'));

const loading = ref<boolean>(false);

/** The reactive request state — seeded once from the server-echoed props. */
const query = reactive<ActivityLogQuery>({
    search: props.filters.search,
    event: props.filters.event,
    log_name: props.filters.log_name,
    causer_id: props.filters.causer_id,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    page: props.logs.current_page,
    per_page: props.logs.per_page,
    sort_dir: 'desc',
});

const firstRecord = computed<number>(() => (props.logs.current_page - 1) * props.logs.per_page);
const recordLabel = computed<string>(() => `${props.logs.total} ${props.logs.total === 1 ? 'record' : 'records'} found`);

const filterFields: FilterField[] = [
    { key: 'dateRange', label: 'Date range', type: 'date-range', placeholder: 'Start — End' },
    { key: 'event', label: 'Event', type: 'text', placeholder: 'created, updated, deleted…' },
    { key: 'causerId', label: 'Actor ID', type: 'text', placeholder: 'Filter by actor id…' },
];

function reload(): void {
    loading.value = true;
    router.get('/activity-logs', buildActivityLogQueryParams(query), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['logs', 'filters'],
        onFinish: () => {
            loading.value = false;
        },
    });
}

function toIsoDate(date: Date): string {
    return date.toISOString().slice(0, 10);
}

function onFilters(criteria: FilterCriteria): void {
    query.search = criteria.search || null;
    query.event = (criteria.event as string | undefined) || null;
    query.causer_id = (criteria.causerId as string | undefined) || null;

    const range = criteria.dateRange as Date[] | undefined;
    query.date_from = range?.[0] ? toIsoDate(range[0]) : null;
    query.date_to = range?.[1] ? toIsoDate(range[1]) : null;

    query.page = 1;
    reload();
}

function onPage(event: DataTablePageEvent): void {
    query.page = event.page + 1;
    query.per_page = event.rows;
    reload();
}

function onSort(event: DataTableSortEvent): void {
    query.sort_dir = event.sortOrder === 1 ? 'asc' : 'desc';
    query.page = 1;
    reload();
}

function openExport(format: 'csv' | 'xlsx' | 'pdf'): void {
    window.location.href = buildActivityLogExportUrl(query, format);
}

function subjectLabel(row: ActivityLog): string {
    if (!row.subject_type) {
        return '—';
    }

    return row.subject_id ? `${row.subject_type} #${row.subject_id}` : row.subject_type;
}
</script>

<template>
    <Head title="Activity Log" />

    <AppHeader title="Activity Log" subtitle="Immutable audit trail of everything that happened across the app" />

    <PermissionGuard permission="VIEW_ANY_ACTIVITY_LOGS">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to view the activity log.</p>
            </div>
        </template>

        <div class="page">
            <AdvancedFilter
                search-placeholder="Search description, event, subject…"
                :fields="filterFields"
                :show-create="false"
                :show-export-pdf="canExport"
                :show-export-excel="canExport"
                :show-export-csv="canExport"
                @filters-change="onFilters"
                @export-pdf="openExport('pdf')"
                @export-excel="openExport('xlsx')"
                @export-csv="openExport('csv')"
            />

            <p class="counter">{{ recordLabel }}</p>

            <div class="crud-table-wrap">
            <DataTable
                :value="props.logs.data"
                data-key="id"
                lazy
                paginator
                :rows="props.logs.per_page"
                :total-records="props.logs.total"
                :first="firstRecord"
                :loading="loading"
                removable-sort
                sort-field="created_at"
                :sort-order="query.sort_dir === 'asc' ? 1 : -1"
                @page="onPage"
                @sort="onSort"
            >
                <template #empty>
                    <div class="table-empty">
                        <i class="pi pi-inbox" aria-hidden="true" />
                        <span>No activity matches your filters.</span>
                    </div>
                </template>

                <Column field="created_at" header="When" sortable>
                    <template #body="{ data }">
                        <span class="mono">{{ formatDateTime((data as ActivityLog).created_at) }}</span>
                    </template>
                </Column>

                <Column field="event" header="Event">
                    <template #body="{ data }">
                        <span class="tag">{{ (data as ActivityLog).event ?? '—' }}</span>
                    </template>
                </Column>

                <Column field="description" header="Description">
                    <template #body="{ data }">
                        <span>{{ (data as ActivityLog).description }}</span>
                    </template>
                </Column>

                <Column field="causer_label" header="Actor">
                    <template #body="{ data }">
                        <span>{{ (data as ActivityLog).causer_label ?? 'System' }}</span>
                    </template>
                </Column>

                <Column header="Subject">
                    <template #body="{ data }">
                        <span>{{ subjectLabel(data as ActivityLog) }}</span>
                    </template>
                </Column>

                <Column field="log_name" header="Log">
                    <template #body="{ data }">
                        <span class="muted">{{ (data as ActivityLog).log_name ?? '—' }}</span>
                    </template>
                </Column>

                <Column header="Actions" :pt="{ bodyCell: 'w-24' }">
                    <template #body="{ data }">
                        <div class="actions-cell">
                            <PermissionGuard permission="VIEW_ACTIVITY_LOGS">
                                <Link
                                    :href="`/activity-logs/${(data as ActivityLog).id}`"
                                    class="btn-crud-action btn-crud-action-view"
                                    aria-label="View activity detail"
                                    title="View detail"
                                    v-tooltip.top="'View detail'"
                                >
                                    <i class="pi pi-eye" aria-hidden="true" />
                                </Link>
                            </PermissionGuard>
                        </div>
                    </template>
                </Column>
            </DataTable>
            </div>
        </div>
    </PermissionGuard>
</template>

<style scoped>
.page {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}

.counter {
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-muted);
}

.mono {
    font-family: var(--font-mono, monospace);
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

.tag {
    display: inline-flex;
    align-items: center;
    padding: 2px var(--space-2);
    border-radius: var(--radius-sm);
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
    background: color-mix(in srgb, var(--accent-primary) 14%, transparent);
    color: var(--accent-primary);
}

.muted {
    color: var(--text-muted);
    font-size: var(--text-sm);
}

/* ── Minimalist transparent CRUD table (matches the GUIDE crud-table) ────────
 * The Volt DataTable paints header/rows/cells with `bg-surface-*`. These scoped
 * deep overrides strip that background down to a borderless, transparent grid:
 * small uppercase centred headers, subtle row separators, gentle hover. Scoped
 * to this page — other tables keep the default Volt surface look.
 */
.crud-table-wrap :deep(table) {
    background: transparent;
}

.crud-table-wrap :deep(thead th) {
    background: transparent;
    border-bottom: 1px solid var(--border-default);
    color: var(--text-secondary);
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    text-align: center;
}

.crud-table-wrap :deep(thead th > div) {
    justify-content: center;
}

.crud-table-wrap :deep(tbody tr) {
    background: transparent;
    transition: background var(--transition);
}

.crud-table-wrap :deep(tbody tr:hover) {
    background: color-mix(in srgb, var(--bg-overlay) 40%, transparent);
}

.crud-table-wrap :deep(tbody td) {
    background: transparent;
    border-bottom: 1px solid var(--border-subtle);
    color: var(--text-primary);
    text-align: center;
    vertical-align: middle;
}

/* Neutralise the paginator surface strip so it blends into the page. */
.crud-table-wrap :deep([data-pc-name='paginator']),
.crud-table-wrap :deep([data-pc-name='pcpaginator']) {
    background: transparent;
}

/* ── Action icons (GUIDE btn-crud-action: bordered colour pill + glow) ─────── */
.actions-cell {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
    white-space: nowrap;
}

.btn-crud-action {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-subtle);
    background: color-mix(in srgb, var(--bg-elevated) 50%, transparent);
    color: var(--text-secondary);
    cursor: pointer;
    transition: transform var(--transition), border-color var(--transition), box-shadow var(--transition);
    overflow: hidden;
}

.btn-crud-action::after {
    content: '';
    position: absolute;
    inset: 0;
    background: currentColor;
    opacity: 0;
    border-radius: inherit;
    transition: opacity var(--transition);
}

.btn-crud-action:hover {
    transform: scale(1.15);
    border-color: currentColor;
}

.btn-crud-action:hover::after {
    opacity: 0.1;
}

.btn-crud-action:active {
    transform: scale(0.95);
}

.btn-crud-action:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 2px;
}

.btn-crud-action .pi {
    position: relative;
    z-index: 1;
    font-size: 0.8rem;
}

.btn-crud-action-view {
    color: var(--accent-info);
}

.btn-crud-action-view:hover {
    box-shadow: 0 0 12px color-mix(in srgb, var(--accent-info) 30%, transparent);
}

.table-empty,
.empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-10) var(--space-6);
    color: var(--text-muted);
}

.table-empty .pi,
.empty .pi {
    font-size: var(--text-2xl);
}
</style>
