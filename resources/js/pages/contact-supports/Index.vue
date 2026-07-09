<script setup lang="ts">
/**
 * Contact & Support — full CRUD over a soft-deletable inbox entity.
 *
 * Like the sibling Blog Categories / Activity Log screens, the list is driven by
 * Inertia partial reloads (`router.get` with `only: ['contactSupports','filters']`
 * on every filter / page change) rather than a separate JSON API. There are no
 * create/edit page routes — the backend store/update return `back()` redirects —
 * so create & edit happen in a Volt Dialog, and delete / restore / bulk / mark-read
 * go through the reusable ConfirmDialog and dedicated actions. The whole page is
 * gated by VIEW_ANY_CONTACT_SUPPORTS; every mutating control by its own permission
 * (CREATE / UPDATE / DELETE / RESTORE / BULK_*).
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
import ContactSupportsTable from './components/ContactSupportsTable.vue';
import ContactSupportFormDialog from './components/ContactSupportFormDialog.vue';
import { useContactSupportMessage } from '@/modules/contact-support/composables/useContactSupportMessage';
import type { SharedProps } from '@/types/inertia';
import type {
    ContactSupport,
    ContactSupportFilters,
    ContactSupportQuery,
    ContactSupportRead,
    ContactSupportStatus,
    PaginatedResponse,
} from '@/modules/contact-support/types';
import {
    buildContactSupportExportUrl,
    buildContactSupportQueryParams,
    type ContactSupportExportFormat,
} from '@/modules/contact-support/helpers/buildContactSupportQueryParams';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    contactSupports: PaginatedResponse<ContactSupport>;
    filters: ContactSupportFilters;
}>();

usePage<SharedProps>();
const toast = useToast();
const { hasPermission } = useAuthorization();
const { fetchMessage } = useContactSupportMessage();

const canCreate = computed<boolean>(() => hasPermission('CREATE_CONTACT_SUPPORTS'));
const canExport = computed<boolean>(() => hasPermission('EXPORT_CONTACT_SUPPORTS'));
const canBulkDelete = computed<boolean>(() => hasPermission('BULK_DELETE_CONTACT_SUPPORTS'));
const canBulkRestore = computed<boolean>(() => hasPermission('BULK_RESTORE_CONTACT_SUPPORTS'));

const loading = ref<boolean>(false);
const selection = ref<ContactSupport[]>([]);

/** The reactive request state — seeded once from the server-echoed props. */
const query = reactive<ContactSupportQuery>({
    search: props.filters.search,
    status: props.filters.status,
    read: props.filters.read,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    page: props.contactSupports.current_page,
    per_page: props.contactSupports.per_page,
});

const firstRecord = computed<number>(() => (props.contactSupports.current_page - 1) * props.contactSupports.per_page);
const recordLabel = computed<string>(
    () => `${props.contactSupports.total} ${props.contactSupports.total === 1 ? 'record' : 'records'} found`,
);

/** The current list is homogeneous: suspended view ⇒ restore, otherwise delete. */
const isSuspendedView = computed<boolean>(() => query.status === 'suspended');
const canBulkAct = computed<boolean>(() => (isSuspendedView.value ? canBulkRestore.value : canBulkDelete.value));

const filterFields: FilterField[] = [
    { key: 'dateRange', label: 'Received between', type: 'date-range', placeholder: 'Start — End' },
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
        key: 'read',
        label: 'Read state',
        type: 'select',
        placeholder: 'All',
        options: [
            { label: 'Read', value: 'read' },
            { label: 'Unread', value: 'unread' },
        ],
    },
];

function toIsoDate(date: Date): string {
    return date.toISOString().slice(0, 10);
}

function reload(): void {
    loading.value = true;
    router.get('/contact-supports', buildContactSupportQueryParams(query), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['contactSupports', 'filters'],
        onFinish: () => {
            loading.value = false;
        },
    });
}

function onFilters(criteria: FilterCriteria): void {
    query.search = criteria.search || null;
    query.status = (criteria.status as ContactSupportStatus | undefined) || null;
    query.read = (criteria.read as ContactSupportRead | undefined) || null;

    const range = criteria.dateRange as Date[] | undefined;
    query.date_from = range?.[0] ? toIsoDate(range[0]) : null;
    query.date_to = range?.[1] ? toIsoDate(range[1]) : null;

    query.page = 1;
    selection.value = [];
    reload();
}

function onPage(event: DataTablePageEvent): void {
    query.page = event.page + 1;
    query.per_page = event.rows;
    reload();
}

function openExport(format: ContactSupportExportFormat): void {
    window.location.href = buildContactSupportExportUrl(query, format);
}

