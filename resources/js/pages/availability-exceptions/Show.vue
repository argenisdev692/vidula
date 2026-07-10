<script setup lang="ts">
/**
 * Date exception detail — read-only view rendered by
 * GET /availability-exceptions/{uuid} (VIEW_AVAILABILITY_EXCEPTIONS). The handler
 * resolves the record `withTrashed`, so a suspended exception is viewable here;
 * its status is shown via a badge, its provenance via a fact. Chrome + facts
 * styling live in the shared {@see DetailCard}.
 */
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import DetailCard from '@/common/ui/DetailCard.vue';
import StatusBadge from '@/common/ui/StatusBadge.vue';
import { formatDate, formatTime } from '@/modules/availability/helpers/availabilityFormat';
import type { AvailabilityException } from '@/modules/availability/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    availabilityException: AvailabilityException;
}>();

const isSuspended = computed<boolean>(() => props.availabilityException.deleted_at !== null);
const hours = computed<string>(() => {
    const e = props.availabilityException;
    if (!e.is_available) {
        return 'Closed all day';
    }
    if (!e.start_time || !e.end_time) {
        return '—';
    }
    return `${formatTime(e.start_time)}–${formatTime(e.end_time)}`;
});
</script>

<template>
    <Head :title="formatDate(availabilityException.date)" />

    <DetailCard
        header-title="Availability Exception"
        header-subtitle="Date override detail"
        permission="VIEW_AVAILABILITY_EXCEPTIONS"
        fallback-text="You don't have permission to view this availability exception."
        back-href="/availability-exceptions"
        back-label="Back to exceptions"
        :title="formatDate(availabilityException.date)"
    >
        <template #badges>
            <StatusBadge
                :tone="isSuspended ? 'danger' : 'success'"
                :label="isSuspended ? 'Suspended' : 'Active'"
            />
        </template>

        <dl class="facts">
            <div class="fact">
                <dt>State</dt>
                <dd>{{ availabilityException.is_available ? 'Open (forced available)' : 'Closed' }}</dd>
            </div>
            <div class="fact">
                <dt>Hours</dt>
                <dd class="mono">{{ hours }}</dd>
            </div>
            <div class="fact">
                <dt>Source</dt>
                <dd>{{ availabilityException.source === 'holiday' ? 'Holiday (system)' : 'Manual' }}</dd>
            </div>
            <div class="fact">
                <dt>Created</dt>
                <dd>{{ formatDate(availabilityException.created_at) }}</dd>
            </div>
            <div class="fact fact--wide">
                <dt>Reason</dt>
                <dd>{{ availabilityException.reason || '—' }}</dd>
            </div>
        </dl>
    </DetailCard>
</template>
