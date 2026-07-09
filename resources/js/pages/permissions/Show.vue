<script setup lang="ts">
/**
 * Permission detail — read-only view rendered by GET /permissions/{uuid}
 * (VIEW_PERMISSIONS). The handler resolves the record `withTrashed`, so a
 * suspended permission is viewable here; its status is shown via a badge.
 */
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import BackLink from '@/common/ui/BackLink.vue';
import { formatDate } from '@/modules/authorization/helpers/formatDate';
import { groupPermissions } from '@/modules/authorization/helpers/groupPermissions';
import type { SharedProps } from '@/types/inertia';
import type { PermissionDetail } from '@/modules/authorization/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    permission: PermissionDetail;
}>();

usePage<SharedProps>();

const isSuspended = computed<boolean>(() => props.permission.deleted_at !== null);

/** Split the name into its module + action for a friendlier summary. */
const parsed = computed(() => groupPermissions([props.permission.name])[0]);
const moduleLabel = computed<string>(() => parsed.value?.label ?? '—');
const actionLabel = computed<string>(() => parsed.value?.entries[0]?.action ?? '—');
</script>

<template>
    <Head :title="permission.name" />

    <AppHeader title="Permission" subtitle="Permission detail" />

    <PermissionGuard permission="VIEW_PERMISSIONS">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to view this permission.</p>
            </div>
        </template>

        <div class="detail">
            <BackLink href="/permissions" label="Back to permissions" />

            <article class="card">
                <div class="card__head">
                    <h2 class="card__title">
                        <i class="pi pi-key" aria-hidden="true" />
                        {{ permission.name }}
                    </h2>
                    <span class="badge" :class="isSuspended ? 'badge--suspended' : 'badge--active'">
                        {{ isSuspended ? 'Suspended' : 'Active' }}
                    </span>
                </div>

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
            </article>
        </div>
    </PermissionGuard>
</template>

<style scoped>
.detail {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    max-width: 48rem;
}

.card {
    background: color-mix(in srgb, var(--bg-surface) 60%, transparent);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-2xl);
    padding: var(--space-6) var(--space-8);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

.card__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
    margin-bottom: var(--space-5);
}

.card__title {
    display: inline-flex;
    align-items: center;
    gap: var(--space-3);
    margin: 0;
    font-family: var(--font-mono, monospace);
    font-size: var(--text-lg);
    font-weight: var(--font-bold);
    color: var(--text-primary);
    word-break: break-word;
}

.card__title .pi {
    color: var(--accent-info);
}

.facts {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: var(--space-5);
    margin: 0;
}

.fact dt {
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--text-muted);
    margin-bottom: var(--space-1);
}

.fact dd {
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-primary);
}

.mono {
    font-family: var(--font-mono, monospace);
}

.badge {
    display: inline-flex;
    align-items: center;
    padding: 2px var(--space-3);
    border-radius: var(--radius-sm);
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
    flex-shrink: 0;
}

.badge--active {
    background: color-mix(in srgb, var(--accent-success) 18%, transparent);
    color: var(--accent-success);
}

.badge--suspended {
    background: color-mix(in srgb, var(--accent-error) 18%, transparent);
    color: var(--accent-error);
}

.empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-16) var(--space-6);
    color: var(--text-muted);
}

.empty .pi {
    font-size: var(--text-3xl);
}

@media (max-width: 640px) {
    .facts {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .card {
        padding: var(--space-5) var(--space-4);
    }
}
</style>
