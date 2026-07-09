<script setup lang="ts">
/**
 * User detail — read-only view rendered by GET /users/{uuid} (VIEW_USERS). The
 * handler resolves the record `withTrashed`, so a suspended user is viewable here;
 * lifecycle state is shown via a badge derived from the same visible signals as
 * the list (helpers/userStatus.ts).
 */
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import BackLink from '@/common/ui/BackLink.vue';
import AccessPanel from '@/pages/users/components/AccessPanel.vue';
import { formatDate } from '@/modules/users/helpers/formatDate';
import { resolveUserStatus, USER_STATUS_META } from '@/modules/users/helpers/userStatus';
import type { SharedProps } from '@/types/inertia';
import type { UserAccessProps, UserDetail } from '@/modules/users/types';

defineOptions({ layout: AppLayout });

const props = defineProps<
    {
        user: UserDetail;
    } & UserAccessProps
>();

usePage<SharedProps>();

const fullName = computed<string>(
    () => [props.user.first_name, props.user.last_name].filter(Boolean).join(' ').trim() || '—',
);
const status = computed(() => USER_STATUS_META[resolveUserStatus(props.user)]);
</script>

<template>
    <Head :title="fullName" />

    <AppHeader title="User" subtitle="User detail" />

    <PermissionGuard permission="VIEW_USERS">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to view this user.</p>
            </div>
        </template>

        <div class="detail">
            <BackLink href="/users" label="Back to users" />

            <article class="card">
                <div class="card__head">
                    <h2 class="card__title">
                        <i class="pi pi-user" aria-hidden="true" />
                        {{ fullName }}
                    </h2>
                    <span class="badge" :class="status.className">{{ status.label }}</span>
                </div>

                <dl class="facts">
                    <div class="fact">
                        <dt>Email</dt>
                        <dd class="mono">{{ user.email }}</dd>
                    </div>
                    <div class="fact">
                        <dt>Username</dt>
                        <dd class="mono">{{ user.username ?? '—' }}</dd>
                    </div>
                    <div class="fact">
                        <dt>Phone</dt>
                        <dd class="mono">{{ user.phone ?? '—' }}</dd>
                    </div>
                    <div class="fact">
                        <dt>Address line 2</dt>
                        <dd>{{ user.address_2 ?? '—' }}</dd>
                    </div>
                    <div class="fact">
                        <dt>Email verified</dt>
                        <dd>{{ formatDate(user.email_verified_at) }}</dd>
                    </div>
                    <div class="fact">
                        <dt>Must change password</dt>
                        <dd>{{ user.must_change_password ? 'Yes' : 'No' }}</dd>
                    </div>
                    <div class="fact">
                        <dt>Invited</dt>
                        <dd>{{ formatDate(user.invited_at ?? null) }}</dd>
                    </div>
                    <div class="fact">
                        <dt>Created</dt>
                        <dd>{{ formatDate(user.created_at) }}</dd>
                    </div>
                    <div class="fact fact--wide">
                        <dt>Roles</dt>
                        <dd>
                            <span v-if="userRoles.length" class="role-tags">
                                <span v-for="role in userRoles" :key="role" class="role-tag">{{ role }}</span>
                            </span>
                            <span v-else>—</span>
                        </dd>
                    </div>
                    <div class="fact fact--wide">
                        <dt>Direct permissions</dt>
                        <dd>
                            <span v-if="directPermissions.length" class="role-tags">
                                <span v-for="perm in directPermissions" :key="perm" class="perm-tag">{{ perm }}</span>
                            </span>
                            <span v-else>—</span>
                        </dd>
                    </div>
                </dl>
            </article>

            <PermissionGuard permission="ASSIGN_ROLES_USERS">
                <AccessPanel
                    :user-uuid="user.uuid"
                    :user-roles="userRoles"
                    :available-roles="availableRoles"
                    :assignable-roles="assignableRoles"
                />
            </PermissionGuard>

            <PermissionGuard permission="ASSIGN_PERMISSIONS_USERS">
                <Link :href="`/users/${user.uuid}/permissions`" class="manage-perms">
                    <i class="pi pi-shield" aria-hidden="true" />
                    <span>
                        <span class="manage-perms__title">Manage permissions</span>
                        <span class="manage-perms__hint">Grant individual permissions independent of roles</span>
                    </span>
                    <i class="pi pi-arrow-right manage-perms__go" aria-hidden="true" />
                </Link>
            </PermissionGuard>
        </div>
    </PermissionGuard>
</template>

<style scoped>
.detail {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    width: 100%;
    max-width: 52rem;
    margin-inline: auto;
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
    word-break: break-word;
}

.mono {
    font-family: var(--font-mono, monospace);
}

.fact--wide {
    grid-column: 1 / -1;
}

.role-tags {
    display: inline-flex;
    flex-wrap: wrap;
    gap: var(--space-2);
}

.role-tag {
    display: inline-flex;
    align-items: center;
    padding: 2px var(--space-2);
    border-radius: var(--radius-sm);
    background: color-mix(in srgb, var(--accent-primary) 14%, transparent);
    color: var(--accent-primary);
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
    font-family: var(--font-mono, monospace);
}

.perm-tag {
    display: inline-flex;
    align-items: center;
    padding: 2px var(--space-2);
    border-radius: var(--radius-sm);
    background: color-mix(in srgb, var(--accent-info) 14%, transparent);
    color: var(--accent-info);
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
    font-family: var(--font-mono, monospace);
}

.manage-perms {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    padding: var(--space-4) var(--space-6);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-2xl);
    background: color-mix(in srgb, var(--bg-surface) 60%, transparent);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    transition: border-color var(--transition), background var(--transition);
}

.manage-perms:hover {
    border-color: color-mix(in srgb, var(--accent-primary) 55%, transparent);
    background: color-mix(in srgb, var(--accent-primary) 8%, transparent);
}

.manage-perms > .pi-shield {
    font-size: var(--text-xl);
    color: var(--accent-primary);
}

.manage-perms > span {
    display: flex;
    flex-direction: column;
    gap: 2px;
    flex: 1;
}

.manage-perms__title {
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
}

.manage-perms__hint {
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.manage-perms__go {
    color: var(--text-muted);
    transition: transform var(--transition);
}

.manage-perms:hover .manage-perms__go {
    transform: translateX(3px);
    color: var(--accent-primary);
}

.badge {
    display: inline-flex;
    align-items: center;
    padding: 2px var(--space-3);
    border-radius: var(--radius-sm);
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
}

.badge--pending {
    background: color-mix(in srgb, var(--accent-warning) 18%, transparent);
    color: var(--accent-warning);
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

@media (max-width: 900px) {
    .facts {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .facts {
        grid-template-columns: 1fr;
    }

    .card {
        padding: var(--space-5) var(--space-4);
    }
}
</style>
