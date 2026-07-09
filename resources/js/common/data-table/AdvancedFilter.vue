<script setup lang="ts">
/**
 * Generic, domain-agnostic list toolbar: debounced search + collapsible advanced
 * panel (configurable select / date-range / text / trash fields) + export and
 * create actions. Emits a flat FilterCriteria the parent maps into its query
 * params. Ported from the GUIDE `app-advanced-filter`.
 *
 * Layer: common/ — imports volt/ only, never modules/ or Pages/.
 */
import { computed, onBeforeUnmount, ref } from 'vue';
import Button from '@/volt/Button.vue';
import Select from '@/volt/Select.vue';
import InputText from '@/volt/InputText.vue';
import DatePicker from '@/volt/DatePicker.vue';
import GradientButton from '@/common/form/GradientButton.vue';

export interface FilterOption {
    label: string;
    value: string;
}

export interface FilterField {
    key: string;
    label: string;
    type: 'select' | 'date-range' | 'text' | 'trash';
    options?: FilterOption[];
    placeholder?: string;
}

export interface FilterCriteria {
    search: string;
    [key: string]: unknown;
}

const props = withDefaults(
    defineProps<{
        searchPlaceholder?: string;
        fields?: FilterField[];
        showExportPdf?: boolean;
        showExportExcel?: boolean;
        showExportCsv?: boolean;
        showCreate?: boolean;
        createLabel?: string;
    }>(),
    {
        searchPlaceholder: 'Search…',
        fields: () => [],
        showExportPdf: true,
        showExportExcel: true,
        showExportCsv: false,
        showCreate: true,
        createLabel: 'New',
    },
);

const emit = defineEmits<{
    filtersChange: [criteria: FilterCriteria];
    create: [];
    exportPdf: [];
    exportExcel: [];
    exportCsv: [];
}>();

const TRASH_OPTIONS: FilterOption[] = [
    { label: 'Active', value: 'active' },
    { label: 'Deleted', value: 'deleted' },
    { label: 'All', value: 'all' },
];

/** Upper bound for date-range fields — future dates never apply to created/updated filters. */
const maxDate = new Date();

const search = ref<string>('');
const advancedOpen = ref<boolean>(false);
const filterValues = ref<Record<string, unknown>>({});

const activeCount = computed<number>(() => {
    let count = 0;
    for (const field of props.fields) {
        const val = filterValues.value[field.key];
        if (val === undefined || val === null) {
            continue;
        }
        if (Array.isArray(val) && val.length === 0) {
            continue;
        }
        if (typeof val === 'string' && val.trim() === '') {
            continue;
        }
        count++;
    }
    return count;
});

let debounceHandle: ReturnType<typeof setTimeout> | undefined;
onBeforeUnmount(() => clearTimeout(debounceHandle));

function emitCriteria(): void {
    emit('filtersChange', { search: search.value.trim(), ...filterValues.value });
}

function onSearchInput(value: string): void {
    search.value = value;
    clearTimeout(debounceHandle);
    debounceHandle = setTimeout(emitCriteria, 300);
}

function setFilterValue(key: string, value: unknown): void {
    filterValues.value = { ...filterValues.value, [key]: value };
}

function onFieldChange(key: string, value: unknown): void {
    setFilterValue(key, value);
    emitCriteria();
}

function getFilterValue(key: string): unknown {
    return filterValues.value[key];
}

function toggleAdvanced(): void {
    advancedOpen.value = !advancedOpen.value;
}

function apply(): void {
    emitCriteria();
    advancedOpen.value = false;
}

function clear(): void {
    search.value = '';
    filterValues.value = {};
    emitCriteria();
}
</script>

