<script setup lang="ts">
/**
 * Access management for a single user — roles + direct permission top-ups.
 * Submits the FULL desired set (sync semantics) to PUT /users/{uuid}/access via
 * Inertia `useForm`. Mirrors the Juan/Carlos model: a role bundle plus optional
 * per-user direct grants.
 *
 * Anti-escalation is enforced server-side (AssignableAccess): an actor may only
 * delegate access they hold. This panel reflects that by DISABLING roles/perms
 * outside `assignableRoles` / `assignablePermissions`, and — so a delegated admin
 * never silently revokes grants they cannot manage — it PRESERVES any currently
 * held role/permission that falls outside their assignable set in the payload.
 */
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Button from '@/volt/Button.vue';
import PermissionPicker from '@/pages/roles/components/PermissionPicker.vue';
import type { UserAccessProps } from '@/modules/users/types';

const props = defineProps<UserAccessProps & { userUuid: string }>();

interface AccessForm {
    roles: string[];
    direct_permissions: string[];
    [key: string]: string[];
}

const form = useForm<AccessForm>({
    roles: [...props.userRoles],
    direct_permissions: [...props.directPermissions],
});

/** Roles to render — the assignable catalogue plus any held role outside it. */
const roleOptions = computed<string[]>(() => {
    const set = new Set<string>([...props.availableRoles, ...props.userRoles]);
    return [...set].sort((a, b) => a.localeCompare(b));
});

const assignableRoleSet = computed<Set<string>>(() => new Set(props.assignableRoles));
const assignablePermissionSet = computed<Set<string>>(() => new Set(props.assignablePermissions));

/** Held grants the actor cannot manage — preserved verbatim on submit. */
const lockedRoles = computed<string[]>(() =>
    props.userRoles.filter((r) => !assignableRoleSet.value.has(r)),
);
const lockedPermissions = computed<string[]>(() =>
    props.directPermissions.filter((p) => !assignablePermissionSet.value.has(p)),
);

function isRoleDisabled(role: string): boolean {
    return !assignableRoleSet.value.has(role);
}

function isRoleChecked(role: string): boolean {
    return form.roles.includes(role);
}

function toggleRole(role: string): void {
    if (isRoleDisabled(role)) {
        return;
    }
    form.roles = isRoleChecked(role)
        ? form.roles.filter((r) => r !== role)
        : [...form.roles, role];
}

/** Re-seed if the server props change (e.g. after a successful sync reload). */
watch(
    () => [props.userRoles, props.directPermissions],
    () => {
        form.roles = [...props.userRoles];
        form.direct_permissions = [...props.directPermissions];
        form.clearErrors();
    },
);

const dirty = computed<boolean>(() => form.isDirty);
const savedFlash = ref<boolean>(false);

function submit(): void {
    form
        .transform((data) => ({
            roles: [...new Set([...(data.roles as string[]), ...lockedRoles.value])],
            direct_permissions: [
                ...new Set([...(data.direct_permissions as string[]), ...lockedPermissions.value]),
            ],
        }))
        .put(`/users/${props.userUuid}/access`, {
            preserveScroll: true,
            onSuccess: () => {
                savedFlash.value = true;
                window.setTimeout(() => (savedFlash.value = false), 2500);
            },
        });
}

function reset(): void {
    form.roles = [...props.userRoles];
    form.direct_permissions = [...props.directPermissions];
    form.clearErrors();
}
</script>

