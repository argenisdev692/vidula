<script setup lang="ts">
/**
 * Shared create / edit meeting form — used by Create/Edit pages and by
 * MeetingFormDialog (modal). Owns Inertia `useForm` + Zod UX validation.
 * Availability windows come from `GET /meetings/availability` (same path for
 * page and dialog — no free-time bypass on dedicated pages).
 *
 *   · create → POST /meetings
 *   · edit   → PUT  /meetings/{uuid}
 *
 * When `variant="dialog"`, success/cancel are emitted for the parent modal
 * instead of navigating away (mirrors AppointmentForm).
 */
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import TextField from '@/common/form/TextField.vue';
import TextareaField from '@/common/form/TextareaField.vue';
import DateField from '@/common/form/DateField.vue';
import SelectField from '@/common/form/SelectField.vue';
import SubmitButton from '@/common/form/SubmitButton.vue';
import SecondaryButton from '@/volt/SecondaryButton.vue';
import AttendeePicker, { type AttendeeOption } from '@/common/meeting/AttendeePicker.vue';
import {
    fetchMeetingAvailability,
    type MeetingAvailabilityDay,
} from '@/modules/meeting/helpers/fetchMeetingAvailability';
import {
    MEETING_DURATION_MINUTES,
    meetingFormSchema,
} from '@/modules/meeting/schemas/meetingFormSchema';
import type { MeetingEditData, MeetingPrefill } from '@/modules/meeting/types';

interface TimeOption {
    label: string;
    value: string;
}

const props = withDefaults(
    defineProps<{
        mode: 'create' | 'edit';
        meeting?: MeetingEditData | null;
        prefill?: MeetingPrefill | null;
        /** `dialog` hides page actions and emits saved/cancel for AppModal. */
        variant?: 'page' | 'dialog';
    }>(),
    { meeting: null, prefill: null, variant: 'page' },
);

const emit = defineEmits<{ saved: []; cancel: [] }>();

const isEdit = computed<boolean>(() => props.mode === 'edit');
const isDialog = computed<boolean>(() => props.variant === 'dialog');

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

/** Title Case — capitalize the first letter of each word. */
function toTitleCase(value: string): string {
    return value.replace(/\S+/gu, (word) => {
        const lower = word.toLocaleLowerCase();
        return lower.charAt(0).toLocaleUpperCase() + lower.slice(1);
    });
}

function seedFromProps(): {
    title: string;
    description: string;
    starts_date: string;
    starts_time: string;
    attendees: AttendeeOption[];
} {
    const sourceStarts = props.meeting?.starts_at ?? props.prefill?.starts_at;
    return {
        title: props.meeting?.title ?? props.prefill?.title ?? '',
        description: props.meeting?.description ?? '',
        starts_date: splitDate(sourceStarts) || (isDialog.value ? isoToday() : ''),
        starts_time: splitTime(sourceStarts) || '',
        attendees: props.meeting?.attendees ?? props.prefill?.attendees ?? [],
    };
}

const seed = seedFromProps();

const form = useForm<{
    title: string;
    description: string;
    starts_date: string;
    starts_time: string;
    attendees: AttendeeOption[];
}>(seed);

const availability = ref<MeetingAvailabilityDay | null>(null);
const availabilityLoading = ref<boolean>(false);
const availabilityError = ref<string>('');
const durationMinutes = ref<number>(MEETING_DURATION_MINUTES);

const titleModel = computed<string>({
    get: () => form.title,
    set: (value) => {
        form.title = toTitleCase(value);
    },
});

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
        const response = await fetchMeetingAvailability({ from: date, to: date });
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

watch(
    () => [props.meeting, props.prefill, props.mode] as const,
    () => {
        if (!isDialog.value) {
            return;
        }
        form.clearErrors();
        availabilityError.value = '';
        const next = seedFromProps();
        form.title = next.title;
        form.description = next.description;
        form.starts_date = next.starts_date;
        form.starts_time = next.starts_time;
        form.attendees = next.attendees;
        void loadAvailability(form.starts_date);
    },
);

watch(
    () => form.starts_date,
    (date) => {
        void loadAvailability(date);
    },
    { immediate: true },
);

