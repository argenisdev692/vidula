<script setup lang="ts">
/**
 * Enrollment detail — read-only view from GET /enrollments/{uuid}.
 * withTrashed, so suspended enrollments remain viewable with a Suspended badge.
 */
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import DetailCard from '@/common/ui/DetailCard.vue';
import StatusBadge from '@/common/ui/StatusBadge.vue';
import { formatDateShort } from '@/modules/enrollments/helpers/formatDate';
import type { Enrollment } from '@/modules/enrollments/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    enrollment: Enrollment;
}>();

const isSuspended = computed<boolean>(() => props.enrollment.deleted_at !== null);
const name = computed<string>(() => props.enrollment.student?.name || 'Enrollment');

const statusTone = computed<'success' | 'danger' | 'muted' | 'primary'>(() => {
    const status = props.enrollment.enrollment_status;
    if (status === 'active') {
        return 'success';
    }
    if (status === 'completed') {
        return 'primary';
    }
    if (status === 'dropped' || status === 'suspended') {
        return 'danger';
    }
    return 'muted';
});
</script>

<template>
    <Head :title="name" />

    <DetailCard
        header-title="Enrollment"
        header-subtitle="Student ↔ classroom assignment"
        permission="VIEW_ENROLLMENTS"
        fallback-text="You don't have permission to view this enrollment."
        back-href="/enrollments"
        back-label="Back to enrollments"
        :title="name"
    >
        <template #badges>
            <StatusBadge :tone="statusTone" :label="enrollment.enrollment_status" />
            <StatusBadge
                :tone="isSuspended ? 'danger' : 'success'"
                :label="isSuspended ? 'Suspended' : 'Listed'"
            />
        </template>

        <dl class="facts">
            <div class="fact">
                <dt>Student email</dt>
                <dd>{{ enrollment.student?.email || '—' }}</dd>
            </div>
            <div class="fact">
                <dt>Classroom</dt>
                <dd>{{ enrollment.classroom?.product?.title || '—' }}</dd>
            </div>
            <div class="fact">
                <dt>Enrolled at</dt>
                <dd>{{ formatDateShort(enrollment.enrolled_at) }}</dd>
            </div>
            <div class="fact">
                <dt>Final grade</dt>
                <dd>{{ enrollment.final_grade ?? '—' }}</dd>
            </div>
            <div class="fact fact--wide">
                <dt>Notes</dt>
                <dd>{{ enrollment.notes || '—' }}</dd>
            </div>
            <div class="fact">
                <dt>Created</dt>
                <dd>{{ formatDateShort(enrollment.created_at) }}</dd>
            </div>
        </dl>

        <div v-if="enrollment.classroom?.uuid && !isSuspended" class="attendance-cta">
            <Link
                :href="`/enrollments/attendance/${enrollment.classroom.uuid}`"
                class="attendance-link"
                prefetch
            >
                <i class="pi pi-calendar" aria-hidden="true" />
                Open attendance sheet
            </Link>
        </div>
    </DetailCard>
</template>

<style scoped>
.attendance-cta {
    margin-top: var(--space-4);
}

.attendance-link {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    text-decoration: none;
    font-weight: var(--font-semibold);
    color: var(--accent-primary);
    font-family: var(--font-sans);
}

.attendance-link:focus-visible {
    outline: 2px solid var(--accent-primary);
    outline-offset: 2px;
    border-radius: var(--radius-sm);
}
</style>
