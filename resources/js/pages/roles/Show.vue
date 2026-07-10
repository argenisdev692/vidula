<script setup lang="ts">
/**
 * Role detail — read-only view rendered by GET /roles/{uuid} (VIEW_ROLES). The
 * handler resolves the record `withTrashed`, so a suspended role is viewable
 * here; its status is shown via a badge. Grants are shown grouped by module. The
 * detail chrome + facts styling live in the shared {@see DetailCard}; only the
 * grants section is specific to this page.
 */
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import DetailCard from '@/common/ui/DetailCard.vue';
import StatusBadge from '@/common/ui/StatusBadge.vue';
import { formatDate } from '@/modules/authorization/helpers/formatDate';
import { groupPermissions } from '@/modules/authorization/helpers/groupPermissions';
import type { RoleDetail } from '@/modules/authorization/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    role: RoleDetail;
}>();

const isSuspended = computed<boolean>(() => props.role.deleted_at !== null);
const permissionNames = computed<string[]>(() => props.role.permissions?.map((p) => p.name) ?? []);
const groups = computed(() => groupPermissions(permissionNames.value));
</script>

<template>
    <Head :title="role.name" />

    <DetailCard
        header-title="Role"
        header-subtitle="Role detail"
        permission="VIEW_ROLES"
        fallback-text="You don't have permission to view this role."
        back-href="/roles"
        back-label="Back to roles"
        :title="role.name"
        :columns="4"
        max-width="52rem"
    >
        <template #title-icon>
            <i class="pi pi-shield" aria-hidden="true" />
        </template>
        <template #badges>
            <StatusBadge
                :tone="isSuspended ? 'danger' : 'success'"
                :label="isSuspended ? 'Suspended' : 'Active'"
            />
        </template>

        <dl class="facts">
            <div class="fact">
                <dt>Guard</dt>
                <dd class="mono">{{ role.guard_name }}</dd>
            </div>
            <div class="fact">
                <dt>Permissions</dt>
                <dd>{{ permissionNames.length }}</dd>
            </div>
            <div class="fact">
                <dt>Created</dt>
                <dd>{{ formatDate(role.created_at) }}</dd>
            </div>
            <div class="fact">
                <dt>Last updated</dt>
                <dd>{{ formatDate(role.updated_at ?? null) }}</dd>
            </div>
        </dl>

        <section class="grants">
            <h3 class="grants__title">Granted permissions</h3>

            <p v-if="groups.length === 0" class="grants__empty">
                This role has no permissions assigned.
            </p>

            <div v-else class="grants__groups">
                <div v-for="group in groups" :key="group.module" class="grant-group">
                    <span class="grant-group__label">{{ group.label }}</span>
                    <div class="grant-group__tags">
                        <span v-for="entry in group.entries" :key="entry.name" class="grant-tag">
                            {{ entry.action }}
                        </span>
                    </div>
                </div>
            </div>
        </section>
    </DetailCard>
</template>

<style scoped>
.grants {
    margin-top: var(--space-6);
    border-top: 1px solid var(--border-subtle);
    padding-top: var(--space-5);
}

.grants__title {
    margin: 0 0 var(--space-4);
    font-size: var(--text-base);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
}

.grants__empty {
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-muted);
}

.grants__groups {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}

.grant-group {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.grant-group__label {
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-secondary);
}

.grant-group__tags {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
}

.grant-tag {
    display: inline-flex;
    align-items: center;
    padding: 2px var(--space-3);
    border-radius: var(--radius-sm);
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
    background: color-mix(in srgb, var(--accent-primary) 14%, transparent);
    color: var(--accent-primary);
}
</style>
