<script setup lang="ts">
/**
 * Availability rule detail — read-only view rendered by
 * GET /availability-rules/{uuid} (VIEW_AVAILABILITY_RULES). The handler resolves
 * the record `withTrashed`, so a suspended rule is viewable here; its status is
 * shown via a badge. Chrome + facts styling live in the shared {@see DetailCard}.
 */
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import DetailCard from '@/common/ui/DetailCard.vue';
import StatusBadge from '@/common/ui/StatusBadge.vue';
import { dayLabel, formatDate, formatTime } from '@/modules/availability/helpers/availabilityFormat';
import type { AvailabilityRule } from '@/modules/availability/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    availabilityRule: AvailabilityRule;
}>();

const isSuspended = computed<boolean>(() => props.availabilityRule.deleted_at !== null);
const title = computed<string>(() => `${dayLabel(props.availabilityRule.day_of_week)} rule`);
</script>

<template>
    <Head :title="title" />

    <DetailCard
        header-title="Availability Rule"
        header-subtitle="Weekly rule detail"
        permission="VIEW_AVAILABILITY_RULES"
        fallback-text="You don't have permission to view this availability rule."
        back-href="/availability-rules"
        back-label="Back to rules"
        :title="dayLabel(availabilityRule.day_of_week)"
    >
        <template #badges>
            <StatusBadge
                :tone="isSuspended ? 'danger' : 'success'"
                :label="isSuspended ? 'Suspended' : 'Active'"
            />
        </template>

        <dl class="facts">
            <div class="fact">
                <dt>Start time</dt>
                <dd class="mono">{{ formatTime(availabilityRule.start_time) }}</dd>
            </div>
            <div class="fact">
                <dt>End time</dt>
                <dd class="mono">{{ formatTime(availabilityRule.end_time) }}</dd>
            </div>
            <div class="fact">
                <dt>Availability</dt>
                <dd>{{ availabilityRule.is_available ? 'Available' : 'Unavailable' }}</dd>
            </div>
            <div class="fact">
                <dt>Created</dt>
                <dd>{{ formatDate(availabilityRule.created_at) }}</dd>
            </div>
            <div class="fact">
                <dt>Last updated</dt>
                <dd>{{ formatDate(availabilityRule.updated_at ?? null) }}</dd>
            </div>
        </dl>
    </DetailCard>
</template>
