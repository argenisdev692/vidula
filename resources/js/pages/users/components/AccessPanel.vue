<script setup lang="ts">
/**
 * Role assignment for a single user. Submits the FULL desired role set (sync
 * semantics) to PUT /users/{uuid}/roles via Inertia `useForm`. Direct permission
 * top-ups live on their own dedicated screen (see users/Permissions.vue).
 *
 * Anti-escalation is enforced server-side (AssignableAccess): an actor may only
 * delegate roles they hold. This panel reflects that by DISABLING roles outside
 * `assignableRoles`, and — so a delegated admin never silently revokes a role they
 * cannot manage — it PRESERVES any held role outside their assignable set on submit.
 */
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Button from '@/volt/Button.vue';

const props = defineProps<{
    userUuid: string;
    userRoles: string[];
    availableRoles: string[];
    assignableRoles: string[];
}>();

const form = useForm<{ roles: string[] }>({ roles: [...props.userRoles] });

/** Roles to render — the assignable catalogue plus any held role outside it. */
const roleOptions = computed<string[]>(() => {
    const set = new Set<string>([...props.availableRoles, ...props.userRoles]);
    return [...set].sort((a, b) => a.localeCompare(b));
});

const assignableRoleSet = computed<Set<string>>(() => new Set(props.assignableRoles));

/** Held roles the actor cannot manage — preserved verbatim on submit. */
const lockedRoles = computed<string[]>(() =>
    props.userRoles.filter((r) => !assignableRoleSet.value.has(r)),
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

watch(
    () => props.userRoles,
    () => {
        form.roles = [...props.userRoles];
        form.clearErrors();
    },
);

const dirty = computed<boolean>(() => form.isDirty);

function submit(): void {
    form
        .transform((data) => ({
            roles: [...new Set([...data.roles, ...lockedRoles.value])],
        }))
        .put(`/users/${props.userUuid}/roles`, { preserveScroll: true });
}

function reset(): void {
    form.roles = [...props.userRoles];
    form.clearErrors();
}
</script>

<template>
    <section class="access" aria-labelledby="access-heading">
        <header class="access__head">
            <h2 id="access-heading" class="access__title">
                <i class="pi pi-users" aria-hidden="true" /> Roles
            </h2>
            <p class="access__hint">
                Assign role bundles. You can only delegate roles you hold yourself — fine-tune
                individual permissions from “Manage permissions”.
            </p>
        </header>

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

        <footer class="access__footer">
            <Button
                label="Reset"
                text
                severity="secondary"
                :disabled="!dirty || form.processing"
                @click="reset"
            />
            <Button
                type="button"
                label="Save roles"
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

@media (max-width: 640px) {
    .access {
        padding: var(--space-5) var(--space-4);
    }
}
</style>
