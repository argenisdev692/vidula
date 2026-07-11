<script setup lang="ts">
/**
 * Contact & Support — full CRUD over a soft-deletable inbox entity.
 *
 * The shared list mechanics live in {@see useResourceList}, the confirm dialogs
 * in {@see useConfirmAction}, and the page chrome in {@see CrudIndexShell}. This
 * file keeps only what is specific to the inbox: the read-state filter, the
 * lazy-loaded message body on edit, the mark-as-read action, and its confirm
 * copy + toasts. Gated by VIEW_ANY_CONTACT_SUPPORTS; every mutating control by
 * its own permission.
 */
import { computed, reactive, ref } from 'vue';
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
import ContactSupportsTable from './components/ContactSupportsTable.vue';
import ContactSupportFormDialog from './components/ContactSupportFormDialog.vue';
import { useContactSupportMessage } from '@/modules/contact-support/composables/useContactSupportMessage';
import type {
    ContactSupport,
    ContactSupportFilters,
    ContactSupportQuery,
    ContactSupportRead,
    ContactSupportSpam,
    ContactSupportStatus,
    PaginatedResponse,
} from '@/modules/contact-support/types';
import { buildContactSupportExportUrl, buildContactSupportQueryParams } from '@/modules/contact-support/helpers/buildContactSupportQueryParams';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    contactSupports: PaginatedResponse<ContactSupport>;
    filters: ContactSupportFilters;
}>();

const toast = useToast();
const { hasPermission } = useAuthorization();
const { fetchMessage } = useContactSupportMessage();

const canCreate = computed<boolean>(() => hasPermission('CREATE_CONTACT_SUPPORTS'));
const canExport = computed<boolean>(() => hasPermission('EXPORT_CONTACT_SUPPORTS'));
const canBulkDelete = computed<boolean>(() => hasPermission('BULK_DELETE_CONTACT_SUPPORTS'));
const canBulkRestore = computed<boolean>(() => hasPermission('BULK_RESTORE_CONTACT_SUPPORTS'));

/** The reactive request state — seeded once from the server-echoed props. */
const query = reactive<ContactSupportQuery>({
    search: props.filters.search,
    status: props.filters.status,
    read: props.filters.read,
    spam: props.filters.spam,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    page: props.contactSupports.current_page,
    per_page: props.contactSupports.per_page,
});

function applyCriteria(target: ContactSupportQuery, criteria: FilterCriteria): void {
    target.search = criteria.search || null;
    target.status = (criteria.status as ContactSupportStatus | undefined) || null;
    target.read = (criteria.read as ContactSupportRead | undefined) || null;
    target.spam = (criteria.spam as ContactSupportSpam | undefined) || null;

    const range = criteria.dateRange as Date[] | undefined;
    target.date_from = range?.[0] ? toLocalIsoDate(range[0]) : null;
    target.date_to = range?.[1] ? toLocalIsoDate(range[1]) : null;
}

const { loading, selection, firstRecord, recordLabel, isSuspendedView, resetSelection, reload, onFilters, onPage, openExport } =
    useResourceList<ContactSupport, ContactSupportQuery>({
        baseUrl: '/contact-supports',
        propKey: 'contactSupports',
        query,
        pagination: computed(() => props.contactSupports),
        buildParams: buildContactSupportQueryParams,
        applyCriteria,
        exportUrl: buildContactSupportExportUrl,
    });

const canBulkAct = computed<boolean>(() => (isSuspendedView.value ? canBulkRestore.value : canBulkDelete.value));

/* ── Create / edit (edit lazy-loads the message body) ─────────────────── */
const { visible: formVisible, mode: formMode, entity: formContact, openCreate: openCreateBase, openEdit: openEditBase } =
    useFormDialog<ContactSupport>();
const formMessage = ref<string>('');

function openCreate(): void {
    formMessage.value = '';
    openCreateBase();
}

async function openEdit(contact: ContactSupport): Promise<void> {
    formMessage.value = await fetchMessage(contact.uuid);
    openEditBase(contact);
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

/* ── Single-row suspend / restore ─────────────────────────────────────── */
type RowAction = { kind: 'delete' | 'restore'; contact: ContactSupport };

const {
    visible: rowVisible,
    loading: rowLoading,
    confirm: rowConfirm,
    ask: askRow,
    run: runRow,
} = useConfirmAction<RowAction>((action) => {
    const name = [action.contact.first_name, action.contact.last_name].filter(Boolean).join(' ').trim() || 'this request';
    if (action.kind === 'restore') {
        return {
            title: 'Restore contact request',
            message: `Restore the request from “${name}”? It will become active again.`,
            confirmLabel: 'Restore',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend contact request',
        message: `Suspend the request from “${name}”? It will be soft-deleted and hidden from the active list. You can restore it later.`,
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
                    summary: action.kind === 'restore' ? 'Contact request restored' : 'Contact request suspended',
                    life: 4000,
                });
            },
            onFinish: finish,
        };
        if (action.kind === 'delete') {
            router.delete(`/contact-supports/${action.contact.uuid}`, options);
        } else {
            router.post(`/contact-supports/${action.contact.uuid}/restore`, {}, options);
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
            message: `Restore ${action.count} contact ${action.count === 1 ? 'request' : 'requests'}? They will become active again.`,
            confirmLabel: 'Restore all',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend selected',
        message: `Suspend ${action.count} contact ${action.count === 1 ? 'request' : 'requests'}? They will be soft-deleted and hidden from the active list.`,
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
        const uuids = selection.value.map((contact) => contact.uuid);
        if (uuids.length === 0) {
            finish();
            return;
        }
        const url = isSuspendedView.value ? '/contact-supports/bulk-restore' : '/contact-supports/bulk-delete';
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
                        summary: isSuspendedView.value ? 'Selected requests restored' : 'Selected requests suspended',
                        life: 4000,
                    });
                },
                onFinish: finish,
            },
        );
    });
}

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
    {
        key: 'spam',
        label: 'Spam',
        type: 'select',
        placeholder: 'All',
        options: [
            { label: 'Legitimate', value: 'ham' },
            { label: 'Spam', value: 'spam' },
        ],
    },
];
</script>

<template>
    <CrudIndexShell
        title="Contact & Support"
        subtitle="Manage inbound contact requests and support enquiries"
        permission="VIEW_ANY_CONTACT_SUPPORTS"
        fallback-text="You don't have permission to view contact requests."
        search-placeholder="Search name, email or subject…"
        :fields="filterFields"
        :can-export="canExport"
        :can-create="canCreate"
        create-label="New request"
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
            <ContactSupportsTable
                v-model:selection="selection"
                :data="contactSupports.data"
                :total="contactSupports.total"
                :per-page="contactSupports.per_page"
                :first="firstRecord"
                :loading="loading"
                @page="onPage"
                @edit="openEdit"
                @delete="(contact: ContactSupport) => askRow({ kind: 'delete', contact })"
                @restore="(contact: ContactSupport) => askRow({ kind: 'restore', contact })"
                @mark-read="markRead"
            />
        </template>

        <template #dialogs>
            <ContactSupportFormDialog
                v-model:visible="formVisible"
                :mode="formMode"
                :contact="formContact"
                :message="formMessage"
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
