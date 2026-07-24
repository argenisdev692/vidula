<script setup lang="ts">
/**
 * Create / edit meeting modal — single date + start time; duration is fixed
 * (30 min from config, shown read-only). Attendees via AttendeePicker (search +
 * quick-create lead). Availability windows from `GET /meetings/availability`.
 *
 *   · create → POST /meetings
 *   · edit   → PUT  /meetings/{uuid}
 */
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import TextField from '@/common/form/TextField.vue';
import TextareaField from '@/common/form/TextareaField.vue';
import DateField from '@/common/form/DateField.vue';
import SelectField from '@/common/form/SelectField.vue';
import AppModal from '@/common/ui/AppModal.vue';
import AttendeePicker, { type AttendeeOption } from '@/common/meeting/AttendeePicker.vue';
import { apiFetch } from '@/lib/http';
import {
    MEETING_DURATION_MINUTES,
    meetingFormSchema,
} from '@/modules/meeting/schemas/meetingFormSchema';
import type { MeetingEditData, MeetingPrefill } from '@/modules/meeting/types';

interface ResolvedDay {
    date: string;
    is_open: boolean;
    slots: Array<{ start: string; end: string }>;
    reason: string | null;
}

interface TimeOption {
    label: string;
    value: string;
}

const visible = defineModel<boolean>('visible', { default: false });

const props = withDefaults(
    defineProps<{
        mode?: 'create' | 'edit';
        meeting?: MeetingEditData | null;
        prefill?: MeetingPrefill | null;
    }>(),
    { mode: 'create', meeting: null, prefill: null },
);

const emit = defineEmits<{ saved: [] }>();

const form = useForm<{
    title: string;
    description: string;
    starts_date: string;
    starts_time: string;
    attendees: AttendeeOption[];
}>({
    title: '',
    description: '',
    starts_date: '',
    starts_time: '',
    attendees: [],
});

const availability = ref<ResolvedDay | null>(null);
const availabilityLoading = ref<boolean>(false);
const availabilityError = ref<string>('');
const durationMinutes = ref<number>(MEETING_DURATION_MINUTES);

const isEdit = computed<boolean>(() => props.mode === 'edit');
const dialogTitle = computed<string>(() => (isEdit.value ? 'Edit meeting' : 'New meeting'));

const startsDateModel = computed<string | null>({
    get: () => form.starts_date || null,
    set: (value) => {
        form.starts_date = value ?? '';
    },
});

const startsTimeModel = computed<string | null>({
    get: () => form.starts_time || null,
    set: (value) => {
        form.starts_time = value ?? '';
    },
});

/** Title Case — capitalize the first letter of each word. */
function toTitleCase(value: string): string {
    return value.replace(/\S+/gu, (word) => {
        const lower = word.toLocaleLowerCase();
        return lower.charAt(0).toLocaleUpperCase() + lower.slice(1);
    });
}

const titleModel = computed<string>({
    get: () => form.title,
    set: (value) => {
        form.title = toTitleCase(value);
    },
});

function splitDate(value: string | undefined): string {
    return value ? value.slice(0, 10) : '';
}

function splitTime(value: string | undefined): string {
    return value ? value.slice(11, 16) : '';
}

function isoToday(): string {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
}

function combine(date: string, time: string): string {
    return date && time ? `${date}T${time}:00` : '';
}

function toMinutes(hhmm: string): number {
    const [h, m] = hhmm.slice(0, 5).split(':').map(Number);
    return h * 60 + m;
}

function fromMinutes(total: number): string {
    const h = Math.floor(total / 60);
    const m = total % 60;
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
}

const timeOptions = computed<TimeOption[]>(() => {
    const day = availability.value;
    if (!day?.is_open || day.slots.length === 0) {
        return [];
    }

    const step = durationMinutes.value;
    const options: TimeOption[] = [];

    for (const slot of day.slots) {
        const start = toMinutes(slot.start);
        const end = toMinutes(slot.end);
        for (let cursor = start; cursor + step <= end; cursor += step) {
            const value = fromMinutes(cursor);
            options.push({ label: value, value });
        }
    }

    return options;
});

const loadAvailability = useDebounceFn(async (date: string): Promise<void> => {
    if (!date) {
        availability.value = null;
        availabilityError.value = '';
        return;
    }

    availabilityLoading.value = true;
    availabilityError.value = '';
    try {
        const response = await apiFetch<{ data: ResolvedDay[]; meta?: { duration_minutes?: number } }>(
            'GET',
            `/meetings/availability?from=${encodeURIComponent(date)}&to=${encodeURIComponent(date)}`,
        );
        if (response.meta?.duration_minutes) {
            durationMinutes.value = response.meta.duration_minutes;
        }
        availability.value = response.data[0] ?? null;
        if (!availability.value?.is_open) {
            availabilityError.value = availability.value?.reason || 'This day is not available.';
            form.starts_time = '';
        } else if (form.starts_time && !timeOptions.value.some((o) => o.value === form.starts_time)) {
            form.starts_time = timeOptions.value[0]?.value ?? '';
        }
    } catch {
        availability.value = null;
        availabilityError.value = 'Could not load availability for this day.';
    } finally {
        availabilityLoading.value = false;
    }
}, 200);