<template>
    <section class="access" aria-labelledby="access-heading">
        <header class="access__head">
            <h2 id="access-heading" class="access__title">
                <i class="pi pi-shield" aria-hidden="true" /> Access
            </h2>
            <p class="access__hint">
                Assign role bundles and fine-tune with direct permission grants. You can only
                delegate access you hold yourself.
            </p>
        </header>

        <!-- Roles -->
        <div class="access__block">
            <span class="access__label">Roles</span>
            <div class="roles" role="group" aria-label="Roles">
                <label
                    v-for="role in roleOptions"
                    :key="role"
                    class="chip"
                    :class="{ 'chip--disabled': isRoleDisabled(role) }"
                >
                    <input
                        type="checkbox"
                        class="chip__input"
                        :checked="isRoleChecked(role)"
                        :disabled="isRoleDisabled(role)"
                        @change="toggleRole(role)"
                    />
                    <span class="chip__box" aria-hidden="true"><i class="pi pi-check" /></span>
                    <span class="chip__label">{{ role }}</span>
                    <i
                        v-if="isRoleDisabled(role)"
                        class="pi pi-lock chip__lock"
                        aria-hidden="true"
                        v-tooltip.top="'Outside your assignable set'"
                    />
                </label>
            </div>
            <p v-if="form.errors.roles" class="access__error" role="alert">{{ form.errors.roles }}</p>
        </div>

        <!-- Direct permissions -->
        <div class="access__block">
            <span class="access__label">Direct permissions</span>
            <p class="access__sublabel">
                Extra grants on top of the roles above (an empty set revokes every direct grant).
            </p>
            <PermissionPicker v-model="form.direct_permissions" :available="assignablePermissions" />
            <p v-if="lockedPermissions.length" class="access__note">
                <i class="pi pi-info-circle" aria-hidden="true" />
                {{ lockedPermissions.length }} direct grant(s) outside your reach are preserved.
            </p>
            <p v-if="form.errors.direct_permissions" class="access__error" role="alert">
                {{ form.errors.direct_permissions }}
            </p>
        </div>

        <footer class="access__footer">
            <span v-if="savedFlash" class="access__saved">
                <i class="pi pi-check-circle" aria-hidden="true" /> Saved
            </span>
            <Button
                label="Reset"
                text
                severity="secondary"
                :disabled="!dirty || form.processing"
                @click="reset"
            />
            <Button
                type="button"
                label="Save access"
                :icon="form.processing ? undefined : 'pi pi-check'"
                :loading="form.processing"
                :disabled="!dirty"
                @click="submit"
            />
        </footer>
    </section>
</template>

<style scoped>
.access {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
    background: color-mix(in srgb, var(--bg-surface) 60%, transparent);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-2xl);
    padding: var(--space-6) var(--space-8);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

.access__head {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
}

.access__title {
    display: inline-flex;
    align-items: center;
    gap: var(--space-3);
    margin: 0;
    font-size: var(--text-lg);
    font-weight: var(--font-bold);
    color: var(--text-primary);
}

.access__title .pi {
    color: var(--accent-primary);
}

.access__hint {
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-muted);
}

.access__block {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.access__label {
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
    color: var(--text-secondary);
}

.access__sublabel {
    margin: 0;
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.roles {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
}

.chip {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-2) var(--space-3);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--bg-elevated) 40%, transparent);
    cursor: pointer;
    font-size: var(--text-sm);
    color: var(--text-secondary);
    transition: border-color var(--transition), background var(--transition);
}

.chip:has(.chip__input:checked) {
    border-color: var(--accent-primary);
    background: color-mix(in srgb, var(--accent-primary) 12%, transparent);
    color: var(--text-primary);
}

.chip--disabled {
    cursor: not-allowed;
    opacity: 0.6;
}

.chip__input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.chip__box {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    height: 16px;
    flex-shrink: 0;
    border: 1px solid var(--border-default);
    border-radius: var(--radius-sm);
    background: var(--input-bg);
    color: transparent;
    transition: background var(--transition), border-color var(--transition), color var(--transition);
}

.chip__box .pi {
    font-size: 0.6rem;
}

.chip__input:checked + .chip__box {
    background: var(--accent-primary);
    border-color: var(--accent-primary);
    color: var(--on-accent, #fff);
}

.chip__input:focus-visible + .chip__box {
    outline: 2px solid var(--accent-primary);
    outline-offset: 2px;
}

.chip__lock {
    font-size: 0.65rem;
    color: var(--text-muted);
}

.access__note {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    margin: 0;
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.access__error {
    margin: 0;
    font-size: var(--text-xs);
    color: var(--accent-error);
}

.access__footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: var(--space-3);
}

.access__saved {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    margin-right: auto;
    font-size: var(--text-sm);
    color: var(--accent-success);
}

@media (max-width: 640px) {
    .access {
        padding: var(--space-5) var(--space-4);
    }
}
</style>
