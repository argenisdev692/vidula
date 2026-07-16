<script setup lang="ts">
/**
 * Mixed-type attendee picker for the Meeting form — search across
 * users/leads/support-contacts (backed by `GET /meetings/attendees/search`,
 * which returns only `{ type, uuid, label }`, never a full record) and build
 * up a chip list. Reuses the already-committed Volt `Select` (server-driven
 * via its `@filter` event, debounced) instead of introducing a new
 * AutoComplete/MultiSelect primitive — no new volt/ component needed.
 *
 * Layer: common/ — imports volt/ + lib/http only, never modules/ or Pages/.
 */
import { computed, ref } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import Select from '@/volt/Select.vue';
import Tag from '@/volt/Tag.vue';
import { apiFetch } from '@/lib/http';

export interface AttendeeOption {
    type: 'user' | 'lead' | 'contact';
    uuid: string;
    label: string;
}

const TYPE_LABEL: Record<AttendeeOption['type'], string> = {
    user: 'User',
    lead: 'Lead',
    contact: 'Contact',
};

const TYPE_SEVERITY: Record<AttendeeOption['type'], 'info' | 'primary' | 'secondary'> = {
    user: 'info',
    lead: 'primary',
    contact: 'secondary',
};

const model = defineModel<AttendeeOption[]>({ default: () => [] });

withDefaults(defineProps<{ error?: string }>(), { error: undefined });

const options = ref<AttendeeOption[]>([]);
const loading = ref<boolean>(false);
const pending = ref<AttendeeOption | null>(null);

const optionKey = (option: AttendeeOption): string => `${option.type}:${option.uuid}`;

const search = useDebounceFn(async (term: string): Promise<void> => {
    const trimmed = term.trim();
    if (trimmed.length < 2) {
        options.value = [];
        return;
    }

    loading.value = true;
    try {
        const response = await apiFetch<{ data: AttendeeOption[] }>(
            'GET',
            `/meetings/attendees/search?q=${encodeURIComponent(trimmed)}`,
        );
        const chosen = new Set(model.value.map(optionKey));
        options.value = response.data.filter((option) => !chosen.has(optionKey(option)));
    } catch {
        options.value = [];
    } finally {
        loading.value = false;
    }
}, 300);

function onFilter(event: { value: string }): void {
    void search(event.value);
}

function onSelect(): void {
    if (!pending.value) {
        return;
    }
    const chosen = pending.value;
    if (!model.value.some((attendee) => optionKey(attendee) === optionKey(chosen))) {
        model.value = [...model.value, chosen];
    }
    pending.value = null;
    options.value = [];
}

function remove(target: AttendeeOption): void {
    model.value = model.value.filter((attendee) => optionKey(attendee) !== optionKey(target));
}

const hasAttendees = computed<boolean>(() => model.value.length > 0);
</script>

<template>
    <div class="attendee-picker">
        <Select
            v-model="pending"
            :options="options"
            option-label="label"
            filter
            :loading="loading"
            placeholder="Search users, leads or support contacts…"
            :invalid="!!error"
            fluid
            @filter="onFilter"
            @update:model-value="onSelect"
        >
            <template #option="{ option }">
                <span class="attendee-picker__option">
                    <Tag :value="TYPE_LABEL[(option as AttendeeOption).type]" :severity="TYPE_SEVERITY[(option as AttendeeOption).type]" />
                    <span>{{ (option as AttendeeOption).label }}</span>
                </span>
            </template>
        </Select>

        <p v-if="error" class="attendee-picker__error" role="alert">{{ error }}</p>

        <div v-if="hasAttendees" class="attendee-picker__chips">
            <span v-for="attendee in model" :key="optionKey(attendee)" class="attendee-picker__chip">
                <Tag :value="TYPE_LABEL[attendee.type]" :severity="TYPE_SEVERITY[attendee.type]" />
                <span class="attendee-picker__chip-label">{{ attendee.label }}</span>
                <button
                    type="button"
                    class="attendee-picker__chip-remove"
                    :aria-label="`Remove ${attendee.label}`"
                    @click="remove(attendee)"
                >
                    <i class="pi pi-times" aria-hidden="true" />
                </button>
            </span>
        </div>
        <p v-else class="attendee-picker__empty">No attendees added yet.</p>
    </div>
</template>

<style scoped>
.attendee-picker {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
}

.attendee-picker__option {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
}

.attendee-picker__error {
    margin: 0;
    font-size: var(--text-xs);
    color: var(--accent-error);
}

.attendee-picker__empty {
    margin: 0;
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.attendee-picker__chips {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
}

.attendee-picker__chip {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-1) var(--space-2);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--bg-elevated) 40%, transparent);
    font-size: var(--text-sm);
    color: var(--text-primary);
}

.attendee-picker__chip-label {
    max-width: 16rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.attendee-picker__chip-remove {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    border: none;
    background: transparent;
    color: var(--text-muted);
    cursor: pointer;
}

.attendee-picker__chip-remove:hover {
    color: var(--accent-error);
}
</style>