watch(visible, (open) => {
    if (!open) {
        return;
    }
    form.clearErrors();
    availabilityError.value = '';

    const sourceStarts = props.meeting?.starts_at ?? props.prefill?.starts_at;
    form.title = props.meeting?.title ?? props.prefill?.title ?? '';
    form.description = props.meeting?.description ?? '';
    form.starts_date = splitDate(sourceStarts) || isoToday();
    form.starts_time = splitTime(sourceStarts);
    form.attendees = props.meeting?.attendees ?? props.prefill?.attendees ?? [];

    void loadAvailability(form.starts_date);
});

watch(
    () => form.starts_date,
    (date) => {
        if (visible.value) {
            void loadAvailability(date);
        }
    },
);

function close(): void {
    visible.value = false;
}

function submit(): void {
    const startsAt = combine(form.starts_date, form.starts_time);

    if (availabilityError.value || (availability.value && !availability.value.is_open)) {
        form.setError('starts_date' as never, availabilityError.value || 'This day is not available.');
        return;
    }

    if (form.starts_time && timeOptions.value.length > 0 && !timeOptions.value.some((o) => o.value === form.starts_time)) {
        form.setError('starts_time' as never, 'Choose a time inside an open availability window.');
        return;
    }

    const parsed = meetingFormSchema.safeParse({
        title: form.title,
        description: form.description,
        starts_at: startsAt,
        attendees: form.attendees,
    });
    if (!parsed.success) {
        form.clearErrors();
        for (const issue of parsed.error.issues) {
            const key = issue.path[0];
            if (key === 'starts_at') {
                form.setError('starts_date' as never, issue.message);
            } else if (typeof key === 'string') {
                form.setError(key as never, issue.message);
            }
        }
        return;
    }

    form.transform((data) => ({
        title: data.title.trim(),
        description: data.description.trim() || null,
        starts_at: startsAt,
        attendees: data.attendees.map((attendee) => ({ type: attendee.type, uuid: attendee.uuid })),
    }));

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            close();
        },
    };

    if (isEdit.value && props.meeting) {
        form.put(`/meetings/${props.meeting.uuid}`, options);
    } else {
        form.post('/meetings', options);
    }
}
</script>

<template>
    <AppModal
        v-model:visible="visible"
        :title="dialogTitle"
        :subtitle="isEdit ? 'Update this meeting. Duration stays fixed.' : 'Schedule a meeting — pick a day and start time.'"
        icon="pi pi-calendar-plus"
        :confirm-label="isEdit ? 'Save changes' : 'Create meeting'"
        :confirm-icon="isEdit ? 'pi pi-check' : 'pi pi-calendar-plus'"
        :loading="form.processing"
        :dismissable="!form.processing"
        width="40rem"
        @confirm="submit"
        @cancel="close"
    >
        <form class="meeting-dialog" @submit.prevent="submit">
                <TextField
                    v-model="titleModel"
                    name="title"
                    label="Title"
                    placeholder="e.g. Project Kickoff"
                    required
                    :maxlength="255"
                    :error="form.errors.title"
                    hint="Capitalized automatically."
                />

            <TextareaField
                v-model="form.description"
                name="description"
                label="Description"
                placeholder="Agenda or notes (optional)…"
                :rows="3"
                :maxlength="5000"
                :error="form.errors.description"
            />

            <div class="meeting-dialog__when">
                <DateField
                    v-model="startsDateModel"
                    name="starts_date"
                    label="Date"
                    required
                    :min-date="isoToday()"
                    :error="form.errors.starts_date || availabilityError || undefined"
                />
                <SelectField
                    v-model="startsTimeModel"
                    name="starts_time"
                    label="Start time"
                    placeholder="Select a time"
                    required
                    :options="timeOptions"
                    :disabled="availabilityLoading || timeOptions.length === 0"
                    :error="form.errors.starts_time"
                />
            </div>

            <p class="meeting-dialog__duration">
                <i class="pi pi-clock" aria-hidden="true" />
                Duration: {{ durationMinutes }} minutes (fixed)
            </p>

            <div class="meeting-dialog__attendees">
                <header>
                    <h3>Attendees</h3>
                    <p>Search users, leads, or contacts — or add a new lead.</p>
                </header>
                <AttendeePicker v-model="form.attendees" :error="form.errors.attendees" />
            </div>

            <button type="submit" class="meeting-dialog__enter" tabindex="-1" aria-hidden="true" />
        </form>
    </AppModal>
</template>

<style scoped>
.meeting-dialog {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.meeting-dialog__when {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-4);
}

.meeting-dialog__duration {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    margin: 0;
    padding: var(--space-3) var(--space-4);
    border-radius: var(--radius-md);
    border: 1px solid var(--border-subtle);
    background: color-mix(in srgb, var(--accent-primary) 8%, transparent);
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

.meeting-dialog__attendees header h3 {
    margin: 0;
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
}

.meeting-dialog__attendees header p {
    margin: var(--space-1) 0 var(--space-3);
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.meeting-dialog__enter {
    display: none;
}

@media (max-width: 640px) {
    .meeting-dialog__when {
        grid-template-columns: 1fr;
    }
}
</style>
