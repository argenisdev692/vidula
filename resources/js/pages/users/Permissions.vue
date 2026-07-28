<script setup lang="ts">
/**
 * Combined per-user Access screen (GET /users/{uuid}/permissions): the user's
 * single role (searchable single-select, see RolePicker) on top, and a
 * module-grouped grid of direct-permission top-ups below, each with an instant
 * toggle (PUT /users/{uuid}/permissions). The two sections are independently
 * gated — role by ASSIGN_ROLES_USERS, permissions by ASSIGN_PERMISSIONS_USERS.
 *
 * Three states per permission:
 *   · direct   — granted directly to the user (editable checkbox).
 *   · via role — inherited from a role (checked + locked, "via role" badge). You
 *                cannot remove it here; change the user's roles instead.
 *   · locked   — outside the acting admin's assignable set (checked state honoured,
 *                but disabled with a lock).
 *
 * Toggles are optimistic; the server (AssignableAccess) stays authoritative and a
 * failed toggle is reverted. Route access is gated by ASSIGN_PERMISSIONS_USERS.
 */
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import BackLink from '@/common/ui/BackLink.vue';
import RolePicker from '@/pages/users/components/RolePicker.vue';
import Card from '@/volt/Card.vue';
import { groupPermissions } from '@/modules/authorization/helpers/groupPermissions';
import type { SharedProps } from '@/types/inertia';
import type { UserPermissionsProps } from '@/modules/users/types';

defineOptions({ layout: AppLayout });

const props = defineProps<UserPermissionsProps>();

usePage<SharedProps>();

const fullName = computed<string>(
    () => [props.user.first_name, props.user.last_name].filter(Boolean).join(' ').trim() || props.user.email,
);

const groups = computed(() => groupPermissions(props.availablePermissions));
const roleSet = computed<Set<string>>(() => new Set(props.rolePermissions));
const assignableSet = computed<Set<string>>(() => new Set(props.assignablePermissions));

/** Local, optimistic view of the user's DIRECT grants (reconciled from server). */
const directSet = ref<Set<string>>(new Set(props.directPermissions));
const savingName = ref<string | null>(null);

watch(
    () => props.directPermissions,
    (next) => {
        directSet.value = new Set(next);
    },
);

function isDirect(name: string): boolean {
    return directSet.value.has(name);
}

function isViaRole(name: string): boolean {
    return roleSet.value.has(name);
}

/** Checked = held directly OR inherited from a role. */
function isChecked(name: string): boolean {
    return isDirect(name) || isViaRole(name);
}

/** Role-inherited grants can't be toggled here; nor can grants outside reach. */
function isLocked(name: string): boolean {
    return isViaRole(name) || !assignableSet.value.has(name);
}

function assignedCount(names: string[]): number {
    return names.filter((n) => isChecked(n)).length;
}

function toggle(name: string): void {
    if (isLocked(name) || savingName.value !== null) {
        return;
    }

    const granted = !isDirect(name);
    savingName.value = name;

    // Optimistic update.
    const optimistic = new Set(directSet.value);
    if (granted) {
        optimistic.add(name);
    } else {
        optimistic.delete(name);
    }
    directSet.value = optimistic;

    router.put(
        `/users/${props.user.uuid}/permissions`,
        { permission: name, granted },
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                // Revert the optimistic change on failure.
                const reverted = new Set(directSet.value);
                if (granted) {
                    reverted.delete(name);
                } else {
                    reverted.add(name);
                }
                directSet.value = reverted;
            },
            onFinish: () => {
                savingName.value = null;
            },
        },
    );
}
</script>