<template>
    <div class="filter-bar">
        <div class="filter-bar__left">
            <div class="search-box">
                <i class="pi pi-search search-box__icon" aria-hidden="true" />
                <input
                    class="search-box__input"
                    type="text"
                    :placeholder="searchPlaceholder"
                    :value="search"
                    aria-label="Search"
                    @input="onSearchInput(($event.target as HTMLInputElement).value)"
                />
                <button
                    v-if="search"
                    class="search-box__clear"
                    type="button"
                    aria-label="Clear search"
                    @click="onSearchInput('')"
                >
                    <i class="pi pi-times" aria-hidden="true" />
                </button>
            </div>

            <Button
                type="button"
                icon="pi pi-filter"
                :label="`Filters${activeCount > 0 ? ` (${activeCount})` : ''}`"
                :outlined="!advancedOpen"
                severity="secondary"
                aria-label="Toggle advanced filters"
                :aria-expanded="advancedOpen"
                @click="toggleAdvanced"
            />
        </div>

        <div class="filter-bar__actions">
            <Button
                v-if="showExportPdf"
                type="button"
                icon="pi pi-file-pdf"
                label="PDF"
                outlined
                severity="secondary"
                @click="emit('exportPdf')"
            />
            <Button
                v-if="showExportExcel"
                type="button"
                icon="pi pi-file-excel"
                label="Excel"
                outlined
                severity="secondary"
                @click="emit('exportExcel')"
            />
            <Button
                v-if="showExportCsv"
                type="button"
                icon="pi pi-file"
                label="CSV"
                outlined
                severity="secondary"
                @click="emit('exportCsv')"
            />
            <GradientButton
                v-if="showCreate"
                type="button"
                icon="pi pi-plus"
                :label="createLabel"
                @click="emit('create')"
            />
        </div>

        <Transition name="panel">
            <div v-if="advancedOpen" class="filter-panel">
                <div v-for="field in fields" :key="field.key" class="filter-field">
                    <label :for="`filter-${field.key}`">{{ field.label }}</label>

                    <DatePicker
                        v-if="field.type === 'date-range'"
                        :input-id="`filter-${field.key}`"
                        :model-value="(getFilterValue(field.key) as Date[] | null | undefined)"
                        selection-mode="range"
                        :max-date="maxDate"
                        :manual-input="false"
                        :placeholder="field.placeholder ?? 'Start — End'"
                        fluid
                        @update:model-value="(v) => onFieldChange(field.key, v)"
                    />

                    <Select
                        v-else-if="field.type === 'select' || field.type === 'trash'"
                        :input-id="`filter-${field.key}`"
                        :model-value="getFilterValue(field.key)"
                        :options="field.type === 'trash' ? TRASH_OPTIONS : (field.options ?? [])"
                        option-label="label"
                        option-value="value"
                        :placeholder="field.placeholder ?? 'All'"
                        show-clear
                        fluid
                        @update:model-value="(v) => onFieldChange(field.key, v)"
                    />

                    <InputText
                        v-else
                        :id="`filter-${field.key}`"
                        :model-value="(getFilterValue(field.key) as string | undefined)"
                        :placeholder="field.placeholder ?? ''"
                        fluid
                        @update:model-value="(v) => setFilterValue(field.key, v)"
                    />
                </div>

                <div class="filter-panel__actions">
                    <Button type="button" label="Clear" text severity="secondary" @click="clear" />
                    <Button type="button" label="Apply" @click="apply" />
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.filter-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: color-mix(in srgb, var(--bg-surface) 55%, transparent);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-lg);
}

.filter-bar__left {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
    min-width: 240px;
}

.filter-bar__actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-left: auto;
}

.search-box {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
    max-width: 360px;
    height: 40px;
    padding: 0 0.75rem;
    background: var(--input-bg);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    transition: border-color var(--transition), box-shadow var(--transition);
}

.search-box:focus-within {
    border-color: color-mix(in srgb, var(--accent-primary) 55%, transparent);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent-primary) 14%, transparent);
}

.search-box__icon {
    font-size: 0.9rem;
    color: var(--text-muted);
    flex-shrink: 0;
}

.search-box__input {
    flex: 1;
    min-width: 0;
    height: 100%;
    background: transparent;
    border: none;
    outline: none;
    color: var(--text-primary);
    font-size: 0.875rem;
}

.search-box__input::placeholder {
    color: var(--text-muted);
}

.search-box__clear {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border: none;
    background: transparent;
    color: var(--text-muted);
    cursor: pointer;
    flex-shrink: 0;
}

.search-box__clear:hover {
    color: var(--text-primary);
}

.filter-panel {
    flex-basis: 100%;
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 1rem;
    margin-top: 0.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-default);
}

.filter-field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 220px;
}

.filter-field label {
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--text-secondary);
}

.filter-panel__actions {
    display: flex;
    gap: 0.5rem;
    margin-left: auto;
}

.panel-enter-active,
.panel-leave-active {
    transition: opacity var(--transition), transform var(--transition);
}

.panel-enter-from,
.panel-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}

@media (max-width: 768px) {
    .filter-bar__left {
        width: 100%;
        flex-basis: 100%;
    }

    .search-box {
        max-width: none;
    }

    .filter-bar__actions {
        width: 100%;
        margin-left: 0;
    }

    .filter-field {
        min-width: 100%;
    }

    .filter-panel__actions {
        width: 100%;
        margin-left: 0;
    }
}
</style>
