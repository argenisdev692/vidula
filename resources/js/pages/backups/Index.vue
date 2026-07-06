<script setup lang="ts">
/**
 * Backups — admin panel over spatie/laravel-backup.
 *
 * Backups are files on the `local` disk (no DB), so this table is client-side
 * (the count is small) rather than the server-side lazy pattern used by the
 * activity log — an honest fit for the data source. Actions: download, delete,
 * and "Run backup now" (queued to Horizon). Scheduled backup:run / clean /
 * monitor run independently via the cron.
 */
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import Column from 'primevue/column';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import DataTable from '@/volt/DataTable.vue';
import Button from '@/volt/Button.vue';
import SecondaryButton from '@/volt/SecondaryButton.vue';
import Dialog from '@/volt/Dialog.vue';
import type { BackupItem, BackupStatus } from '@/modules/backup/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    backups: BackupItem[];
    status: BackupStatus;
}>();

const toast = useToast();

const running = ref<boolean>(false);
const runConfirmOpen = ref<boolean>(false);
const deleteTarget = ref<BackupItem | null>(null);
const deleting = ref<boolean>(false);

function formatDate(iso: string | null): string {
    if (!iso) {
        return '—';
    }
    const date = new Date(iso);
    return Number.isNaN(date.getTime())
        ? '—'
        : new Intl.DateTimeFormat(undefined, {
              year: 'numeric',
              month: 'short',
              day: '2-digit',
              hour: '2-digit',
              minute: '2-digit',
          }).format(date);
}

function downloadBackup(item: BackupItem): void {
    window.location.href = `/backups/${encodeURIComponent(item.id)}/download`;
}

function confirmRun(): void {
    running.value = true;
    router.post('/backups/run', {}, {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Backup queued', life: 4000 }),
        onError: () => toast.add({ severity: 'error', summary: 'Could not queue the backup', life: 5000 }),
        onFinish: () => {
            running.value = false;
            runConfirmOpen.value = false;
        },
    });
}

function confirmDelete(): void {
    if (!deleteTarget.value) {
        return;
    }
    deleting.value = true;
    router.delete(`/backups/${encodeURIComponent(deleteTarget.value.id)}`, {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Backup deleted', life: 4000 }),
        onError: () => toast.add({ severity: 'error', summary: 'Could not delete the backup', life: 5000 }),
        onFinish: () => {
            deleting.value = false;
            deleteTarget.value = null;
        },
    });
}
</script>

