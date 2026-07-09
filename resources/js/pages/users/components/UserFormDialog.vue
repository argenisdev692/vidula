<script setup lang="ts">
/**
 * Invite (create) / edit modal for a user. There is no `GET /create` or
 * `GET /{uuid}/edit` route — the backend store/update return `back()` redirects —
 * so the form lives in a Volt Dialog on the Index page and submits via Inertia
 * `useForm`:
 *
 *   · create → POST /users        (InviteUserData — sends an activation link, NO password)
 *   · edit   → PUT  /users/{uuid} (UpdateUserData — never touches the password)
 *
 * `force_password_change` is EDIT-only (the invite DTO has no such field). Optional
 * text fields are normalised '' → null before submit so uniqueness checks and
 * storage stay clean. Client-side Zod validation mirrors the backend; the server
 * stays authoritative and surfaces uniqueness errors through `form.errors`.
 */
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useAuthorization } from '@/modules/auth/composables/useAuthorization';
import TextField from '@/common/form/TextField.vue';
import PhoneField from '@/common/form/PhoneField.vue';
import Dialog from '@/volt/Dialog.vue';
import Button from '@/volt/Button.vue';
import ToggleSwitch from '@/volt/ToggleSwitch.vue';
import { userFormSchema, type UserFormValues } from '@/modules/users/schemas/userFormSchema';
import type { User } from '@/modules/users/types';

const visible = defineModel<boolean>('visible', { default: false });

const props = withDefaults(
    defineProps<{
        mode?: 'create' | 'edit';
        user?: User | null;
        availableRoles?: string[];
        assignableRoles?: string[];
    }>(),
    { mode: 'create', user: null, availableRoles: () => [], assignableRoles: () => [] },
);

const emit = defineEmits<{ saved: [] }>();

const form = useForm<UserFormValues & { roles: string[] }>({
    first_name: '',
    last_name: '',
    email: '',
    username: '',
    phone: null,
    address_2: '',
    force_password_change: false,
    roles: [],
});

const { hasPermission } = useAuthorization();
const canAssignRoles = computed<boolean>(() => hasPermission('ASSIGN_ROLES_USERS'));

const assignableRoleSet = computed<Set<string>>(() => new Set(props.assignableRoles));

function isRoleDisabled(role: string): boolean {
    return !assignableRoleSet.value.has(role);
}

function toggleRole(role: string): void {
    if (isRoleDisabled(role)) {
        return;
    }
    form.roles = form.roles.includes(role)
        ? form.roles.filter((r) => r !== role)
        : [...form.roles, role];
}

const isEdit = computed<boolean>(() => props.mode === 'edit');
const dialogTitle = computed<string>(() => (isEdit.value ? 'Edit user' : 'Invite user'));
const submitLabel = computed<string>(() => (isEdit.value ? 'Save changes' : 'Send invitation'));

/** Re-seed the form each time the dialog opens (never carry stale state). */
watch(visible, (open) => {
    if (!open) {
        return;
    }
    form.clearErrors();
    form.first_name = props.user?.first_name ?? '';
    form.last_name = props.user?.last_name ?? '';
    form.email = props.user?.email ?? '';
    form.username = props.user?.username ?? '';
    form.phone = props.user?.phone ?? null;
    form.address_2 = props.user?.address_2 ?? '';
    form.force_password_change = false;
    form.roles = [];
});

function close(): void {
    visible.value = false;
}

/** Trim + collapse empty optional strings to null for the backend payload. */
function emptyToNull(value: string): string | null {
    const trimmed = value.trim();
    return trimmed === '' ? null : trimmed;
}

function submit(): void {
    const parsed = userFormSchema.safeParse({ ...form.data() });
    if (!parsed.success) {
        form.clearErrors();
        for (const issue of parsed.error.issues) {
            const key = issue.path[0];
            if (typeof key === 'string') {
                form.setError(key as keyof UserFormValues, issue.message);
            }
        }
        return;
    }

    const edit = isEdit.value;
    form.transform((data) => {
        const payload: Record<string, unknown> = {
            first_name: data.first_name.trim(),
            last_name: emptyToNull(data.last_name),
            email: data.email.trim(),
            username: emptyToNull(data.username),
            phone: data.phone ? data.phone.trim() : null,
            address_2: emptyToNull(data.address_2),
        };
        // force_password_change is meaningless on invite (InviteUserData ignores it).
        if (edit) {
            payload.force_password_change = data.force_password_change;
        } else {
            // Roles are invite-only here; the Access panel manages them afterwards.
            payload.roles = data.roles;
        }
        return payload;
    });

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            close();
        },
    };

    if (edit) {
        form.put(`/users/${props.user!.uuid}`, options);
    } else {
        form.post('/users', options);
    }
}
</script>

