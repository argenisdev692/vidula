<script setup lang="ts">
/**
 * Mixed-type attendee picker for Meeting forms — remote search across
 * users/leads/support-contacts (`GET /meetings/attendees/search`) plus an
 * "Add new lead" panel when the person does not exist yet
 * (`POST /meetings/attendees/quick-lead`). Uses a custom search box + result
 * list (not PrimeVue Select) so server matches by email are never wiped by
 * client-side label filtering.
 *
 * Layer: common/ — imports volt/ + common/form + lib/http only.
 */
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useDebounceFn, onClickOutside } from '@vueuse/core';
import Tag from '@/volt/Tag.vue';
import InputText from '@/volt/InputText.vue';
import Button from '@/volt/Button.vue';
import TextField from '@/common/form/TextField.vue';
import PhoneField from '@/common/form/PhoneField.vue';
import { apiFetch, HttpError } from '@/lib/http';

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

const rootRef = ref<HTMLElement | null>(null);
const query = ref<string>('');
const options = ref<AttendeeOption[]>([]);
const loading = ref<boolean>(false);
const open = ref<boolean>(false);
const showCreate = ref<boolean>(false);
const creating = ref<boolean>(false);
const createErrors = ref<Record<string, string>>({});

const newLead = ref({
    first_name: '',
    last_name: '',
    email: '',
    phone: null as string | null,
});

const NAME_MAX = 20;
const NAME_MIN = 3;

/** Letters only, no spaces — single Capitalized word (mirrors AppointmentForm). */
function toNameValue(value: string): string {
    const letters = value.replace(/[^\p{L}]/gu, '').slice(0, NAME_MAX);
    return letters ? letters.charAt(0).toUpperCase() + letters.slice(1).toLowerCase() : '';
}

const firstNameModel = computed<string>({
    get: () => newLead.value.first_name,
    set: (value) => {
        newLead.value.first_name = toNameValue(value);
    },
});

const lastNameModel = computed<string>({
    get: () => newLead.value.last_name,
    set: (value) => {
        newLead.value.last_name = toNameValue(value);
    },
});

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
        open.value = true;
    } catch {
        options.value = [];
    } finally {
        loading.value = false;
    }
}, 300);

function onQueryInput(value: string | undefined): void {
    query.value = value ?? '';
    showCreate.value = false;
    void search(query.value);
}

function pick(option: AttendeeOption): void {
    if (!model.value.some((attendee) => optionKey(attendee) === optionKey(option))) {
        model.value = [...model.value, option];
    }
    query.value = '';
    options.value = [];
    open.value = false;
    showCreate.value = false;
}

function remove(target: AttendeeOption): void {
    model.value = model.value.filter((attendee) => optionKey(attendee) !== optionKey(target));
}

function openCreatePanel(): void {
    showCreate.value = true;
    open.value = false;
    createErrors.value = {};

    const trimmed = query.value.trim();
    const looksLikeEmail = trimmed.includes('@');
    const parts = looksLikeEmail ? [] : trimmed.split(/\s+/).filter(Boolean);

    newLead.value = {
        first_name: parts[0] ? toNameValue(parts[0]) : '',
        last_name: parts[1] ? toNameValue(parts[1]) : '',
        email: looksLikeEmail ? trimmed.toLowerCase() : '',
        phone: null,
    };
}

async function submitNewLead(): Promise<void> {
    createErrors.value = {};
    const payload = {
        first_name: newLead.value.first_name.trim(),
        last_name: newLead.value.last_name.trim(),
        email: newLead.value.email.trim(),
        phone: newLead.value.phone?.trim() ?? '',
    };

    if (payload.first_name.length < NAME_MIN) {
        createErrors.value.first_name = `First name must be at least ${NAME_MIN} letters.`;
    }
    if (payload.last_name.length < NAME_MIN) {
        createErrors.value.last_name = `Last name must be at least ${NAME_MIN} letters.`;
    }
    if (!payload.email) {
        createErrors.value.email = 'Email is required.';
    }
    if (!payload.phone) {
        createErrors.value.phone = 'Phone is required.';
    }
    if (Object.keys(createErrors.value).length > 0) {
        return;
    }

    creating.value = true;
    try {
        const response = await apiFetch<{ data: AttendeeOption }>('POST', '/meetings/attendees/quick-lead', payload);
        pick(response.data);
        showCreate.value = false;
    } catch (error) {
        if (error instanceof HttpError && error.body && typeof error.body === 'object') {
            const body = error.body as { errors?: Record<string, string[]> };
            const errors = body.errors ?? {};
            for (const [key, messages] of Object.entries(errors)) {
                createErrors.value[key] = messages[0] ?? 'Invalid value.';
            }
        } else {
            createErrors.value.email = 'Could not create the lead. Try again.';
        }
    } finally {
        creating.value = false;
    }
}

const hasAttendees = computed<boolean>(() => model.value.length > 0);
const showEmptyHint = computed<boolean>(
    () => !loading.value && query.value.trim().length >= 2 && options.value.length === 0 && !showCreate.value,
);

