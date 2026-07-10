<script setup lang="ts">
/**
 * Grouped permission picker for the role form. Takes the flat active-permission
 * catalogue (`available`) and binds the selected permission NAMES via v-model —
 * the exact set the backend syncs (an empty set revokes every grant).
 *
 * Volt ships no MultiSelect, and a role can hold dozens of grants across modules,
 * so a searchable, module-grouped checkbox tree reads far better than a tag
 * dropdown here. Fully keyboard-accessible and token-driven (no hardcoded colour).
 */
import { computed, ref } from 'vue';
import { groupPermissions } from '@/modules/authorization/helpers/groupPermissions';

const selected = defineModel<string[]>({ default: () => [] });

const props = withDefaults(
    defineProps<{
        available: string[];
        disabled?: boolean;
    }>(),
    { disabled: false },
);

const search = ref<string>('');

const groups = computed(() => groupPermissions(props.available));

const visibleGroups = computed(() => {
    const term = search.value.trim().toLowerCase();
    const source = term
        ? groups.value
            .map((group) => ({
                ...group,
                entries: group.entries.filter(
                    (entry) =>
                        entry.name.toLowerCase().includes(term) ||
                        entry.action.toLowerCase().includes(term),
                ),
            }))
            .filter((group) => group.entries.length > 0)
        : groups.value;

    // Attach the flat name list once so the template never re-maps per render.
    return source.map((group) => ({
        ...group,
        names: group.entries.map((entry) => entry.name),
    }));
});

const selectedSet = computed<Set<string>>(() => new Set(selected.value));

const totalCount = computed<number>(() => props.available.length);
const selectedCount = computed<number>(() => selected.value.length);

/** When a filter is active, bulk actions target only the visible matches. */
const searchActive = computed<boolean>(() => search.value.trim().length > 0);

const scopeNames = computed<string[]>(() =>
    searchActive.value ? visibleGroups.value.flatMap((group) => group.names) : props.available,
);

const allScopeSelected = computed<boolean>(
    () => scopeNames.value.length > 0 && scopeNames.value.every((name) => selectedSet.value.has(name)),
);
const anyScopeSelected = computed<boolean>(
    () => scopeNames.value.some((name) => selectedSet.value.has(name)),
);

const canSelectAll = computed<boolean>(
    () => !props.disabled && scopeNames.value.length > 0 && !allScopeSelected.value,
);
const canClear = computed<boolean>(() => !props.disabled && anyScopeSelected.value);

function isChecked(name: string): boolean {
    return selectedSet.value.has(name);
}

function toggle(name: string): void {
    if (props.disabled) {
        return;
    }
    selected.value = isChecked(name)
        ? selected.value.filter((n) => n !== name)
        : [...selected.value, name];
}

function groupSelectedCount(names: string[]): number {
    return names.filter((n) => selectedSet.value.has(n)).length;
}

function groupState(names: string[]): 'all' | 'some' | 'none' {
    const chosen = groupSelectedCount(names);
    if (chosen === 0) {
        return 'none';
    }
    return chosen === names.length ? 'all' : 'some';
}

function toggleGroup(names: string[]): void {
    if (props.disabled) {
        return;
    }
    const next = new Set(selected.value);
    if (groupState(names) === 'all') {
        names.forEach((n) => next.delete(n));
    } else {
        names.forEach((n) => next.add(n));
    }
    selected.value = [...next];
}

function selectAll(): void {
    if (!canSelectAll.value) {
        return;
    }
    const next = new Set(selected.value);
    scopeNames.value.forEach((name) => next.add(name));
    selected.value = [...next];
}

function clearAll(): void {
    if (!canClear.value) {
        return;
    }
    if (!searchActive.value) {
        selected.value = [];
        return;
    }
    const remove = new Set(scopeNames.value);
    selected.value = selected.value.filter((name) => !remove.has(name));
}
</script>

<template>
    <div class="picker" :class="{ 'picker--disabled': disabled }">
        <div class="picker__head">
            <div class="picker__search">
                <i class="pi pi-search" aria-hidden="true" />
                <input
                    v-model="search"
                    type="text"
                    class="picker__search-input"
                    placeholder="Filter permissions…"
                    aria-label="Filter permissions"
                    :disabled="disabled"
                />
            </div>
            <div class="picker__bulk" role="group" aria-label="Bulk permission selection">
                <button
                    type="button"
                    class="picker__chip"
                    :disabled="!canSelectAll"
                    @click="selectAll"
                >
                    <i class="pi pi-check-square" aria-hidden="true" />
                    <span>{{ searchActive ? 'Select filtered' : 'Select all' }}</span>
                </button>
                <button
                    type="button"
                    class="picker__chip"
                    :disabled="!canClear"
                    @click="clearAll"
                >
                    <i class="pi pi-eraser" aria-hidden="true" />
                    <span>{{ searchActive ? 'Clear filtered' : 'Clear' }}</span>
                </button>
            </div>
        </div>

        <p class="picker__counter">
            <span class="picker__counter-num">{{ selectedCount }}</span> of {{ totalCount }} selected
        </p>

        <div class="picker__groups">
            <fieldset
                v-for="group in visibleGroups"
                :key="group.module"
                class="group"
            >
                <legend class="group__legend">
                    <label
                        class="group__toggle"
                        :class="`group__toggle--${groupState(group.names)}`"
                    >
                        <input
                            type="checkbox"
                            class="checkbox__input"
                            :checked="groupState(group.names) === 'all'"
                            :indeterminate.prop="groupState(group.names) === 'some'"
                            :disabled="disabled"
                            :aria-label="`Toggle all ${group.label} permissions`"
                            @change="toggleGroup(group.names)"
                        />
                        <span class="checkbox__box" aria-hidden="true">
                            <i class="pi pi-check" />
                        </span>
                        <span class="group__title">{{ group.label }}</span>
                        <span class="group__count">{{ groupSelectedCount(group.names) }}/{{ group.names.length }}</span>
                    </label>
                </legend>

                <div class="group__grid">
                    <label
                        v-for="entry in group.entries"
                        :key="entry.name"
                        class="checkbox"
                    >
                        <input
                            type="checkbox"
                            class="checkbox__input"
                            :checked="isChecked(entry.name)"
                            :disabled="disabled"
                            @change="toggle(entry.name)"
                        />
                        <span class="checkbox__box" aria-hidden="true">
                            <i class="pi pi-check" />
                        </span>
                        <span class="checkbox__label">{{ entry.action }}</span>
                    </label>
                </div>
            </fieldset>

            <p v-if="visibleGroups.length === 0" class="picker__empty">
                No permissions match “{{ search }}”.
            </p>
        </div>
    </div>
