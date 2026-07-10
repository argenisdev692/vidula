<script setup lang="ts">
/**
 * Permission detail — read-only view rendered by GET /permissions/{uuid}
 * (VIEW_PERMISSIONS). The handler resolves the record `withTrashed`, so a
 * suspended permission is viewable here; its status is shown via a badge. All
 * chrome + facts styling live in the shared {@see DetailCard}.
 */
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import DetailCard from '@/common/ui/DetailCard.vue';
import StatusBadge from '@/common/ui/StatusBadge.vue';
import { formatDate } from '@/modules/authorization/helpers/formatDate';
import { groupPermissions } from '@/modules/authorization/helpers/groupPermissions';
import type { PermissionDetail } from '@/modules/authorization/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    permission: PermissionDetail;
}>();

const isSuspended = computed<boolean>(() => props.permission.deleted_at !== null);

/** Split the name into its module + action for a friendlier summary. */
const parsed = computed(() => groupPermissions([props.permission.name])[0]);
const moduleLabel = computed<string>(() => parsed.value?.label ?? '—');
const actionLabel = computed<string>(() => parsed.value?.entries[0]?.action ?? '—');
</script>

<template>
    <Head :title="permission.name" />

    <DetailCard
        header-title="Permission"
        header-subtitle="Permission detail"
        permission="VIEW_PERMISSIONS"
        fallback-text="You don't have permission to view this permission."
        back-href="/permissions"
        back-label="Back to permissions"
        :title="permission.name"
        :columns="3"
        mono-title
        icon-tone="info"
    >
        <template #title-icon>
            <i class="pi pi-key" aria-hidden="true" />
        </template>
        <template #badges>
            <StatusBadge
                :tone="isSuspended ? 'danger' : 'success'"
                :label="isSuspended ? 'Suspended' : 'Active'"
            />
        </template>

        <dl class="facts">
            <div class="fact">
                <dt>Action</dt>
                <dd>{{ actionLabel }}</dd>
            </div>
            <div class="fact">
                <dt>Module</dt>
                <dd>{{ moduleLabel }}</dd>
            </div>
            <div class="fact">
                <dt>Guard</dt>
                <dd class="mono">{{ permission.guard_name }}</dd>
            </div>
            <div class="fact">
                <dt>Used by</dt>
                <dd>{{ permission.roles_count }} {{ permission.roles_count === 1 ? 'role' : 'roles' }}</dd>
            </div>
            <div class="fact">
                <dt>Created</dt>
                <dd>{{ formatDate(permission.created_at) }}</dd>
            </div>
            <div class="fact">
                <dt>Last updated</dt>
                <dd>{{ formatDate(permission.updated_at ?? null) }}</dd>
            </div>
        </dl>
    </DetailCard>
</template>