<template>
    <Head :title="`Permissions — ${fullName}`" />

    <AppHeader title="Access" :subtitle="`Manage ${fullName}'s role & permissions`" />

    <div class="perms">
        <BackLink :href="`/users/${user.uuid}`" label="Back to user" />

        <PermissionGuard permission="ASSIGN_ROLES_USERS">
            <RolePicker
                :user-uuid="user.uuid"
                :user-roles="userRoles"
                :available-roles="availableRoles"
                :assignable-roles="assignableRoles"
            />
        </PermissionGuard>

        <PermissionGuard permission="ASSIGN_PERMISSIONS_USERS">
            <p class="perms__hint">
                <i class="pi pi-info-circle" aria-hidden="true" />
                Direct grants add to whatever the user already inherits from their role. Permissions
                marked <span class="badge badge--role">via role</span> come from the role and are managed above.
            </p>

            <div class="grid">
                <Card v-for="group in groups" :key="group.module" class="perm-card">
                    <template #content>
                        <header class="card__head">
                            <h3 class="card__title">{{ group.label }}</h3>
                            <span class="card__count">
                                {{ assignedCount(group.entries.map((e) => e.name)) }} / {{ group.entries.length }}
                            </span>
                        </header>

                        <ul class="list">
                            <li v-for="entry in group.entries" :key="entry.name" class="item">
                                <label class="item__label" :class="{ 'item__label--locked': isLocked(entry.name) }">
                                    <input
                                        type="checkbox"
                                        class="item__input"
                                        :checked="isChecked(entry.name)"
                                        :disabled="isLocked(entry.name) || savingName === entry.name"
                                        :aria-label="entry.name"
                                        @change="toggle(entry.name)"
                                    />
                                    <span class="item__box" aria-hidden="true"><i class="pi pi-check" /></span>
                                    <span class="item__text">
                                        <span class="item__action">{{ entry.action }}</span>
                                        <span class="item__name">{{ entry.name }}</span>
                                    </span>
                                </label>

                                <span v-if="isViaRole(entry.name)" class="badge badge--role">via role</span>
                                <span
                                    v-else-if="isLocked(entry.name)"
                                    class="badge badge--locked"
                                    v-tooltip.left="'Outside your assignable set'"
                                >
                                    <i class="pi pi-lock" aria-hidden="true" />
                                </span>
                                <i
                                    v-if="savingName === entry.name"
                                    class="pi pi-spin pi-spinner item__spin"
                                    aria-hidden="true"
                                />
                            </li>
                        </ul>
                    </template>
                </Card>

                <div v-if="groups.length === 0" class="empty">
                    <i class="pi pi-inbox" aria-hidden="true" />
                    <p>No permissions available in the system.</p>
                </div>
            </div>
        </PermissionGuard>
    </div>
</template>

<style scoped>
.perms {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    width: 100%;
    max-width: 52rem;
    margin-inline: auto;
}

.perms__hint {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    flex-wrap: wrap;
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-muted);
}

.grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(20rem, 1fr));
    gap: var(--space-4);
}

.perm-card {
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

.card__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
    margin-bottom: var(--space-3);
    padding-bottom: var(--space-3);
    border-bottom: 1px solid var(--border-subtle);
}

.card__title {
    margin: 0;
    font-size: var(--text-base);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
}

.card__count {
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
    color: var(--text-muted);
    background: color-mix(in srgb, var(--bg-elevated) 50%, transparent);
    padding: 2px var(--space-2);
    border-radius: var(--radius-sm);
}

.list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
}

.item {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-1) 0;
}

.item__label {
    display: inline-flex;
    align-items: center;
    gap: var(--space-3);
    flex: 1;
    cursor: pointer;
    min-width: 0;
}

.item__label--locked {
    cursor: not-allowed;
    opacity: 0.65;
}

.item__input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.item__box {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    flex-shrink: 0;
    border: 1px solid var(--border-default);
    border-radius: var(--radius-sm);
    background: var(--input-bg);
    color: transparent;
    transition: background var(--transition), border-color var(--transition), color var(--transition);
}

.item__box .pi {
    font-size: 0.65rem;
}

.item__input:checked + .item__box {
    background: var(--accent-primary);
    border-color: var(--accent-primary);
    color: var(--on-accent);
}

.item__input:focus-visible + .item__box {
    outline: 2px solid var(--accent-primary);
    outline-offset: 2px;
}

.item__text {
    display: flex;
    flex-direction: column;
    gap: 1px;
    min-width: 0;
}

.item__action {
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--text-primary);
}

.item__name {
    font-size: var(--text-xs);
    color: var(--accent-primary);
    font-family: var(--font-mono, monospace);
    word-break: break-word;
}

.item__spin {
    font-size: 0.8rem;
    color: var(--accent-primary);
}

.badge {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
    padding: 2px var(--space-2);
    border-radius: var(--radius-sm);
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
    white-space: nowrap;
}

.badge--role {
    background: color-mix(in srgb, var(--accent-info) 16%, transparent);
    color: var(--accent-info);
}

.badge--locked {
    background: color-mix(in srgb, var(--bg-elevated) 60%, transparent);
    color: var(--text-muted);
}

.empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-16) var(--space-6);
    color: var(--text-muted);
    grid-column: 1 / -1;
}

.empty .pi {
    font-size: var(--text-3xl);
}

@media (max-width: 640px) {
    .grid {
        grid-template-columns: 1fr;
    }
}
</style>