</template>

<style scoped>
.picker {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
}

.picker--disabled {
    opacity: 0.7;
}

.picker__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-2) var(--space-3);
    flex-wrap: wrap;
}

.picker__search {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    flex: 1 1 12rem;
    min-width: 0;
    height: 36px;
    padding: 0 var(--space-3);
    background: var(--input-bg);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    transition: border-color var(--transition), box-shadow var(--transition);
}

.picker__search:focus-within {
    border-color: color-mix(in srgb, var(--accent-primary) 55%, transparent);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent-primary) 14%, transparent);
}

.picker__search .pi {
    font-size: 0.85rem;
    color: var(--text-muted);
}

.picker__search-input {
    flex: 1;
    min-width: 0;
    background: transparent;
    border: none;
    outline: none;
    color: var(--text-primary);
    font-size: var(--text-sm);
}

.picker__search-input::placeholder {
    color: var(--text-muted);
}

.picker__bulk {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    flex-wrap: wrap;
}

.picker__chip {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    height: 32px;
    padding: 0 var(--space-3);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    background: var(--input-bg);
    color: var(--text-secondary);
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
    white-space: nowrap;
    cursor: pointer;
    transition: background var(--transition), border-color var(--transition), color var(--transition);
}

.picker__chip .pi {
    font-size: 0.75rem;
}

.picker__chip:hover:not(:disabled) {
    border-color: color-mix(in srgb, var(--accent-primary) 45%, transparent);
    background: color-mix(in srgb, var(--accent-primary) 8%, transparent);
    color: var(--accent-primary);
}

.picker__chip:focus-visible {
    outline: 2px solid var(--accent-primary);
    outline-offset: 2px;
}

.picker__chip:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.picker__counter {
    margin: 0;
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.picker__counter-num {
    font-weight: var(--font-semibold);
    color: var(--text-secondary);
}

.picker__groups {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    max-height: min(22rem, 50vh);
    overflow-y: auto;
    padding-right: var(--space-1);
    overscroll-behavior: contain;
}

.group {
    margin: 0;
    padding: var(--space-3) var(--space-4);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--bg-elevated) 40%, transparent);
}

.group__legend {
    padding: 0;
    margin-bottom: var(--space-3);
}

.group__toggle {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-1) var(--space-3) var(--space-1) var(--space-2);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-md);
    background: var(--input-bg);
    cursor: pointer;
    transition: background var(--transition), border-color var(--transition);
}

.group__toggle:hover {
    border-color: color-mix(in srgb, var(--accent-primary) 40%, transparent);
    background: color-mix(in srgb, var(--accent-primary) 6%, transparent);
}

.group__toggle--all {
    border-color: color-mix(in srgb, var(--accent-primary) 45%, transparent);
    background: color-mix(in srgb, var(--accent-primary) 10%, transparent);
}

.group__title {
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
}

.group__count {
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
    color: var(--text-muted);
    font-variant-numeric: tabular-nums;
}

.group__toggle--all .group__count,
.group__toggle--some .group__count {
    color: var(--accent-primary);
}

.group__grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));
    gap: var(--space-2) var(--space-4);
}

.checkbox {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    cursor: pointer;
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

.checkbox__input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.checkbox__box {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    flex-shrink: 0;
    border: 1px solid var(--border-default);
    border-radius: var(--radius-sm);
    background: var(--input-bg);
    color: transparent;
    transition: background var(--transition), border-color var(--transition), color var(--transition);
}

.checkbox__box .pi {
    font-size: 0.65rem;
}

.checkbox__input:checked + .checkbox__box {
    background: var(--accent-primary);
    border-color: var(--accent-primary);
    color: var(--on-accent, #fff);
}

.checkbox__input:indeterminate + .checkbox__box {
    background: color-mix(in srgb, var(--accent-primary) 40%, transparent);
    border-color: var(--accent-primary);
    color: var(--on-accent, #fff);
}

.checkbox__input:focus-visible + .checkbox__box {
    outline: 2px solid var(--accent-primary);
    outline-offset: 2px;
}

.checkbox__input:disabled + .checkbox__box {
    opacity: 0.5;
}

.picker__empty {
    margin: 0;
    padding: var(--space-4);
    text-align: center;
    font-size: var(--text-sm);
    color: var(--text-muted);
}
</style>