<template>
    <Dialog
        v-model:visible="visible"
        :header="dialogTitle"
        modal
        :draggable="false"
        :dismissable-mask="!form.processing"
        :style="{ width: '40rem', maxWidth: '96vw' }"
    >
        <form class="user-form" @submit.prevent="submit">
            <div class="user-form__grid">
                <TextField
                    v-model="form.first_name"
                    name="first_name"
                    label="First name"
                    placeholder="e.g. Ada"
                    required
                    :maxlength="255"
                    autocomplete="given-name"
                    :error="form.errors.first_name"
                />
                <TextField
                    v-model="form.last_name"
                    name="last_name"
                    label="Last name"
                    placeholder="e.g. Lovelace"
                    :maxlength="255"
                    autocomplete="family-name"
                    :error="form.errors.last_name"
                />
            </div>

            <TextField
                v-model="form.email"
                name="email"
                label="Email"
                type="email"
                placeholder="name@example.com"
                required
                :maxlength="255"
                autocomplete="email"
                :error="form.errors.email"
                :hint="isEdit ? undefined : 'An activation link will be emailed here — the invitee sets their own password.'"
            />

            <div class="user-form__grid">
                <TextField
                    v-model="form.username"
                    name="username"
                    label="Username"
                    placeholder="Optional"
                    :maxlength="255"
                    autocomplete="username"
                    :error="form.errors.username"
                />
                <PhoneField
                    v-model="form.phone"
                    name="phone"
                    label="Phone"
                    :error="form.errors.phone"
                />
            </div>

            <TextField
                v-model="form.address_2"
                name="address_2"
                label="Address line 2"
                placeholder="Apartment, suite, unit (optional)"
                :maxlength="255"
                autocomplete="address-line2"
                :error="form.errors.address_2"
            />

            <div v-if="!isEdit && canAssignRoles && availableRoles.length" class="user-form__roles">
                <span class="user-form__roles-label">Roles <span class="user-form__optional">(optional)</span></span>
                <span class="user-form__roles-hint">
                    Assign a starter role now — fine-tune permissions later from the user's Access panel.
                </span>
                <div class="user-form__roles-list" role="group" aria-label="Roles">
                    <label
                        v-for="role in availableRoles"
                        :key="role"
                        class="role-chip"
                        :class="{ 'role-chip--disabled': isRoleDisabled(role) }"
                    >
                        <input
                            type="checkbox"
                            class="role-chip__input"
                            :checked="form.roles.includes(role)"
                            :disabled="isRoleDisabled(role)"
                            @change="toggleRole(role)"
                        />
                        <span class="role-chip__box" aria-hidden="true"><i class="pi pi-check" /></span>
                        <span class="role-chip__label">{{ role }}</span>
                    </label>
                </div>
            </div>

            <div v-if="isEdit" class="user-form__toggle">
                <div class="user-form__toggle-copy">
                    <span class="user-form__toggle-label">Force password change</span>
                    <span class="user-form__toggle-hint">
                        Require this user to set a new password on their next sign-in.
                    </span>
                </div>
                <ToggleSwitch
                    v-model="form.force_password_change"
                    input-id="force_password_change"
                    aria-label="Force password change on next sign-in"
                />
            </div>

            <!-- Hidden submit lets Enter submit the form from any field. -->
            <button type="submit" class="user-form__enter" tabindex="-1" aria-hidden="true" />
        </form>

        <template #footer>
            <Button label="Cancel" text severity="secondary" :disabled="form.processing" @click="close" />
            <Button
                type="button"
                :label="submitLabel"
                :icon="form.processing ? undefined : (isEdit ? 'pi pi-check' : 'pi pi-send')"
                :loading="form.processing"
                @click="submit"
            />
        </template>
    </Dialog>
</template>

<style scoped>
.user-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.user-form__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-4);
}

.user-form__toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
    padding: var(--space-4);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--bg-elevated) 40%, transparent);
}

.user-form__toggle-copy {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
}

.user-form__toggle-label {
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--text-primary);
}

.user-form__toggle-hint {
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.user-form__roles {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.user-form__roles-label {
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--text-primary);
}

.user-form__optional {
    font-weight: var(--font-normal);
    color: var(--text-muted);
}

.user-form__roles-hint {
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.user-form__roles-list {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
}

.role-chip {
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

.role-chip:has(.role-chip__input:checked) {
    border-color: var(--accent-primary);
    background: color-mix(in srgb, var(--accent-primary) 12%, transparent);
    color: var(--text-primary);
}

.role-chip--disabled {
    cursor: not-allowed;
    opacity: 0.6;
}

.role-chip__input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.role-chip__box {
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

.role-chip__box .pi {
    font-size: 0.6rem;
}

.role-chip__input:checked + .role-chip__box {
    background: var(--accent-primary);
    border-color: var(--accent-primary);
    color: var(--on-accent, #fff);
}

.role-chip__input:focus-visible + .role-chip__box {
    outline: 2px solid var(--accent-primary);
    outline-offset: 2px;
}

.user-form__enter {
    display: none;
}

@media (max-width: 640px) {
    .user-form__grid {
        grid-template-columns: 1fr;
    }
}
</style>
