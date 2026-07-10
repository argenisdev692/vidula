<script setup lang="ts">
/**
 * Single-role assignment on the Access screen. A user holds AT MOST one role,
 * chosen from a searchable single-select (select2-style); everything beyond it is
 * layered on as direct permissions in the grid below. Submits the choice as a
 * 0/1-length array to PUT /users/{uuid}/roles (sync semantics — clearing the
 * select revokes the role, which the server reflects by reloading `userRoles`).
 *
 * Anti-escalation is enforced server-side (AssignableAccess): an actor may only
 * delegate roles they hold. If the user's CURRENT role sits outside the actor's
 * assignable set, the picker LOCKS — the actor cannot manage a role above them.
 */
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import SelectField from '@/common/form/SelectField.vue';
import Button from '@/volt/Button.vue';
import SecondaryButton from '@/volt/SecondaryButton.vue';
import type { SelectOption } from '@/common/form/types';

const props = defineProps<{
    userUuid: string;
    userRoles: string[];
    availableRoles: string[];
    assignableRoles: string[];
}>();

const currentRole = computed<string | null>(() => props.userRoles[0] ?? null);
const assignableSet = computed<Set<string>>(() => new Set(props.assignableRoles));

/** The user's current role is outside the actor's reach — read-only lock. */
const locked = computed<boolean>(
    () => currentRole.value !== null && !assignableSet.value.has(currentRole.value),
);

/** Only the roles this admin may actually grant become selectable options. */
const roleOptions = computed<SelectOption[]>(() =>
    [...props.assignableRoles]
        .sort((a, b) => a.localeCompare(b))
        .map((role) => ({ label: role, value: role })),
);

const form = useForm<{ roles: string[] }>({ roles: [...props.userRoles] });

/** Flat single-role bridge over the synced-array payload. */
const roleModel = computed<string | null>({
    get: () => form.roles[0] ?? null,
    set: (value) => {
        form.roles = value ? [value] : [];
    },
});

const dirty = computed<boolean>(() => form.isDirty);

// Re-sync (and re-baseline) whenever the server sends back a fresh role after a save.
watch(
    () => props.userRoles,
    (next) => {
        form.defaults({ roles: [...next] });
        form.reset();
        form.clearErrors();
    },
);

function submit(): void {
    form.put(`/users/${props.userUuid}/roles`, { preserveScroll: true });
}

function reset(): void {
    form.reset();
    form.clearErrors();
}
</script>

<template>
    <section class="role" aria-labelledby="role-heading">
        <header class="role__head">
            <h2 id="role-heading" class="role__title">
                <i class="pi pi-id-card" aria-hidden="true" /> Role
            </h2>
            <p class="role__hint">
                Every user holds a single role. Its permissions are inherited automatically — grant
                anything extra as direct permissions below. You can only assign roles you hold yourself.
            </p>
        </header>

        <div class="role__body">
            <SelectField
                v-model="roleModel"
                name="role"
                :options="roleOptions"
                placeholder="Select a role"
                filter
                show-clear
                :disabled="locked || form.processing"
                :error="form.errors.roles"
                :hint="locked ? 'This user’s current role is outside your assignable set.' : undefined"
            />

            <footer class="role__footer">
                <SecondaryButton
                    label="Reset"
                    :disabled="!dirty || form.processing"
                    @click="reset"
                />
                <Button
                    type="button"
                    label="Save role"
                    :icon="form.processing ? undefined : 'pi pi-check'"
                    :loading="form.processing"
                    :disabled="!dirty || locked"
                    @click="submit"
                />
            </footer>
        </div>
    </section>
</template>

<style scoped>
.role {
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

.role__head {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
}

.role__title {
    display: inline-flex;
    align-items: center;
    gap: var(--space-3);
    margin: 0;
    font-size: var(--text-lg);
    font-weight: var(--font-bold);
    color: var(--text-primary);
}

.role__title .pi {
    color: var(--accent-primary);
}

.role__hint {
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-muted);
}

.role__body {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    max-width: 26rem;
}

.role__footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: var(--space-3);
}

@media (max-width: 640px) {
    .role {
        padding: var(--space-5) var(--space-4);
    }

    .role__body {
        max-width: none;
    }
}
</style>
