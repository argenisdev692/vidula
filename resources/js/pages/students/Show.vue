<script setup lang="ts">
/**
 * Student detail — read-only view from GET /students/{uuid} (VIEW_STUDENTS).
 * withTrashed, so suspended students remain viewable with a Suspended badge.
 */
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import DetailCard from '@/common/ui/DetailCard.vue';
import StatusBadge from '@/common/ui/StatusBadge.vue';
import { formatDateShort } from '@/modules/students/helpers/formatDate';
import type { Student } from '@/modules/students/types';

defineOptions({ layout: AppLayout });

type StudentDetail = Student & { updated_at?: string | null };

const props = defineProps<{
    student: StudentDetail;
}>();

const isSuspended = computed<boolean>(() => props.student.deleted_at !== null);
const name = computed<string>(() => props.student.name || 'Untitled student');

const lifecycleTone = computed<'success' | 'primary' | 'muted'>(() => {
    if (props.student.status === 'ACTIVE') {
        return 'success';
    }
    if (props.student.status === 'DRAFT') {
        return 'primary';
    }
    return 'muted';
});
</script>

<template>
    <Head :title="name" />

    <DetailCard
        header-title="Student"
        header-subtitle="LMS learner profile"
        permission="VIEW_STUDENTS"
        fallback-text="You don't have permission to view this student."
        back-href="/students"
        back-label="Back to students"
        :title="name"
    >
        <template #badges>
            <StatusBadge :tone="lifecycleTone" :label="student.status" />
            <StatusBadge
                :tone="student.active ? 'success' : 'muted'"
                :label="student.active ? 'Flag: Active' : 'Flag: Inactive'"
            />
            <StatusBadge
                :tone="isSuspended ? 'danger' : 'success'"
                :label="isSuspended ? 'Suspended' : 'Listed'"
            />
        </template>

        <dl class="facts">
            <div class="fact">
                <dt>Email</dt>
                <dd>{{ student.email || '—' }}</dd>
            </div>
            <div class="fact">
                <dt>Phone</dt>
                <dd class="mono">{{ student.phone || '—' }}</dd>
            </div>
            <div class="fact">
                <dt>DNI</dt>
                <dd>{{ student.dni || '—' }}</dd>
            </div>
            <div class="fact fact--wide">
                <dt>Address</dt>
                <dd>{{ student.address || '—' }}</dd>
            </div>
            <div class="fact fact--wide">
                <dt>Avatar URL</dt>
                <dd>{{ student.avatar || '—' }}</dd>
            </div>
            <div class="fact fact--wide">
                <dt>Notes</dt>
                <dd>{{ student.notes || '—' }}</dd>
            </div>
            <div class="fact">
                <dt>Created</dt>
                <dd>{{ formatDateShort(student.created_at) }}</dd>
            </div>
            <div class="fact">
                <dt>Last updated</dt>
                <dd>{{ formatDateShort(student.updated_at ?? null) }}</dd>
            </div>
        </dl>
    </DetailCard>
</template>