<template>
    <Head title="Backups" />

    <AppHeader title="Backups" subtitle="Database & application archives (spatie/laravel-backup)" />

    <PermissionGuard permission="VIEW_ANY_BACKUPS">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to view backups.</p>
            </div>
        </template>

        <div class="page">
            <!-- ══ Status ══ -->
            <section class="stats">
                <div class="stat">
                    <span class="stat__label">Health</span>
                    <span class="chip" :class="props.status.healthy ? 'chip--ok' : 'chip--bad'">
                        <i :class="props.status.healthy ? 'pi pi-check-circle' : 'pi pi-exclamation-triangle'" aria-hidden="true" />
                        {{ props.status.healthy ? 'Healthy' : 'Attention needed' }}
                    </span>
                </div>
                <div class="stat">
                    <span class="stat__label">Backups</span>
                    <span class="stat__value">{{ props.status.backup_count }}</span>
                </div>
                <div class="stat">
                    <span class="stat__label">Newest</span>
                    <span class="stat__value">{{ formatDate(props.status.newest_at) }}</span>
                </div>
                <div class="stat">
                    <span class="stat__label">Storage used</span>
                    <span class="stat__value">{{ props.status.used_storage_human }}</span>
                </div>
                <div class="stat">
                    <span class="stat__label">Disk</span>
                    <span class="stat__value">{{ props.status.disk }}</span>
                </div>
            </section>

            <!-- ══ Toolbar ══ -->
            <div class="toolbar">
                <p class="counter">
                    {{ props.backups.length }} {{ props.backups.length === 1 ? 'archive' : 'archives' }} on
                    <strong>{{ props.status.backup_name }}</strong>
                </p>
                <PermissionGuard permission="CREATE_BACKUPS">
                    <Button
                        icon="pi pi-play"
                        label="Run backup now"
                        :loading="running"
                        @click="runConfirmOpen = true"
                    />
                </PermissionGuard>
            </div>

            <!-- ══ Table ══ -->
            <DataTable :value="props.backups" data-key="id" paginator :rows="10">
                <template #empty>
                    <div class="table-empty">
                        <i class="pi pi-database" aria-hidden="true" />
                        <span>No backups yet. Run one now or wait for the nightly job.</span>
                    </div>
                </template>

                <Column field="date" header="Created">
                    <template #body="{ data }">
                        <span class="mono">{{ formatDate((data as BackupItem).date) }}</span>
                    </template>
                </Column>

                <Column field="size_human" header="Size">
                    <template #body="{ data }">{{ (data as BackupItem).size_human }}</template>
                </Column>

                <Column field="id" header="File">
                    <template #body="{ data }">
                        <span class="muted">{{ (data as BackupItem).id }}</span>
                    </template>
                </Column>

                <Column header="Actions">
                    <template #body="{ data }">
                        <div class="actions">
                            <PermissionGuard permission="DOWNLOAD_BACKUPS">
                                <button
                                    type="button"
                                    class="action-btn"
                                    aria-label="Download backup"
                                    title="Download"
                                    v-tooltip.top="'Download'"
                                    @click="downloadBackup(data as BackupItem)"
                                >
                                    <i class="pi pi-download" aria-hidden="true" />
                                </button>
                            </PermissionGuard>
                            <PermissionGuard permission="DELETE_BACKUPS">
                                <button
                                    type="button"
                                    class="action-btn action-btn--danger"
                                    aria-label="Delete backup"
                                    title="Delete"
                                    v-tooltip.top="'Delete'"
                                    @click="deleteTarget = data as BackupItem"
                                >
                                    <i class="pi pi-trash" aria-hidden="true" />
                                </button>
                            </PermissionGuard>
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- ══ Run confirm ══ -->
        <Dialog v-model:visible="runConfirmOpen" modal header="Run a backup now?">
            <p class="dialog-text">
                This queues a full backup (database + files). It runs in the background and appears in the list once finished.
            </p>
            <template #footer>
                <SecondaryButton label="Cancel" @click="runConfirmOpen = false" />
                <Button label="Run backup" icon="pi pi-play" :loading="running" autofocus @click="confirmRun" />
            </template>
        </Dialog>

        <!-- ══ Delete confirm ══ -->
        <Dialog
            :visible="deleteTarget !== null"
            modal
            header="Delete this backup?"
            @update:visible="(v: boolean) => { if (!v) deleteTarget = null; }"
        >
            <p class="dialog-text">
                <strong>{{ deleteTarget?.id }}</strong> will be permanently removed from
                <strong>{{ props.status.disk }}</strong>. This cannot be undone.
            </p>
            <template #footer>
                <SecondaryButton label="Cancel" @click="deleteTarget = null" />
                <Button label="Delete backup" icon="pi pi-trash" severity="danger" :loading="deleting" autofocus @click="confirmDelete" />
            </template>
        </Dialog>
    </PermissionGuard>
</template>

<style scoped>
.page {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: var(--space-4);
}

.stat {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    padding: var(--space-4) var(--space-5);
    background: color-mix(in srgb, var(--bg-surface) 60%, transparent);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-xl);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

.stat__label {
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    text-transform: uppercase;
    letter-spacing: 1.2px;
    color: var(--text-muted);
}

.stat__value {
    font-size: var(--text-lg);
    font-weight: var(--font-bold);
    color: var(--text-primary);
}

.chip {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    align-self: flex-start;
    padding: 2px var(--space-3);
    border-radius: var(--radius-md);
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
}

.chip--ok {
    background: color-mix(in srgb, var(--accent-success) 16%, transparent);
    color: var(--accent-success);
}

.chip--bad {
    background: color-mix(in srgb, var(--accent-error) 16%, transparent);
    color: var(--accent-error);
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

.mono {
    font-family: var(--font-mono, monospace);
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

.muted {
    color: var(--text-muted);
    font-size: var(--text-sm);
}

.actions {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border: none;
    border-radius: var(--radius-md);
    background: transparent;
    color: var(--text-secondary);
    cursor: pointer;
    transition: background var(--transition), color var(--transition);
}

.action-btn:hover {
    background: color-mix(in srgb, var(--accent-primary) 12%, transparent);
    color: var(--accent-primary);
}

.action-btn--danger:hover {
    background: color-mix(in srgb, var(--accent-error) 12%, transparent);
    color: var(--accent-error);
}

.action-btn:focus-visible {
    outline: 2px solid var(--accent-primary);
    outline-offset: 2px;
}

.dialog-text {
    margin: 0;
    max-width: 42ch;
    font-size: var(--text-sm);
    color: var(--text-secondary);
    line-height: 1.6;
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
