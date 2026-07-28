<script setup lang="ts">
/**
 * Show-page pipeline toolbar for an active lead: confirm / reschedule / cancel
 * the booked meeting, log a follow-up call, mark as read, and deep-link to edit.
 * Each action posts to the dedicated backend route (never mass-assigned update).
 * Suspended leads render nothing — restore happens from the Index list.
 */
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import AppModal from '@/common/ui/AppModal.vue';
import DateField from '@/common/form/DateField.vue';
import TimeField from '@/common/form/TimeField.vue';
import TextareaField from '@/common/form/TextareaField.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import Button from '@/volt/Button.vue';
import type { AppointmentDetail } from '@/modules/appointments/types';

const props = defineProps<{
    appointment: AppointmentDetail;
}>();

const toast = useToast();
const processing = ref<boolean>(false);

type PipelineDialog = 'confirm' | 'reschedule' | 'cancel' | 'followUp' | null;
const activeDialog = ref<PipelineDialog>(null);

const scheduledDate = ref<string | null>(null);
const scheduledTime = ref<string | null>('09:00');
const cancelReason = ref<string>('');
const followUpNote = ref<string>('');
const fieldError = ref<string>('');

const isSuspended = computed<boolean>(() => props.appointment.deleted_at !== null);
const isCancelled = computed<boolean>(() => props.appointment.meeting_status === 'Cancelled');
const canMutate = computed<boolean>(() => !isSuspended.value);

function seedScheduleFromAppointment(): void {
    const raw = props.appointment.scheduled_at;
    if (!raw) {
        scheduledDate.value = null;
        scheduledTime.value = '09:00';
        return;
    }
    const date = new Date(raw);
    if (Number.isNaN(date.getTime())) {
        scheduledDate.value = null;
        scheduledTime.value = '09:00';
        return;
    }
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    const hh = String(date.getHours()).padStart(2, '0');
    const mi = String(date.getMinutes()).padStart(2, '0');
    scheduledDate.value = `${yyyy}-${mm}-${dd}`;
    scheduledTime.value = `${hh}:${mi}`;
}

function openDialog(kind: Exclude<PipelineDialog, null>): void {
    fieldError.value = '';
    cancelReason.value = '';
    followUpNote.value = '';
    seedScheduleFromAppointment();
    activeDialog.value = kind;
}

function closeDialog(): void {
    if (processing.value) {
        return;
    }
    activeDialog.value = null;
}

function buildScheduledAt(): string | null {
    if (!scheduledDate.value) {
        return null;
    }
    return `${scheduledDate.value}T${scheduledTime.value || '09:00'}:00`;
}

function goEdit(): void {
    router.visit(`/appointments/${props.appointment.uuid}/edit`);
}

function runPatch(url: string, payload: Record<string, unknown>, successSummary: string): void {
    processing.value = true;
    router.patch(url, payload, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: successSummary, life: 3000 });
            activeDialog.value = null;
        },
        onError: (errors) => {
            const first = Object.values(errors)[0];
            fieldError.value = typeof first === 'string' ? first : 'Could not complete this action.';
            toast.add({ severity: 'error', summary: fieldError.value, life: 4000 });
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}

function runPost(url: string, payload: Record<string, unknown>, successSummary: string): void {
    processing.value = true;
    router.post(url, payload, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: successSummary, life: 3000 });
            activeDialog.value = null;
        },
        onError: (errors) => {
            const first = Object.values(errors)[0];
            fieldError.value = typeof first === 'string' ? first : 'Could not complete this action.';
            toast.add({ severity: 'error', summary: fieldError.value, life: 4000 });
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}

function submitConfirm(): void {
    fieldError.value = '';
    const scheduledAt = buildScheduledAt();
    runPatch(
        `/appointments/${props.appointment.uuid}/confirm`,
        scheduledAt ? { scheduled_at: scheduledAt } : {},
        'Appointment confirmed',
    );
}

function submitReschedule(): void {
    fieldError.value = '';
    const scheduledAt = buildScheduledAt();
    if (!scheduledAt) {
        fieldError.value = 'Pick a new date and time.';
        return;
    }
    runPatch(
        `/appointments/${props.appointment.uuid}/reschedule`,
        { scheduled_at: scheduledAt },
        'Appointment rescheduled',
    );
}

function submitCancel(): void {
    fieldError.value = '';
    runPatch(
        `/appointments/${props.appointment.uuid}/cancel`,
        { reason: cancelReason.value.trim() || null },
        'Appointment cancelled',
    );
}

function submitFollowUp(): void {
    fieldError.value = '';
    const note = followUpNote.value.trim();
    if (!note) {
        fieldError.value = 'A call note is required.';
        return;
    }
    runPost(
        `/appointments/${props.appointment.uuid}/follow-up-calls`,
        { note },
        'Follow-up call logged',
    );
}

