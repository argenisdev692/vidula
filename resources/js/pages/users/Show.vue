<script setup lang="ts">
/**
 * User detail — read-only view rendered by GET /users/{uuid} (VIEW_USERS). The
 * handler resolves the record `withTrashed`, so a suspended user is viewable here;
 * lifecycle state is shown via a Tag derived from the same visible signals as the
 * list (helpers/userStatus.ts). Chrome + facts styling live in the shared
 * {@see DetailCard}; the permissions/roles section is specific to this page.
 */
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import DetailCard from '@/common/ui/DetailCard.vue';
import Tag from '@/volt/Tag.vue';
import { formatDate } from '@/modules/users/helpers/formatDate';
import { resolveUserStatus, USER_STATUS_META } from '@/modules/users/helpers/userStatus';
import { groupPermissions } from '@/modules/authorization/helpers/groupPermissions';
import type { UserAccessProps, UserDetail } from '@/modules/users/types';

defineOptions({ layout: AppLayout });

const props = defineProps<
    {
        user: UserDetail;
    } & UserAccessProps
>();

const fullName = computed<string>(
    () => [props.user.first_name, props.user.last_name].filter(Boolean).join(' ').trim() || '—',
);
const status = computed(() => USER_STATUS_META[resolveUserStatus(props.user)]);

/** All permissions the user effectively holds (direct + inherited via role), grouped by module. */
const groups = computed(() => groupPermissions(props.effectivePermissions));
const directSet = computed<Set<string>>(() => new Set(props.directPermissions));

function isDirect(name: string): boolean {
    return directSet.value.has(name);
}
</script>

<template>
    <Head :title="fullName" />

    <DetailCard
        header-title="User"
        header-subtitle="User detail"
        permission="VIEW_USERS"
        fallback-text="You don't have permission to view this user."
        back-href="/users"
        back-label="Back to users"
        :title="fullName"
        :columns="4"
        max-width="52rem"
    >
        <template #title-icon>
            <i class="pi pi-user" aria-hidden="true" />
        </template>
        <template #badges>
            <Tag :value="status.label" :severity="status.severity" />
        </template>

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
                <dt>Role</dt>
                <dd>
                    <span v-if="userRoles.length" class="role-tags">
                        <span v-for="role in userRoles" :key="role" class="role-tag">{{ role }}</span>
                    </span>
                    <span v-else>—</span>
                </dd>
            </div>
        </dl>

        <section class="grants">
            <div class="grants__head">
                <h3 class="grants__title">Permissions</h3>
                <span class="grants__legend">
                    <span class="grant-tag grant-tag--direct"><i class="pi pi-user" aria-hidden="true" /> Direct</span>
                    <span class="grants__legend-hint">others inherited from the role</span>
                </span>
            </div>

            <p v-if="groups.length === 0" class="grants__empty">
                This user has no permissions.
            </p>

            <div v-else class="grants__groups">
                <div v-for="group in groups" :key="group.module" class="grant-group">
                    <span class="grant-group__label">{{ group.label }}</span>
                    <div class="grant-group__tags">
                        <span
                            v-for="entry in group.entries"
                            :key="entry.name"
                            class="grant-tag"
                            :class="{ 'grant-tag--direct': isDirect(entry.name) }"
                        >
                            <i v-if="isDirect(entry.name)" class="pi pi-user grant-tag__mark" aria-hidden="true" />
                            {{ entry.action }}
                        </span>
                    </div>
                </div>
            </div>
        </section>
    </DetailCard>
</template>

<style scoped>
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

.grants {
    margin-top: var(--space-6);
    border-top: 1px solid var(--border-subtle);
    padding-top: var(--space-5);
}

.grants__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: var(--space-3);
    margin-bottom: var(--space-4);
}

.grants__title {
    margin: 0;
    font-size: var(--text-base);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
}

.grants__legend {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.grants__legend-hint {
    font-size: var(--text-xs);
    color: var(--text-muted);
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
    gap: var(--space-1);
    padding: 2px var(--space-3);
    border-radius: var(--radius-sm);
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
    background: color-mix(in srgb, var(--bg-elevated) 55%, transparent);
    color: var(--text-secondary);
    border: 1px solid var(--border-subtle);
}

.grant-tag--direct {
    background: color-mix(in srgb, var(--accent-primary) 14%, transparent);
    color: var(--accent-primary);
    border-color: color-mix(in srgb, var(--accent-primary) 30%, transparent);
}

.grant-tag__mark {
    font-size: 0.6rem;
}
</style>
