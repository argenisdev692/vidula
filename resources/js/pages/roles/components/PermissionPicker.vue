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
    if (!term) {
        return groups.value;
    }
    return groups.value
        .map((group) => ({
            ...group,
            entries: group.entries.filter(
                (entry) =>
                    entry.name.toLowerCase().includes(term) || entry.action.toLowerCase().includes(term),
            ),
        }))
        .filter((group) => group.entries.length > 0);
});

const selectedSet = computed<Set<string>>(() => new Set(selected.value));

const totalCount = computed<number>(() => props.available.length);
const selectedCount = computed<number>(() => selected.value.length);

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

function groupState(names: string[]): 'all' | 'some' | 'none' {
    const chosen = names.filter((n) => selectedSet.value.has(n)).length;
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
    if (props.disabled) {
        return;
    }
    selected.value = [...props.available];
}

function clearAll(): void {
    if (props.disabled) {
        return;
    }
    selected.value = [];
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
            <div class="picker__bulk">
                <button type="button" class="picker__link" :disabled="disabled" @click="selectAll">
                    Select all
                </button>
                <span aria-hidden="true">·</span>
                <button type="button" class="picker__link" :disabled="disabled" @click="clearAll">
                    Clear
                </button>
            </div>
        </div>

        <p class="picker__counter">{{ selectedCount }} of {{ totalCount }} selected</p>

        <div class="picker__groups">
            <fieldset
                v-for="group in visibleGroups"
                :key="group.module"
                class="group"
            >
                <legend class="group__legend">
                    <label class="checkbox checkbox--group">
                        <input
                            type="checkbox"
                            class="checkbox__input"
                            :checked="groupState(group.entries.map((e) => e.name)) === 'all'"
                            :indeterminate.prop="groupState(group.entries.map((e) => e.name)) === 'some'"
                            :disabled="disabled"
                            :aria-label="`Toggle all ${group.label} permissions`"
                            @change="toggleGroup(group.entries.map((e) => e.name))"
                        />
                        <span class="checkbox__box" aria-hidden="true">
                            <i class="pi pi-check" />
                        </span>
                        <span class="group__title">{{ group.label }}</span>
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
    gap: var(--space-3);
    flex-wrap: wrap;
}

.picker__search {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    flex: 1;
    min-width: 12rem;
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
    color: var(--text-muted);
    font-size: var(--text-sm);
}

.picker__link {
    background: none;
    border: none;
    padding: 0;
    color: var(--accent-primary);
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    cursor: pointer;
}

.picker__link:disabled {
    color: var(--text-disabled);
    cursor: not-allowed;
}

.picker__link:hover:not(:disabled) {
    text-decoration: underline;
}

.picker__counter {
    margin: 0;
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.picker__groups {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    max-height: 22rem;
    overflow-y: auto;
    padding-right: var(--space-1);
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
    margin-bottom: var(--space-2);
}

.group__title {
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
}

.group__grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(9rem, 1fr));
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

.checkbox--group .checkbox__label,
.checkbox--group {
    font-weight: var(--font-semibold);
}

.picker__empty {
    margin: 0;
    padding: var(--space-4);
    text-align: center;
    font-size: var(--text-sm);
    color: var(--text-muted);
}
</style>