const submitLabel = computed<string>(() => (isEdit.value ? 'Save changes' : 'Create meeting'));
const submitIcon = computed<string>(() => (isEdit.value ? 'pi pi-check' : 'pi pi-calendar-plus'));

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
            if (isDialog.value) {
                emit('saved');
                return;
            }
            router.visit('/meetings');
        },
    };

    if (isEdit.value && props.meeting) {
        form.put(`/meetings/${props.meeting.uuid}`, options);
    } else {
        form.post('/meetings', options);
    }
}

function onCancel(): void {
    if (isDialog.value) {
        emit('cancel');
        return;
    }
    router.visit('/meetings');
}

defineExpose({
    submit,
    processing: computed(() => form.processing),
});
</script>

<template>
    <form class="meeting-form" :class="{ 'meeting-form--dialog': isDialog }" @submit.prevent="submit">
        <section v-if="!isDialog" class="meeting-form__section">
            <header class="meeting-form__section-head">
                <h3 class="meeting-form__section-title">Details</h3>
            </header>
            <div class="meeting-form__grid meeting-form__grid--single">
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
            </div>
            <TextareaField
                v-model="form.description"
                name="description"
                label="Description"
                placeholder="Agenda or notes (optional)…"
                :rows="4"
                :maxlength="5000"
                :error="form.errors.description"
            />
        </section>

        <template v-else>
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
        </template>

        <section class="meeting-form__section" :class="{ 'meeting-form__section--flat': isDialog }">
            <header v-if="!isDialog" class="meeting-form__section-head">
                <h3 class="meeting-form__section-title">When</h3>
                <p class="meeting-form__section-hint">Duration is fixed at {{ durationMinutes }} minutes.</p>
            </header>
            <div class="meeting-form__grid">
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
            <p v-if="isDialog" class="meeting-form__duration">
                <i class="pi pi-clock" aria-hidden="true" />
                Duration: {{ durationMinutes }} minutes (fixed)
            </p>
        </section>

        <section class="meeting-form__section" :class="{ 'meeting-form__section--flat': isDialog }">
            <header class="meeting-form__section-head" :class="{ 'meeting-form__section-head--plain': isDialog }">
                <h3 class="meeting-form__section-title" :class="{ 'meeting-form__section-title--plain': isDialog }">
                    Attendees
                </h3>
                <p class="meeting-form__section-hint" :class="{ 'meeting-form__section-hint--plain': isDialog }">
                    Users, leads, or support contacts — search by name or email.
                </p>
            </header>
            <AttendeePicker v-model="form.attendees" :error="form.errors.attendees" />
        </section>

        <div v-if="!isDialog" class="meeting-form__actions">
            <SecondaryButton type="button" label="Cancel" :disabled="form.processing" @click="onCancel" />
            <SubmitButton :label="submitLabel" :icon="submitIcon" :loading="form.processing" />
        </div>

        <button v-else type="submit" class="meeting-form__enter" tabindex="-1" aria-hidden="true" />
    </form>
</template>

<style scoped>
.meeting-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-7);
}

.meeting-form--dialog {
    gap: var(--space-5);
}

.meeting-form__section {
    display: flex;
    flex-direction: column;
    gap: var(--space-6);
}

.meeting-form__section--flat {
    gap: var(--space-3);
}

.meeting-form__section-head {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
    padding-bottom: var(--space-3);
    border-bottom: 1px solid var(--border-default);
}

.meeting-form__section-head--plain {
    padding-bottom: 0;
    border-bottom: none;
}

.meeting-form__section-title {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    margin: 0;
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
    letter-spacing: 0.01em;
    color: var(--accent-primary);
}

.meeting-form__section-title::before {
    content: '';
    width: 3px;
    height: 1.1em;
    flex-shrink: 0;
    border-radius: var(--radius-full, 99px);
    background: var(--grad-primary, var(--accent-primary));
}

.meeting-form__section-title--plain {
    color: var(--text-primary);
}

.meeting-form__section-title--plain::before {
    display: none;
}

.meeting-form__section-hint {
    margin: 0;
    padding-left: calc(3px + var(--space-2));
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.meeting-form__section-hint--plain {
    padding-left: 0;
    margin-bottom: var(--space-1);
}

.meeting-form__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-3);
}

.meeting-form__grid--single {
    grid-template-columns: 1fr;
}

.meeting-form__duration {
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

.meeting-form__actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: var(--space-3);
    margin-top: var(--space-2);
}

.meeting-form__enter {
    display: none;
}

@media (max-width: 640px) {
    .meeting-form__grid {
        grid-template-columns: 1fr;
    }
}
</style>
