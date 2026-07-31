<script setup lang="ts">
/**
 * Lead detail — GET /appointments/{uuid} (VIEW_APPOINTMENTS). Suspended leads
 * remain viewable (`withTrashed`). Pipeline mutations (confirm / reschedule /
 * cancel / follow-up / mark-read / edit) live in AppointmentPipelinePanel;
 * Schedule Google Meet deep-links into Meetings with the lead prefilled.
 */
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import DetailCard from '@/common/ui/DetailCard.vue';
import StatusBadge from '@/common/ui/StatusBadge.vue';
import { useAuthorization } from '@/modules/auth/composables/useAuthorization';
import Button from '@/volt/Button.vue';
import Tag from '@/volt/Tag.vue';
import AppointmentPipelinePanel from './components/AppointmentPipelinePanel.vue';
import { formatDateTime } from '@/modules/appointments/helpers/formatDate';
import { appointmentDisplayName } from '@/modules/appointments/helpers/displayName';
import { appointmentServiceLabel } from '@/modules/appointments/helpers/serviceLabel';
import { CLIENT_TYPE_LABEL, MEETING_STATUS_META, STATUS_LEAD_META } from '@/modules/appointments/helpers/statusMeta';
import type { AppointmentDetail } from '@/modules/appointments/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    appointment: AppointmentDetail;
}>();

const { hasPermission } = useAuthorization();
const canScheduleMeeting = computed<boolean>(() => hasPermission('CREATE_MEETINGS'));

const isSuspended = computed<boolean>(() => props.appointment.deleted_at !== null);
const fullName = computed<string>(() => appointmentDisplayName(props.appointment, 'Unknown lead'));

function scheduleMeeting(): void {
    router.visit(`/meetings/create?lead=${props.appointment.uuid}`);
}

const addressLine = computed<string>(() => {
    const parts = [
        props.appointment.address,
        props.appointment.address_2,
        props.appointment.city,
        props.appointment.state,
        props.appointment.zip_code,
        props.appointment.country,
    ].filter(Boolean);
    return parts.length ? parts.join(', ') : '—';
});
</script>

<template>
    <Head :title="fullName" />

    <DetailCard
        header-title="Appointment"
        header-subtitle="Lead & meeting detail"
        permission="VIEW_APPOINTMENTS"
        fallback-text="You don't have permission to view this lead."
        back-href="/appointments"
        back-label="Back to appointments"
        :title="fullName"
        :columns="3"
        max-width="56rem"
    >
        <template #title-icon>
            <i class="pi pi-user" aria-hidden="true" />
        </template>
        <template #badges>
            <StatusBadge
                :tone="appointment.readed ? 'muted' : 'primary'"
                :label="appointment.readed ? 'Read' : 'Unread'"
                :strong="!appointment.readed"
            />
            <StatusBadge v-if="appointment.is_spam" tone="danger" label="Spam" strong />
            <StatusBadge :tone="isSuspended ? 'danger' : 'success'" :label="isSuspended ? 'Suspended' : 'Active'" />
        </template>

        <AppointmentPipelinePanel :appointment="appointment" />

        <div v-if="canScheduleMeeting && !isSuspended" class="lead-actions">
            <Button
                label="Schedule Google Meet"
                icon="pi pi-video"
                size="small"
                outlined
                @click="scheduleMeeting"
            />
        </div>

        <dl class="facts">
            <div class="fact">
                <dt>Client type</dt>
                <dd>{{ CLIENT_TYPE_LABEL[appointment.client_type] }}</dd>
            </div>
            <div class="fact">
                <dt>Company</dt>
                <dd>{{ appointment.company_name ?? '—' }}</dd>
            </div>
            <div class="fact">
                <dt>Service</dt>
                <dd>{{ appointmentServiceLabel(appointment) }}</dd>
            </div>

            <div class="fact">
                <dt>Email</dt>
                <dd>
                    <a class="link" :href="`mailto:${appointment.email}`">{{ appointment.email }}</a>
                </dd>
            </div>
            <div class="fact">
                <dt>Phone</dt>
                <dd>
                    <a v-if="appointment.phone" class="link" :href="`tel:${appointment.phone}`">{{ appointment.phone }}</a>
                    <span v-else>—</span>
                </dd>
            </div>
            <div class="fact">
                <dt>SMS consent</dt>
                <dd>{{ appointment.sms_consent ? 'Yes' : 'No' }}</dd>
            </div>

            <div class="fact fact--wide">
                <dt>Address</dt>
                <dd>{{ addressLine }}</dd>
            </div>

            <div class="fact">
                <dt>Lead status</dt>
                <dd>
                    <Tag
                        v-if="appointment.status_lead"
                        :value="STATUS_LEAD_META[appointment.status_lead!].label"
                        :severity="STATUS_LEAD_META[appointment.status_lead!].severity"
                    />
                    <span v-else>—</span>
                </dd>
            </div>
            <div class="fact">
                <dt>Meeting status</dt>
                <dd>
                    <Tag
                        v-if="appointment.meeting_status"
                        :value="MEETING_STATUS_META[appointment.meeting_status!].label"
                        :severity="MEETING_STATUS_META[appointment.meeting_status!].severity"
                    />
                    <span v-else>Not yet confirmed</span>
                </dd>
            </div>
            <div class="fact">
                <dt>Owner</dt>
                <dd>{{ appointment.owner ?? '—' }}</dd>
            </div>

            <div class="fact">
                <dt>Scheduled for</dt>
                <dd>{{ formatDateTime(appointment.scheduled_at) }}</dd>
            </div>
            <div class="fact">
                <dt>Previously scheduled</dt>
                <dd>{{ formatDateTime(appointment.previous_scheduled_at) }}</dd>
            </div>
            <div class="fact">
                <dt>Captured</dt>
                <dd>{{ formatDateTime(appointment.created_at) }}</dd>
            </div>

            <div v-if="appointment.is_spam" class="fact fact--wide">
                <dt>Spam verdict</dt>
                <dd>
                    Score {{ appointment.spam_score }}
                    <template v-if="appointment.spam_reasons?.length">
                        — {{ appointment.spam_reasons.join(', ') }}
                    </template>
                </dd>
            </div>

            <div v-if="appointment.notes" class="fact fact--wide">
                <dt>Notes</dt>
                <dd class="message">{{ appointment.notes }}</dd>
            </div>

            <div v-if="appointment.follow_up_calls?.length" class="fact fact--wide">
                <dt>Follow-up calls</dt>
                <dd>
                    <ul class="calls">
                        <li v-for="(call, index) in appointment.follow_up_calls" :key="index" class="calls__item">
                            <span class="calls__date">{{ formatDateTime(call.at) }}</span>
                            <span class="calls__note">{{ call.note }}</span>
                        </li>
                    </ul>
                </dd>
            </div>
        </dl>
    </DetailCard>
</template>

<style scoped>
.lead-actions {
    display: flex;
    justify-content: flex-end;
    margin-bottom: var(--space-5);
}

.message {
    white-space: pre-wrap;
    word-break: break-word;
}

.calls {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    margin: 0;
    padding: 0;
    list-style: none;
}

.calls__item {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius-sm);
    background: color-mix(in srgb, var(--bg-elevated) 40%, transparent);
}

.calls__date {
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.calls__note {
    font-size: var(--text-sm);
    color: var(--text-primary);
}
</style>
