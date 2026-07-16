<script setup lang="ts">
/**
 * Read-only meeting detail. Uses the shared DetailCard shell (AppHeader +
 * VIEW_MEETINGS guard + BackLink + glass card), mirrors the other modules'
 * Show pages. Attendees render with just their type tag + id (no name) since
 * this endpoint only returns `attendable_type`/`attendable_id` — resolving a
 * human label per row would mean either an eager N+1 join across three
 * unrelated tables or duplicating `SearchAttendeesHandler`'s minimal-field
 * lookup here; deferred to a fast-follow if operators ask for it.
 */
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import DetailCard from '@/common/ui/DetailCard.vue';
import Tag from '@/volt/Tag.vue';
import { formatDateTime } from '@/modules/meeting/helpers/formatDate';
import type { MeetingDetail } from '@/modules/meeting/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    meeting: MeetingDetail;
}>();

function organizerName(): string {
    if (!props.meeting.organizer) {
        return '—';
    }
    return `${props.meeting.organizer.first_name} ${props.meeting.organizer.last_name}`.trim() || '—';
}

const TYPE_LABEL: Record<string, string> = { user: 'User', lead: 'Lead', contact: 'Contact' };
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
            <div v-if="meeting.description" class="fact fact--wide">
                <dt>Description</dt>
                <dd>{{ meeting.description }}</dd>
            </div>
            <div class="fact fact--wide">
                <dt>Attendee list</dt>
                <dd>
                    <div class="attendee-list">
                        <Tag
                            v-for="attendee in meeting.attendees"
                            :key="`${attendee.attendable_type}-${attendee.attendable_id}`"
                            :value="`${TYPE_LABEL[attendee.attendable_type] ?? attendee.attendable_type} #${attendee.attendable_id}`"
                            severity="secondary"
                        />
                        <span v-if="meeting.attendees.length === 0" class="mono">No attendees.</span>
                    </div>
                </dd>
            </div>
        </dl>
    </DetailCard>
</template>

<style scoped>
.attendee-list {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
}
</style>