onClickOutside(rootRef, () => {
    open.value = false;
});

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        open.value = false;
        showCreate.value = false;
    }
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <div ref="rootRef" class="attendee-picker">
        <div class="attendee-picker__search">
            <span class="attendee-picker__search-icon" aria-hidden="true">
                <i class="pi pi-search" />
            </span>
            <InputText
                :model-value="query"
                class="attendee-picker__input"
                placeholder="Search by name or email…"
                fluid
                :invalid="!!error"
                autocomplete="off"
                @update:model-value="onQueryInput"
                @focus="open = query.trim().length >= 2"
            />
            <i v-if="loading" class="pi pi-spin pi-spinner attendee-picker__spinner" aria-hidden="true" />
        </div>

        <ul v-if="open && options.length > 0" class="attendee-picker__results" role="listbox">
            <li
                v-for="option in options"
                :key="optionKey(option)"
                class="attendee-picker__result"
                role="option"
                @mousedown.prevent="pick(option)"
            >
                <Tag :value="TYPE_LABEL[option.type]" :severity="TYPE_SEVERITY[option.type]" />
                <span>{{ option.label }}</span>
            </li>
            <li class="attendee-picker__result attendee-picker__result--action" role="option">
                <button type="button" class="attendee-picker__link" @mousedown.prevent="openCreatePanel">
                    <i class="pi pi-user-plus" aria-hidden="true" />
                    Not listed? Add as new lead
                </button>
            </li>
        </ul>

        <div v-else-if="showEmptyHint" class="attendee-picker__empty-search">
            <div class="attendee-picker__empty-copy">
                <p class="attendee-picker__empty-title">No matching users, leads, or contacts</p>
                <p class="attendee-picker__empty-hint">Create a lead with name, email and phone, then add them here.</p>
            </div>
            <Button
                type="button"
                label="Add new lead"
                icon="pi pi-user-plus"
                size="small"
                @click="openCreatePanel"
            />
        </div>

        <p v-else-if="!showCreate" class="attendee-picker__always-link">
            <button type="button" class="attendee-picker__link" @click="openCreatePanel">
                <i class="pi pi-user-plus" aria-hidden="true" />
                Person not found? Add as new lead
            </button>
        </p>

        <div v-if="showCreate" class="attendee-picker__create">
            <header class="attendee-picker__create-head">
                <h4>Add new lead</h4>
                <p>Creates a lead record and adds them as an attendee.</p>
            </header>
            <div class="attendee-picker__create-grid">
                <TextField
                    v-model="firstNameModel"
                    name="quick_first_name"
                    label="First name"
                    required
                    :maxlength="NAME_MAX"
                    :error="createErrors.first_name"
                    :hint="`Letters only, no spaces — ${NAME_MIN} to ${NAME_MAX} characters.`"
                />
                <TextField
                    v-model="lastNameModel"
                    name="quick_last_name"
                    label="Last name"
                    required
                    :maxlength="NAME_MAX"
                    :error="createErrors.last_name"
                    :hint="`Letters only, no spaces — ${NAME_MIN} to ${NAME_MAX} characters.`"
                />
                <TextField
                    v-model="newLead.email"
                    name="quick_email"
                    label="Email"
                    type="email"
                    required
                    :error="createErrors.email"
                />
                <PhoneField
                    v-model="newLead.phone"
                    name="quick_phone"
                    label="Phone"
                    required
                    :error="createErrors.phone"
                />
            </div>
            <div class="attendee-picker__create-actions">
                <Button type="button" label="Cancel" text severity="secondary" :disabled="creating" @click="showCreate = false" />
                <Button type="button" label="Create & add" icon="pi pi-check" size="small" :loading="creating" @click="submitNewLead" />
            </div>
        </div>

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
    position: relative;
}

.attendee-picker__search {
    position: relative;
    display: flex;
    align-items: center;
}

.attendee-picker__search-icon {
    position: absolute;
    left: var(--space-3);
    z-index: 1;
    color: var(--text-muted);
    pointer-events: none;
}

.attendee-picker__input {
    padding-left: 2.25rem !important;
}

.attendee-picker__spinner {
    position: absolute;
    right: var(--space-3);
    color: var(--text-muted);
}

.attendee-picker__results {
    position: absolute;
    top: calc(var(--input-height, 40px) + var(--space-1));
    left: 0;
    right: 0;
    z-index: 20;
    margin: 0;
    padding: var(--space-1);
    list-style: none;
    max-height: 14rem;
    overflow: auto;
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    background: var(--bg-surface);
    box-shadow: 0 8px 24px color-mix(in srgb, var(--text-primary) 12%, transparent);
}

.attendee-picker__result {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-size: var(--text-sm);
    color: var(--text-primary);
}

.attendee-picker__result:hover {
    background: var(--bg-hover);
}

.attendee-picker__result--action {
    border-top: 1px solid var(--border-subtle);
    margin-top: var(--space-1);
    padding-top: var(--space-2);
}

.attendee-picker__link {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    padding: 0;
    border: none;
    background: transparent;
    color: var(--accent-primary);
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    cursor: pointer;
    text-decoration: underline;
    text-underline-offset: 2px;
}

.attendee-picker__link:hover {
    color: var(--accent-secondary, var(--accent-primary));
}

.attendee-picker__always-link {
    margin: 0;
}

.attendee-picker__empty-search {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
    padding: var(--space-4);
    border: 1px dashed var(--border-default);
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--accent-primary) 6%, var(--bg-surface));
}

.attendee-picker__empty-copy {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
    min-width: 0;
    flex: 1;
}

.attendee-picker__empty-title {
    margin: 0;
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--text-primary);
}

.attendee-picker__empty-hint {
    margin: 0;
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.attendee-picker__create {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    padding: var(--space-4);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--bg-elevated) 50%, transparent);
}

.attendee-picker__create-head h4 {
    margin: 0;
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
}

.attendee-picker__create-head p {
    margin: var(--space-1) 0 0;
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.attendee-picker__create-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-3);
}

.attendee-picker__create-actions {
    display: flex;
    justify-content: flex-end;
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

@media (max-width: 640px) {
    .attendee-picker__create-grid {
        grid-template-columns: 1fr;
    }
}
</style>
