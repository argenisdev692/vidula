<script setup lang="ts">
/**
 * The chrome every server-side CRUD index screen shared verbatim: the page
 * `<Head>`, the AppHeader, the VIEW_ANY PermissionGuard + lock fallback, the
 * AdvancedFilter, and the record-count / bulk-action toolbar — plus the scoped
 * `.page` / `.toolbar` / `.bulk-bar` styles that were byte-identical across 7
 * pages. The table and the dialogs (form + confirmations) come in via slots so
 * each page supplies only its own content and wiring.
 *
 * Layer: common/ — imports Pages/layouts + modules/{app,auth} chrome + volt/.
 */
import { Head } from '@inertiajs/vue3';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import AdvancedFilter, { type FilterCriteria, type FilterField } from './AdvancedFilter.vue';
import Button from '@/volt/Button.vue';

withDefaults(
    defineProps<{
        title: string;
        subtitle: string;
        permission: string;
        fallbackText: string;
        searchPlaceholder: string;
        fields: FilterField[];
        canExport: boolean;
        canCreate: boolean;
        createLabel: string;
        recordLabel: string;
        selectionCount: number;
        canBulkAct: boolean;
        isSuspendedView: boolean;
        /** Blocks the bulk button (e.g. a protected system row is selected). */
        bulkDisabled?: boolean;
    }>(),
    { bulkDisabled: false },
);

const emit = defineEmits<{
    filtersChange: [criteria: FilterCriteria];
    create: [];
    exportPdf: [];
    exportExcel: [];
    exportCsv: [];
    bulk: [];
}>();
</script>

<template>
    <Head :title="title" />

    <AppHeader :title="title" :subtitle="subtitle" />

    <PermissionGuard :permission="permission">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>{{ fallbackText }}</p>
            </div>
        </template>

        <div class="page">
            <AdvancedFilter
                :search-placeholder="searchPlaceholder"
                :fields="fields"
                :show-export-pdf="canExport"
                :show-export-excel="canExport"
                :show-export-csv="canExport"
                :show-create="canCreate"
                :create-label="createLabel"
                @filters-change="emit('filtersChange', $event)"
                @create="emit('create')"
                @export-pdf="emit('exportPdf')"
                @export-excel="emit('exportExcel')"
                @export-csv="emit('exportCsv')"
            />

            <div class="toolbar">
                <p class="counter">{{ recordLabel }}</p>

                <Transition name="fade">
                    <div v-if="selectionCount > 0 && canBulkAct" class="bulk-bar">
                        <span class="bulk-bar__count">{{ selectionCount }} selected</span>
                        <slot name="bulk-note" />
                        <Button
                            class="bulk-bar__btn"
                            :class="isSuspendedView ? 'bulk-bar__btn--restore' : 'bulk-bar__btn--suspend'"
                            size="small"
                            :label="isSuspendedView ? 'Restore selected' : 'Suspend selected'"
                            :icon="isSuspendedView ? 'pi pi-check-circle' : 'pi pi-trash'"
                            :disabled="bulkDisabled"
                            @click="emit('bulk')"
                        />
                    </div>
                </Transition>
            </div>

            <slot name="table" />
        </div>
    </PermissionGuard>

    <slot name="dialogs" />
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
    flex-wrap: wrap;
}

.bulk-bar__count {
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--text-secondary);
}

/* Filled gradient bulk action, with a lift + glow on hover. Compound selector
   keeps specificity above the volt Button's Tailwind pass-through classes. */
.bulk-bar .bulk-bar__btn {
    border: none;
    color: #fff;
    background: var(--bulk-grad);
    box-shadow: 0 1px 2px color-mix(in srgb, var(--bulk-accent) 30%, transparent);
    transition:
        transform var(--transition),
        box-shadow var(--transition),
        filter var(--transition);
}

.bulk-bar .bulk-bar__btn :deep(.p-button-label),
.bulk-bar .bulk-bar__btn :deep(.p-button-icon) {
    color: #fff;
}

.bulk-bar .bulk-bar__btn:not(:disabled):hover {
    transform: translateY(-2px);
    filter: brightness(1.08);
    box-shadow: 0 6px 18px color-mix(in srgb, var(--bulk-accent) 45%, transparent);
}

.bulk-bar .bulk-bar__btn:not(:disabled):active {
    transform: translateY(0);
    filter: brightness(0.96);
    box-shadow: 0 1px 2px color-mix(in srgb, var(--bulk-accent) 30%, transparent);
}

.bulk-bar__btn--suspend {
    --bulk-accent: var(--accent-error);
    --bulk-grad: linear-gradient(
        135deg,
        color-mix(in srgb, var(--accent-error) 80%, #000),
        color-mix(in srgb, var(--accent-error) 58%, #000)
    );
}

.bulk-bar__btn--restore {
    --bulk-accent: var(--accent-success);
    --bulk-grad: linear-gradient(
        135deg,
        color-mix(in srgb, var(--accent-success) 80%, #000),
        color-mix(in srgb, var(--accent-success) 58%, #000)
    );
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