/* ── Create / edit ────────────────────────────────────────────────────── */
const formVisible = ref<boolean>(false);
const formMode = ref<'create' | 'edit'>('create');
const formContact = ref<ContactSupport | null>(null);
const formMessage = ref<string>('');

function openCreate(): void {
    formMode.value = 'create';
    formContact.value = null;
    formMessage.value = '';
    formVisible.value = true;
}

async function openEdit(contact: ContactSupport): Promise<void> {
    formMode.value = 'edit';
    formContact.value = contact;
    formMessage.value = await fetchMessage(contact.uuid);
    formVisible.value = true;
}

function onSaved(): void {
    toast.add({
        severity: 'success',
        summary: formMode.value === 'edit' ? 'Contact request updated' : 'Contact request created',
        life: 4000,
    });
    reload();
}

/* ── Mark as read ─────────────────────────────────────────────────────── */
function markRead(contact: ContactSupport): void {
    router.patch(`/contact-supports/${contact.uuid}/read`, {}, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Marked as read', life: 3000 });
            reload();
        },
    });
}

/* ── Single-row delete / restore ──────────────────────────────────────── */
const rowAction = ref<{ kind: 'delete' | 'restore'; contact: ContactSupport } | null>(null);
const rowActionVisible = ref<boolean>(false);
const rowActionLoading = ref<boolean>(false);

const rowConfirm = computed(() => {
    const name = rowAction.value
        ? [rowAction.value.contact.first_name, rowAction.value.contact.last_name].filter(Boolean).join(' ').trim()
        : 'this request';
    if (rowAction.value?.kind === 'restore') {
        return {
            title: 'Restore contact request',
            message: `Restore the request from “${name}”? It will become active again.`,
            confirmLabel: 'Restore',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success' as const,
        };
    }
    return {
        title: 'Suspend contact request',
        message: `Suspend the request from “${name}”? It will be soft-deleted and hidden from the active list. You can restore it later.`,
        confirmLabel: 'Suspend',
        confirmIcon: 'pi pi-trash',
        tone: 'danger' as const,
    };
});

function askDelete(contact: ContactSupport): void {
    rowAction.value = { kind: 'delete', contact };
    rowActionVisible.value = true;
}

function askRestore(contact: ContactSupport): void {
    rowAction.value = { kind: 'restore', contact };
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
                summary: action.kind === 'restore' ? 'Contact request restored' : 'Contact request suspended',
                life: 4000,
            });
        },
        onFinish: () => {
            rowActionLoading.value = false;
            rowActionVisible.value = false;
        },
    };

    if (action.kind === 'delete') {
        router.delete(`/contact-supports/${action.contact.uuid}`, options);
    } else {
        router.post(`/contact-supports/${action.contact.uuid}/restore`, {}, options);
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
            message: `Restore ${count} contact ${count === 1 ? 'request' : 'requests'}? They will become active again.`,
            confirmLabel: 'Restore all',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success' as const,
        };
    }
    return {
        title: 'Suspend selected',
        message: `Suspend ${count} contact ${count === 1 ? 'request' : 'requests'}? They will be soft-deleted and hidden from the active list.`,
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
    const uuids = selection.value.map((c) => c.uuid);
    if (uuids.length === 0) {
        return;
    }
    bulkLoading.value = true;

    const url = isSuspendedView.value ? '/contact-supports/bulk-restore' : '/contact-supports/bulk-delete';
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
                    summary: isSuspendedView.value ? 'Selected requests restored' : 'Selected requests suspended',
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
    <Head title="Contact & Support" />

    <AppHeader title="Contact & Support" subtitle="Manage inbound contact requests and support enquiries" />

    <PermissionGuard permission="VIEW_ANY_CONTACT_SUPPORTS">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to view contact requests.</p>
            </div>
        </template>

        <div class="page">
            <AdvancedFilter
                search-placeholder="Search name, email or subject…"
                :fields="filterFields"
                :show-export-pdf="canExport"
                :show-export-excel="canExport"
                :show-export-csv="canExport"
                :show-create="canCreate"
                create-label="New request"
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
                            @click="askBulk"
                        />
                    </div>
                </Transition>
            </div>

            <ContactSupportsTable
                v-model:selection="selection"
                :data="contactSupports.data"
                :total="contactSupports.total"
                :per-page="contactSupports.per_page"
                :first="firstRecord"
                :loading="loading"
                @page="onPage"
                @edit="openEdit"
                @delete="askDelete"
                @restore="askRestore"
                @mark-read="markRead"
            />
        </div>
    </PermissionGuard>

    <ContactSupportFormDialog
        v-model:visible="formVisible"
        :mode="formMode"
        :contact="formContact"
        :message="formMessage"
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
