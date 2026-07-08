<script setup lang="ts">
/**
 * Role detail — read-only view rendered by GET /roles/{uuid} (VIEW_ROLES). The
 * handler resolves the record `withTrashed`, so a suspended role is viewable
 * here; its status is shown via a badge. Grants are shown grouped by module.
 */
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import { formatDate } from '@/modules/authorization/helpers/formatDate';
import { groupPermissions } from '@/modules/authorization/helpers/groupPermissions';
import type { SharedProps } from '@/types/inertia';
import type { RoleDetail } from '@/modules/authorization/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    role: RoleDetail;
}>();

usePage<SharedProps>();

const isSuspended = computed<boolean>(() => props.role.deleted_at !== null);
const permissionNames = computed<string[]>(() => props.role.permissions?.map((p) => p.name) ?? []);
const groups = computed(() => groupPermissions(permissionNames.value));
</script>

<template>
    <Head :title="role.name" />

    <AppHeader title="Role" subtitle="Role detail" />

    <PermissionGuard permission="VIEW_ROLES">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to view this role.</p>
            </div>
        </template>

        <div class="detail">
            <Link href="/roles" class="back" aria-label="Back to roles">
                <i class="pi pi-arrow-left" aria-hidden="true" /> Back to roles
            </Link>

            <article class="card">
                <div class="card__head">
                    <h2 class="card__title">
                        <i class="pi pi-shield" aria-hidden="true" />
                        {{ role.name }}
                    </h2>
                    <span class="badge" :class="isSuspended ? 'badge--suspended' : 'badge--active'">
                        {{ isSuspended ? 'Suspended' : 'Active' }}
                    </span>
                </div>

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
            </article>
        </div>
    </PermissionGuard>
</template>

<style scoped>
.detail {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    max-width: 52rem;
}

.back {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--text-secondary);
    transition: color var(--transition);
    width: fit-content;
}

.back:hover {
    color: var(--accent-primary);
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
    font-size: var(--text-xl);
    font-weight: var(--font-bold);
    color: var(--text-primary);
}

.card__title .pi {
    color: var(--accent-primary);
}

.facts {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: var(--space-5);
    margin: 0 0 var(--space-6);
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
}

.badge--active {
    background: color-mix(in srgb, var(--accent-success) 18%, transparent);
    color: var(--accent-success);
}

.badge--suspended {
    background: color-mix(in srgb, var(--accent-error) 18%, transparent);
    color: var(--accent-error);
}

.grants {
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
