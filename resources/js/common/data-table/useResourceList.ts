/**
 * The mechanical half of a server-side CRUD list screen — everything the 7
 * near-identical index pages copy-pasted: loading / selection state, the
 * `firstRecord` + `recordLabel` derivations, the suspended-view flag, and the
 * Inertia partial-reload / filter / page / export plumbing.
 *
 * Each page keeps only what genuinely differs: its reactive `query`, how filter
 * criteria map onto that query (`applyCriteria`), and the param / export-URL
 * builders. Confirm-dialog copy and mutating requests live in {@see useConfirmAction}.
 *
 * Layer: common/ — imports Inertia + lib/ only, never modules/ or Pages/.
 */
import { computed, ref, toValue, type ComputedRef, type MaybeRef, type Ref } from 'vue';
import { router } from '@inertiajs/vue3';
import type { DataTablePageEvent } from 'primevue/datatable';
import type { FilterCriteria } from './AdvancedFilter.vue';
import type { ExportFormat } from '@/lib/queryParams';

/** The pagination envelope every `PaginatedResponse<T>` satisfies structurally. */
interface Pagination {
    current_page: number;
    per_page: number;
    total: number;
}

/** The minimal shape a query must have to drive the shared reload / suspend logic. */
interface ListQuery {
    page: number;
    per_page: number;
    status?: string | null;
}

export interface ResourceListOptions<T, Q extends ListQuery> {
    /** Resource base path, e.g. `/blog-categories`. */
    baseUrl: string;
    /** The Inertia prop key to partially reload, e.g. `blogCategories`. */
    propKey: string;
    /** The page-owned reactive query, seeded from the server-echoed props. */
    query: MaybeRef<Q>;
    /** The paginator, typically `computed(() => props.blogCategories)`. */
    pagination: ComputedRef<Pagination> | Ref<Pagination>;
    /** Serialises the query into the request params (module-specific keys). */
    buildParams: (query: Q) => Record<string, string | number>;
    /** Maps the flat AdvancedFilter criteria onto the module-specific query keys. */
    applyCriteria: (query: Q, criteria: FilterCriteria) => void;
    /** Builds the export download URL; omit when the resource has no export. */
    exportUrl?: (query: Q, format: ExportFormat) => string;
}

export function useResourceList<T, Q extends ListQuery>(options: ResourceListOptions<T, Q>) {
    const { baseUrl, propKey, query, pagination } = options;

    const loading = ref<boolean>(false);
    const selection = ref<T[]>([]) as Ref<T[]>;

    const firstRecord = computed<number>(() => (pagination.value.current_page - 1) * pagination.value.per_page);
    const recordLabel = computed<string>(() => {
        const total = pagination.value.total;
        return `${total} ${total === 1 ? 'record' : 'records'} found`;
    });

    /** The list is homogeneous per the status filter: suspended view ⇒ restore. */
    const isSuspendedView = computed<boolean>(() => toValue(query).status === 'suspended');

    function resetSelection(): void {
        selection.value = [];
    }

    function reload(): void {
        const currentQuery = toValue(query);
        loading.value = true;
        router.get(baseUrl, options.buildParams(currentQuery), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: [propKey, 'filters'],
            onFinish: () => {
                loading.value = false;
            },
        });
    }

    function onFilters(criteria: FilterCriteria): void {
        const currentQuery = toValue(query);
        options.applyCriteria(currentQuery, criteria);
        currentQuery.page = 1;
        resetSelection();
        reload();
    }

    function onPage(event: DataTablePageEvent): void {
        const currentQuery = toValue(query);
        currentQuery.page = event.page + 1;
        currentQuery.per_page = event.rows;
        reload();
    }

    function openExport(format: ExportFormat): void {
        if (options.exportUrl) {
            window.location.href = options.exportUrl(toValue(query), format);
        }
    }

    return {
        loading,
        selection,
        firstRecord,
        recordLabel,
        isSuspendedView,
        resetSelection,
        reload,
        onFilters,
        onPage,
        openExport,
    };
}
