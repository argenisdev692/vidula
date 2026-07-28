<script setup lang="ts">
/**
 * Read-only meeting detail. Uses the shared DetailCard shell (AppHeader +
 * VIEW_MEETINGS guard + BackLink + glass card), mirrors the other modules'
 * Show pages.
 */
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import DetailCard from '@/common/ui/DetailCard.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import Tag from '@/volt/Tag.vue';
import Button from '@/volt/Button.vue';
import {
    ATTENDEE_TYPE_LABEL,
    ATTENDEE_TYPE_SEVERITY,
} from '@/common/meeting/attendeeMeta';
import { formatDateTime } from '@/modules/meeting/helpers/formatDate';
import type { MeetingAttendeeOption, MeetingDetail } from '@/modules/meeting/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    meeting: MeetingDetail;
    attendeeLabels: MeetingAttendeeOption[];
}>();

function organizerName(): string {
    if (!props.meeting.organizer) {
        return '—';
    }
    return `${props.meeting.organizer.first_name} ${props.meeting.organizer.last_name}`.trim() || '—';
}
</script>

<template>
    <Head :title="meeting.title" />

    <DetailCard
        header-title="Meeting"
        header-subtitle="Internal scheduling detail"
        permission="VIEW_MEETINGS"
        fallback-text="You don't have permission to view this meeting."
        back-href="/meetings"
        back-label="Back to meetings"
        :title="meeting.title"
        :columns="2"
    >
        <template #badges>
            <Tag :value="meeting.status" :severity="meeting.status === 'Cancelled' ? 'danger' : 'success'" />
            <Tag v-if="meeting.deleted_at" value="Deleted" severity="secondary" />
            <PermissionGuard v-if="!meeting.deleted_at" permission="UPDATE_MEETINGS">
                <Link :href="`/meetings/${meeting.uuid}/edit`" class="edit-link">
                    <Button type="button" label="Edit" icon="pi pi-pencil" size="small" outlined />
                </Link>
            </PermissionGuard>
        </template>

        <dl class="facts">
            <div class="fact">
                <dt>Organizer</dt>
                <dd>{{ organizerName() }}</dd>
            </div>
            <div class="fact">
                <dt>Attendees</dt>
                <dd>{{ meeting.attendees_count }}</dd>
            </div>
            <div class="fact">
                <dt>Starts</dt>
                <dd class="mono">{{ formatDateTime(meeting.starts_at) }}</dd>
            </div>
            <div class="fact">
                <dt>Ends</dt>
                <dd class="mono">{{ formatDateTime(meeting.ends_at) }}</dd>
            </div>
            <div v-if="meeting.meet_link" class="fact fact--wide">
                <dt>Google Meet</dt>
                <dd>
                    <a class="link" :href="meeting.meet_link" target="_blank" rel="noopener noreferrer">
                        {{ meeting.meet_link }}
                    </a>
                </dd>
            </div>
            <div v-if="meeting.description" class="fact fact--wide">
                <dt>Description</dt>
                <dd>{{ meeting.description }}</dd>
            </div>
            <div class="fact fact--wide">
                <dt>Attendee list</dt>
                <dd>
                    <div class="attendee-list">
                        <span
                            v-for="attendee in attendeeLabels"
                            :key="`${attendee.type}:${attendee.uuid}`"
                            class="attendee-list__item"
                        >
                            <Tag :value="ATTENDEE_TYPE_LABEL[attendee.type] ?? attendee.type" :severity="ATTENDEE_TYPE_SEVERITY[attendee.type] ?? 'secondary'" />
                            <span>{{ attendee.label }}</span>
                        </span>
                        <span v-if="attendeeLabels.length === 0" class="mono">No attendees.</span>
                    </div>
                </dd>
            </div>
        </dl>
    </DetailCard>
</template>

<style scoped>
.attendee-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.attendee-list__item {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
}

.edit-link {
    text-decoration: none;
}
</style>
