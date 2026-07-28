<script setup lang="ts">
/**
 * Products — billable catalog CRUD (classroom / video) with soft-delete.
 * Mirrors clients/Index mechanics via CrudIndexShell + server-side DataTable.
 */
import { computed, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import { useAuthorization } from '@/modules/auth/composables/useAuthorization';
import type { FilterCriteria, FilterField } from '@/common/data-table/AdvancedFilter.vue';
import CrudIndexShell from '@/common/data-table/CrudIndexShell.vue';
import ConfirmDialog from '@/common/data-table/ConfirmDialog.vue';
import { useResourceList } from '@/common/data-table/useResourceList';
import { useConfirmAction } from '@/common/data-table/useConfirmAction';
import { useFormDialog } from '@/common/data-table/useFormDialog';
import { toLocalIsoDate } from '@/lib/date';
import ProductsTable from './components/ProductsTable.vue';
import ProductFormDialog from './components/ProductFormDialog.vue';
import type {
    PaginatedResponse,
    Product,
    ProductClientOption,
    ProductFilters,
    ProductLifecycleStatus,
    ProductQuery,
    ProductSoftStatus,
    ProductType,
} from '@/modules/products/types';
import { buildProductExportUrl, buildProductQueryParams } from '@/modules/products/helpers/buildProductQueryParams';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    products: PaginatedResponse<Product>;
    filters: ProductFilters;
    clients: ProductClientOption[];
}>();

const { hasPermission } = useAuthorization();

const canCreate = computed<boolean>(() => hasPermission('CREATE_PRODUCTS'));
const canExport = computed<boolean>(() => hasPermission('EXPORT_PRODUCTS'));
const canBulkDelete = computed<boolean>(() => hasPermission('BULK_DELETE_PRODUCTS'));
const canBulkRestore = computed<boolean>(() => hasPermission('BULK_RESTORE_PRODUCTS'));

const query = reactive<ProductQuery>({
    search: props.filters.search,
    status: props.filters.status,
    product_status: props.filters.product_status,
    type: props.filters.type,
    client_uuid: props.filters.client_uuid,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    page: props.products.current_page,
    per_page: props.products.per_page,
});

function applyCriteria(target: ProductQuery, criteria: FilterCriteria): void {
    target.search = criteria.search || null;
    target.status = (criteria.status as ProductSoftStatus | undefined) || null;
    target.product_status = (criteria.product_status as ProductLifecycleStatus | undefined) || null;
    target.type = (criteria.type as ProductType | undefined) || null;

    const range = criteria.dateRange as Date[] | undefined;
    target.date_from = range?.[0] ? toLocalIsoDate(range[0]) : null;
    target.date_to = range?.[1] ? toLocalIsoDate(range[1]) : null;
}

const { loading, selection, firstRecord, recordLabel, isSuspendedView, resetSelection, onFilters, onPage, openExport } =
    useResourceList<Product, ProductQuery>({
        baseUrl: '/products',
        propKey: 'products',
        query,
        pagination: computed(() => props.products),
        buildParams: buildProductQueryParams,
        applyCriteria,
        exportUrl: buildProductExportUrl,
    });

const canBulkAct = computed<boolean>(() => (isSuspendedView.value ? canBulkRestore.value : canBulkDelete.value));

const { visible: formVisible, mode: formMode, entity: formProduct, openCreate, openEdit } = useFormDialog<Product>();

type RowAction = { kind: 'delete' | 'restore'; product: Product };

const {
    visible: rowVisible,
    loading: rowLoading,
    confirm: rowConfirm,
    ask: askRow,
    run: runRow,
} = useConfirmAction<RowAction>((action) => {
    const name = action.product.title ?? 'this product';
    if (action.kind === 'restore') {
        return {
            title: 'Restore product',
            message: `Restore “${name}”? It will become active again.`,
            confirmLabel: 'Restore',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend product',
        message: `Suspend “${name}”? It will be soft-deleted. You can restore it later.`,
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
            },
            onFinish: finish,
        };
        if (action.kind === 'delete') {
            router.delete(`/products/${action.product.uuid}`, options);
        } else {
            router.post(`/products/${action.product.uuid}/restore`, {}, options);
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
            message: `Restore ${action.count} ${action.count === 1 ? 'product' : 'products'}?`,
            confirmLabel: 'Restore all',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend selected',
        message: `Suspend ${action.count} ${action.count === 1 ? 'product' : 'products'}?`,
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
        const uuids = selection.value.map((product) => product.uuid);
        if (uuids.length === 0) {
            finish();
            return;
        }
        const url = isSuspendedView.value ? '/products/bulk-restore' : '/products/bulk-delete';
        router.post(
            url,
            { uuids },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    resetSelection();
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
        key: 'product_status',
        label: 'Lifecycle',
        type: 'select',
        placeholder: 'All lifecycles',
        options: [
            { label: 'Draft', value: 'draft' },
            { label: 'Published', value: 'published' },
            { label: 'Archived', value: 'archived' },
        ],
    },
    {
        key: 'type',
        label: 'Type',
        type: 'select',
        placeholder: 'All types',
        options: [
            { label: 'Classroom', value: 'classroom' },
            { label: 'Video tutorial', value: 'video_tutorial' },
            { label: 'Video pill', value: 'video_pill' },
        ],
    },
];
</script>

<template>
    <CrudIndexShell
        title="Products"
        subtitle="Classroom and video products for content generation and billing"
        permission="VIEW_ANY_PRODUCTS"
        fallback-text="You don't have permission to view products."
        search-placeholder="Search title, slug…"
        :fields="filterFields"
        :can-export="canExport"
        :can-create="canCreate"
        create-label="New product"
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
            <ProductsTable
                v-model:selection="selection"
                :data="products.data"
                :total="products.total"
                :per-page="products.per_page"
                :first="firstRecord"
                :loading="loading"
                @page="onPage"
                @edit="openEdit"
                @delete="(product: Product) => askRow({ kind: 'delete', product })"
                @restore="(product: Product) => askRow({ kind: 'restore', product })"
            />
        </template>

        <template #dialogs>
            <ProductFormDialog
                v-model:visible="formVisible"
                :mode="formMode"
                :product="formProduct"
                :clients="clients"
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