function markRead(): void {
    processing.value = true;
    router.patch(
        `/appointments/${props.appointment.uuid}/read`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Marked as read', life: 3000 });
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}
</script>

<template>
    <div v-if="canMutate" class="pipeline">
        <PermissionGuard permission="UPDATE_APPOINTMENTS">
            <div class="pipeline__row">
                <Button
                    v-if="!appointment.readed"
                    type="button"
                    label="Mark read"
                    icon="pi pi-envelope-open"
                    size="small"
                    outlined
                    :disabled="processing"
                    aria-label="Mark lead as read"
                    @click="markRead"
                />
                <Button
                    v-if="!isCancelled"
                    type="button"
                    label="Confirm"
                    icon="pi pi-check"
                    size="small"
                    :disabled="processing"
                    aria-label="Confirm appointment"
                    @click="openDialog('confirm')"
                />
                <Button
                    v-if="!isCancelled"
                    type="button"
                    label="Reschedule"
                    icon="pi pi-calendar"
                    size="small"
                    outlined
                    :disabled="processing"
                    aria-label="Reschedule appointment"
                    @click="openDialog('reschedule')"
                />
                <Button
                    v-if="!isCancelled"
                    type="button"
                    label="Cancel meeting"
                    icon="pi pi-times"
                    size="small"
                    severity="danger"
                    outlined
                    :disabled="processing"
                    aria-label="Cancel appointment"
                    @click="openDialog('cancel')"
                />
                <Button
                    type="button"
                    label="Log call"
                    icon="pi pi-phone"
                    size="small"
                    outlined
                    :disabled="processing"
                    aria-label="Log follow-up call"
                    @click="openDialog('followUp')"
                />
                <Button
                    type="button"
                    label="Edit profile"
                    icon="pi pi-pencil"
                    size="small"
                    text
                    aria-label="Edit lead profile"
                    @click="goEdit"
                />
            </div>
        </PermissionGuard>

        <AppModal
            :visible="activeDialog === 'confirm'"
            title="Confirm appointment"
            subtitle="Locks the meeting time and emails the lead."
            icon="pi pi-check"
            confirm-label="Confirm"
            confirm-icon="pi pi-check"
            :loading="processing"
            :dismissable="!processing"
            autofocus-confirm
            @update:visible="(open: boolean) => { if (!open) closeDialog(); }"
            @confirm="submitConfirm"
            @cancel="closeDialog"
        >
            <p class="hint">Leave the schedule blank to keep the requested time.</p>
            <div class="fields">
                <DateField v-model="scheduledDate" name="scheduled_date" label="Date" />
                <TimeField v-model="scheduledTime" name="scheduled_time" label="Time" />
            </div>
            <p v-if="fieldError" class="error" role="alert">{{ fieldError }}</p>
        </AppModal>

        <AppModal
            :visible="activeDialog === 'reschedule'"
            title="Reschedule appointment"
            subtitle="Moves the meeting and keeps the previous timestamp for the email."
            icon="pi pi-calendar"
            confirm-label="Reschedule"
            confirm-icon="pi pi-calendar"
            :loading="processing"
            :dismissable="!processing"
            autofocus-confirm
            @update:visible="(open: boolean) => { if (!open) closeDialog(); }"
            @confirm="submitReschedule"
            @cancel="closeDialog"
        >
            <div class="fields">
                <DateField v-model="scheduledDate" name="reschedule_date" label="New date" required />
                <TimeField v-model="scheduledTime" name="reschedule_time" label="New time" required />
            </div>
            <p v-if="fieldError" class="error" role="alert">{{ fieldError }}</p>
        </AppModal>

        <AppModal
            :visible="activeDialog === 'cancel'"
            title="Cancel appointment"
            subtitle="Marks the meeting cancelled. Optional reason is appended to notes."
            icon="pi pi-times"
            tone="danger"
            confirm-label="Cancel meeting"
            confirm-icon="pi pi-times"
            :loading="processing"
            :dismissable="!processing"
            autofocus-confirm
            @update:visible="(open: boolean) => { if (!open) closeDialog(); }"
            @confirm="submitCancel"
            @cancel="closeDialog"
        >
            <TextareaField
                v-model="cancelReason"
                name="reason"
                label="Reason (optional)"
                :rows="3"
                :maxlength="1000"
                placeholder="e.g. Client requested a later date"
            />
            <p v-if="fieldError" class="error" role="alert">{{ fieldError }}</p>
        </AppModal>

        <AppModal
            :visible="activeDialog === 'followUp'"
            title="Log follow-up call"
            subtitle="Appends a timestamped note. A New lead advances to Called."
            icon="pi pi-phone"
            confirm-label="Log call"
            confirm-icon="pi pi-phone"
            :loading="processing"
            :dismissable="!processing"
            autofocus-confirm
            @update:visible="(open: boolean) => { if (!open) closeDialog(); }"
            @confirm="submitFollowUp"
            @cancel="closeDialog"
        >
            <TextareaField
                v-model="followUpNote"
                name="note"
                label="Call note"
                required
                :rows="3"
                :maxlength="1000"
                placeholder="e.g. Left a voicemail"
                :error="fieldError || undefined"
            />
        </AppModal>
    </div>
</template>

<style scoped>
.pipeline {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    margin-bottom: var(--space-5);
}

.pipeline__row {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: var(--space-2);
}

.fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-3);
}

.hint {
    margin: 0 0 var(--space-3);
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

.error {
    margin: var(--space-3) 0 0;
    font-size: var(--text-sm);
    color: var(--accent-error);
}

@media (max-width: 640px) {
    .fields {
        grid-template-columns: 1fr;
    }

    .pipeline__row {
        justify-content: stretch;
    }

    .pipeline__row :deep(button) {
        flex: 1 1 auto;
    }
}
</style>
