<script setup lang="ts">
/**
 * Resume Studio hub — mode cards + server-side runs table (GET /resume-studio).
 */
import { computed, reactive, ref } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import { useAuthorization } from '@/modules/auth/composables/useAuthorization';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import type { FilterCriteria, FilterField } from '@/common/data-table/AdvancedFilter.vue';
import CrudIndexShell from '@/common/data-table/CrudIndexShell.vue';
import { useResourceList } from '@/common/data-table/useResourceList';
import { toLocalIsoDate } from '@/lib/date';
import StudioRunsTable from './components/StudioRunsTable.vue';
import StartStudioDialog from './components/StartStudioDialog.vue';
import type {
    CvOption,
    JobSearchConfig,
    PaginatedResponse,
    StudioFilters,
    StudioMode,
    StudioQuery,
    StudioRun,
    StudioSoftStatus,
} from '@/modules/resume-studio/types';
import { buildStudioExportUrl, buildStudioQueryParams } from '@/modules/resume-studio/helpers/buildStudioQueryParams';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    runs: PaginatedResponse<StudioRun>;
    configs: JobSearchConfig[];
    filters: StudioFilters;
    cvs: CvOption[];
}>();

const { hasPermission } = useAuthorization();

const canCreate = computed<boolean>(() => hasPermission('RUN_RESUME_STUDIOS'));
const canExport = computed<boolean>(() => hasPermission('EXPORT_RESUME_STUDIOS'));

const dialogVisible = ref<boolean>(false);
const dialogMode = ref<StudioMode>('career');

const modeCards: Array<{ mode: StudioMode; title: string; blurb: string; icon: string }> = [
    {
        mode: 'career',
        title: 'Career (fullstack)',
        blurb: 'ATS refine with GitHub enrichment, job search, scoring, and outreach drafts.',
        icon: 'pi pi-briefcase',
    },
    {
        mode: 'other',
        title: 'Other niche',
        blurb: 'Custom targeting prompt for non-fullstack CVs — same pipeline, different niche.',
        icon: 'pi pi-compass',
    },
];

const query = reactive<StudioQuery>({
    search: props.filters.search,
    status: props.filters.status,
    mode: props.filters.mode,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    run_uuid: props.filters.run_uuid ?? null,
    page: props.runs.current_page,
    per_page: props.runs.per_page,
});

function applyCriteria(target: StudioQuery, criteria: FilterCriteria): void {
    target.search = criteria.search || null;
    target.status = (criteria.status as StudioSoftStatus | undefined) || null;
    target.mode = (criteria.mode as StudioMode | undefined) || null;

    const range = criteria.dateRange as Date[] | undefined;
    target.date_from = range?.[0] ? toLocalIsoDate(range[0]) : null;
    target.date_to = range?.[1] ? toLocalIsoDate(range[1]) : null;
}

const { loading, firstRecord, recordLabel, onFilters, onPage, openExport } = useResourceList<StudioRun, StudioQuery>({
    baseUrl: '/resume-studio',
    propKey: 'runs',
    query,
    pagination: computed(() => props.runs),
    buildParams: buildStudioQueryParams,
    applyCriteria,
    exportUrl: buildStudioExportUrl,
});

function openStudioDialog(mode: StudioMode): void {
    dialogMode.value = mode;
    dialogVisible.value = true;
}

function onCreate(): void {
    openStudioDialog('career');
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
        key: 'mode',
        label: 'Mode',
        type: 'select',
        placeholder: 'All modes',
        options: [
            { label: 'Career', value: 'career' },
            { label: 'Other niche', value: 'other' },
        ],
    },
];
</script>

<template>
    <CrudIndexShell
        title="Resume Studio"
        subtitle="ATS refine, job matching, and outreach drafts"
        permission="VIEW_ANY_RESUME_STUDIOS"
        fallback-text="You don't have permission to view Resume Studio."
        search-placeholder="Search mode, status…"
        :fields="filterFields"
        :can-export="canExport"
        :can-create="canCreate"
        create-label="Start studio"
        :record-label="recordLabel"
        :selection-count="0"
        :can-bulk-act="false"
        :is-suspended-view="query.status === 'suspended'"
        @filters-change="onFilters"
        @create="onCreate"
        @export-pdf="openExport('pdf')"
        @export-excel="openExport('xlsx')"
        @export-csv="openExport('csv')"
    >
        <template #table>
            <PermissionGuard permission="RUN_RESUME_STUDIOS">
                <section class="mode-hub" aria-label="Start a studio run">
                    <button
                        v-for="card in modeCards"
                        :key="card.mode"
                        type="button"
                        class="mode-card"
                        :aria-label="`Start ${card.title} run`"
                        @click="openStudioDialog(card.mode)"
                    >
                        <i :class="card.icon" aria-hidden="true" />
                        <span class="mode-card__title">{{ card.title }}</span>
                        <span class="mode-card__blurb">{{ card.blurb }}</span>
                    </button>
                </section>
            </PermissionGuard>

            <StudioRunsTable
                :data="runs.data"
                :total="runs.total"
                :per-page="runs.per_page"
                :first="firstRecord"
                :loading="loading"
                @page="onPage"
            />
        </template>

        <template #dialogs>
            <StartStudioDialog
                v-model:visible="dialogVisible"
                :mode="dialogMode"
                :cvs="cvs"
            />
        </template>
    </CrudIndexShell>
</template>

<style scoped>
.mode-hub {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-4);
    margin-bottom: var(--space-4);
}

.mode-card {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: var(--space-2);
    padding: var(--space-5);
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-subtle);
    background: color-mix(in srgb, var(--bg-surface) 70%, transparent);
    color: var(--text-primary);
    cursor: pointer;
    text-align: left;
    transition:
        border-color var(--transition),
        transform var(--transition),
        box-shadow var(--transition);
}

.mode-card .pi {
    font-size: var(--text-xl);
    color: var(--accent-primary);
}

.mode-card:hover {
    border-color: var(--accent-primary);
    transform: translateY(-2px);
    box-shadow: 0 8px 24px color-mix(in srgb, var(--accent-primary) 12%, transparent);
}

.mode-card__title {
    font-weight: var(--font-semibold);
    font-size: var(--text-sm);
}

.mode-card__blurb {
    font-size: var(--text-xs);
    color: var(--text-muted);
    line-height: 1.45;
}

@media (max-width: 640px) {
    .mode-hub {
        grid-template-columns: 1fr;
    }
}

@media (prefers-reduced-motion: reduce) {
    .mode-card {
        transition: none;
    }

    .mode-card:hover {
        transform: none;
        box-shadow: none;
    }
}
</style>
