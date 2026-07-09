<script setup lang="ts">
/**
 * Shared invite (create) / edit user form — the single source of truth behind the
 * dedicated Create.vue and Edit.vue pages (no modal). Owns its Inertia `useForm`
 * and submits:
 *
 *   · create → POST /users        (InviteUserData — sends an activation link, NO password)
 *   · edit   → PUT  /users/{uuid} (UpdateUserData — never touches the password)
 *
 * On success the backend redirects to the users list, so Inertia navigates there
 * on its own. The form is flat (string fields, '' when empty); optional strings
 * are normalised '' → null before submit. The address block (street + line 2 +
 * managed city/state/zip/country + silent lat/lng) is delegated to the reusable
 * AddressAutocomplete via a single AddressValue bridge — profile photo is
 * intentionally out of scope (a self-service concern).
 *
 * `force_password_change` is EDIT-only; role seeding is CREATE-only (afterwards
 * roles are managed from the user's Access panel). Client-side Zod validation
 * mirrors the backend; the server stays authoritative on uniqueness.
 */
import { computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { useAuthorization } from '@/modules/auth/composables/useAuthorization';
import TextField from '@/common/form/TextField.vue';
import SelectField from '@/common/form/SelectField.vue';
import DateField from '@/common/form/DateField.vue';
import PhoneField from '@/common/form/PhoneField.vue';
import SubmitButton from '@/common/form/SubmitButton.vue';
import AddressAutocomplete from '@/common/address/AddressAutocomplete.vue';
import SecondaryButton from '@/volt/SecondaryButton.vue';
import ToggleSwitch from '@/volt/ToggleSwitch.vue';
import type { AddressValue } from '@/common/address/types';
import type { SharedProps } from '@/types/inertia';
import { genderOptions } from '@/modules/profile/helpers/genderOptions';
import { userFormSchema, type UserFormValues } from '@/modules/users/schemas/userFormSchema';
import type { UserEditData } from '@/modules/users/types';

const props = withDefaults(
    defineProps<{
        mode: 'create' | 'edit';
        user?: UserEditData | null;
        availableRoles?: string[];
        assignableRoles?: string[];
    }>(),
    { user: null, availableRoles: () => [], assignableRoles: () => [] },
);

const isEdit = computed<boolean>(() => props.mode === 'edit');
const page = usePage<SharedProps>();
const today = new Date().toISOString().slice(0, 10);

const form = useForm<UserFormValues>({
    first_name: props.user?.first_name ?? '',
    last_name: props.user?.last_name ?? '',
    username: props.user?.username ?? '',
    email: props.user?.email ?? '',
    phone: props.user?.phone ?? '',
    date_of_birth: (props.user?.date_of_birth ?? '').slice(0, 10),
    gender: props.user?.gender ?? '',
    address: props.user?.address ?? '',
    address_2: props.user?.address_2 ?? '',
    zip_code: props.user?.zip_code ?? '',
    city: props.user?.city ?? '',
    state: props.user?.state ?? '',
    country: props.user?.country ?? '',
    latitude: props.user?.latitude ?? null,
    longitude: props.user?.longitude ?? null,
    roles: [],
    force_password_change: props.user?.must_change_password ?? false,
});

/* ── Roles (create only) ──────────────────────────────────────────────── */
const { hasPermission } = useAuthorization();
const canAssignRoles = computed<boolean>(() => hasPermission('ASSIGN_ROLES_USERS'));
const showRoles = computed<boolean>(() => !isEdit.value && canAssignRoles.value && props.availableRoles.length > 0);

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

/* ── Field bridges (flat form ⇄ component value objects) ───────────────── */
const phoneModel = computed<string | null>({
    get: () => form.phone || null,
    set: (value) => {
        form.phone = value ?? '';
    },
});

const addressModel = computed<AddressValue>({
    get: () => ({
        address: form.address || null,
        address_2: form.address_2 || null,
        zip_code: form.zip_code || null,
        city: form.city || null,
        state: form.state || null,
        country: form.country || null,
        latitude: form.latitude,
        longitude: form.longitude,
    }),
    set: (value) => {
        form.address = value.address ?? '';
        form.address_2 = value.address_2 ?? '';
        form.zip_code = value.zip_code ?? '';
        form.city = value.city ?? '';
        form.state = value.state ?? '';
        form.country = value.country ?? '';
        form.latitude = value.latitude;
        form.longitude = value.longitude;
    },
});

const addressErrors = computed(() => ({
    address: form.errors.address,
    address_2: form.errors.address_2,
    zip_code: form.errors.zip_code,
    city: form.errors.city,
    state: form.errors.state,
    country: form.errors.country,
}));

/* ── Submit ───────────────────────────────────────────────────────────── */
const submitLabel = computed<string>(() => (isEdit.value ? 'Save changes' : 'Send invitation'));
const submitIcon = computed<string>(() => (isEdit.value ? 'pi pi-check' : 'pi pi-send'));

/** Trim + collapse empty optional strings to null for the backend payload. */
function emptyToNull(value: string): string | null {
    const trimmed = value.trim();
    return trimmed === '' ? null : trimmed;
}

function submit(): void {
    const parsed = userFormSchema.safeParse(form.data());
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
            phone: emptyToNull(data.phone),
            date_of_birth: emptyToNull(data.date_of_birth),
            gender: emptyToNull(data.gender),
            address: emptyToNull(data.address),
            address_2: emptyToNull(data.address_2),
            zip_code: emptyToNull(data.zip_code),
            city: emptyToNull(data.city),
            state: emptyToNull(data.state),
            country: emptyToNull(data.country),
            latitude: data.latitude,
            longitude: data.longitude,
        };
        if (edit) {
            payload.force_password_change = data.force_password_change;
        } else {
            payload.roles = data.roles;
        }
        return payload;
    });

    const options = { preserveScroll: true };
    if (edit) {
        form.put(`/users/${props.user!.uuid}`, options);
    } else {
        form.post('/users', options);
    }
}
</script>

<template>
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
                v-model="phoneModel"
                name="phone"
                label="Phone"
                default-country="US"
                :error="form.errors.phone"
            />
            <DateField
                v-model="form.date_of_birth"
                name="date_of_birth"
                label="Date of birth"
                placeholder="Select date of birth"
                :max-date="today"
                :error="form.errors.date_of_birth"
            />

            <SelectField
                v-model="form.gender"
                name="gender"
                label="Gender"
                placeholder="Select gender"
                show-clear
                :options="genderOptions"
                :error="form.errors.gender"
            />

            <div class="user-form__full">
                <AddressAutocomplete
                    v-model="addressModel"
                    :api-key="page.props.config.google_maps_api_key"
                    :errors="addressErrors"
                />
            </div>
        </div>

        <!-- Starter roles (invite only) -->
        <div v-if="showRoles" class="user-form__roles">
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

        <!-- Force password change (edit only) -->
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

        <div class="user-form__actions">
            <SecondaryButton
                type="button"
                label="Cancel"
                :disabled="form.processing"
                @click="router.visit('/users')"
            />
            <SubmitButton :label="submitLabel" :icon="submitIcon" :loading="form.processing" />
        </div>
    </form>
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

.user-form__full {
    grid-column: 1 / -1;
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

.user-form__actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: var(--space-3);
    margin-top: var(--space-2);
}

@media (max-width: 640px) {
    .user-form__grid {
        grid-template-columns: 1fr;
    }
}
</style>
