<script setup lang="ts">
/**
 * Shared create / edit meeting form — the single source of truth behind the
 * dedicated Create.vue and Edit.vue pages (mirrors AppointmentForm.vue's "no
 * modal" convention). Split date/time inputs are combined into
 * `starts_at`/`ends_at` on submit; the attendee list is resolved server-side
 * from `{type, uuid}` pairs by `AttendeeResolver` — this form never sends a
 * full attendee record, only what `AttendeePicker` already narrowed to.
 */
import { computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import TextField from '@/common/form/TextField.vue';
import TextareaField from '@/common/form/TextareaField.vue';
import DateField from '@/common/form/DateField.vue';
import TimeField from '@/common/form/TimeField.vue';
import SubmitButton from '@/common/form/SubmitButton.vue';
import SecondaryButton from '@/volt/SecondaryButton.vue';
import AttendeePicker, { type AttendeeOption } from '@/common/meeting/AttendeePicker.vue';
import { meetingFormSchema, type MeetingFormValues } from '@/modules/meeting/schemas/meetingFormSchema';
import type { MeetingEditData } from '@/modules/meeting/types';

const props = withDefaults(
    defineProps<{
        mode: 'create' | 'edit';
        meeting?: MeetingEditData | null;
    }>(),
    { meeting: null },
);

const isEdit = computed<boolean>(() => props.mode === 'edit');

function splitDate(value: string | undefined): string {
    return value ? value.slice(0, 10) : '';
}

function splitTime(value: string | undefined): string {
    return value ? value.slice(11, 16) : '';
}

const form = useForm<{
    title: string;
    description: string;
    starts_date: string;
    starts_time: string;
    ends_date: string;
    ends_time: string;
    attendees: AttendeeOption[];
}>({
    title: props.meeting?.title ?? '',
    description: props.meeting?.description ?? '',
    starts_date: splitDate(props.meeting?.starts_at),
    starts_time: splitTime(props.meeting?.starts_at) || '09:00',
    ends_date: splitDate(props.meeting?.ends_at),
    ends_time: splitTime(props.meeting?.ends_at) || '10:00',
    attendees: props.meeting?.attendees ?? [],
});

const submitLabel = computed<string>(() => (isEdit.value ? 'Save changes' : 'Create meeting'));
const submitIcon = computed<string>(() => (isEdit.value ? 'pi pi-check' : 'pi pi-calendar-plus'));

function combine(date: string, time: string): string {
    return date && time ? `${date}T${time}:00` : '';
}

function submit(): void {
    const startsAt = combine(form.starts_date, form.starts_time);
    const endsAt = combine(form.ends_date, form.ends_time);

    const parsed = meetingFormSchema.safeParse({
        title: form.title,
        description: form.description,
        starts_at: startsAt,
        ends_at: endsAt,
        attendees: form.attendees,
    });
    if (!parsed.success) {
        form.clearErrors();
        for (const issue of parsed.error.issues) {
            const key = issue.path[0];
            if (key === 'starts_at') {
                form.setError('starts_date' as never, issue.message);
            } else if (key === 'ends_at') {
                form.setError('ends_date' as never, issue.message);
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
        ends_at: endsAt,
        attendees: data.attendees.map((attendee) => ({ type: attendee.type, uuid: attendee.uuid })),
    }));

    const options = { preserveScroll: true };
    if (isEdit.value) {
        form.put(`/meetings/${props.meeting!.uuid}`, options);
    } else {
        form.post('/meetings', options);
    }
}
</script>

<template>
    <form class="meeting-form" @submit.prevent="submit">
        <section class="meeting-form__section">
            <header class="meeting-form__section-head">
                <h3 class="meeting-form__section-title">Details</h3>
            </header>
            <div class="meeting-form__grid">
                <TextField
                    v-model="form.title"
                    name="title"
                    label="Title"
                    placeholder="e.g. Project kickoff"
                    required
                    :maxlength="255"
                    :error="form.errors.title"
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

        <section class="meeting-form__section">
            <header class="meeting-form__section-head">
                <h3 class="meeting-form__section-title">When</h3>
            </header>
            <div class="meeting-form__grid">
                <DateField
                    v-model="form.starts_date"
                    name="starts_date"
                    label="Start date"
                    required
                    :error="form.errors.starts_date"
                />
                <TimeField v-model="form.starts_time" name="starts_time" label="Start time" required />
                <DateField
                    v-model="form.ends_date"
                    name="ends_date"
                    label="End date"
                    required
                    :error="form.errors.ends_date"
                />
                <TimeField v-model="form.ends_time" name="ends_time" label="End time" required />
            </div>
        </section>

        <section class="meeting-form__section">
            <header class="meeting-form__section-head">
                <h3 class="meeting-form__section-title">Attendees</h3>
                <p class="meeting-form__section-hint">Users, leads, or support contacts — search by name or email.</p>
            </header>
            <AttendeePicker v-model="form.attendees" :error="form.errors.attendees" />
        </section>

        <div class="meeting-form__actions">
            <SecondaryButton
                type="button"
                label="Cancel"
                :disabled="form.processing"
                @click="router.visit('/meetings')"
            />
            <SubmitButton :label="submitLabel" :icon="submitIcon" :loading="form.processing" />
        </div>
    </form>
</template>

<style scoped>
.meeting-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-7);
}

.meeting-form__section {
    display: flex;
    flex-direction: column;
    gap: var(--space-6);
}

.meeting-form__section-head {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
    padding-bottom: var(--space-3);
    border-bottom: 1px solid var(--border-default);
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

.meeting-form__section-hint {
    margin: 0;
    padding-left: calc(3px + var(--space-2));
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.meeting-form__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-3);
}

.meeting-form__actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: var(--space-3);
    margin-top: var(--space-2);
}

@media (max-width: 640px) {
    .meeting-form__grid {
        grid-template-columns: 1fr;
    }
}
</style>
