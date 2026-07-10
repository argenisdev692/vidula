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
 * the role is managed from the user's Access screen). Client-side Zod validation
 * mirrors the backend; the server stays authoritative on uniqueness.
 */
import { computed, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { useAuthorization } from '@/modules/auth/composables/useAuthorization';
import { useFieldAvailability } from '@/common/composables/useFieldAvailability';
import { isValidUsername, sanitizeUsername, USERNAME_MAX, USERNAME_MIN, USERNAME_RULE_MESSAGE } from '@/common/form/username';
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
import type { SelectOption } from '@/common/form/types';
import type { UserEditData } from '@/modules/users/types';
import { toLocalIsoDate } from '@/lib/date';

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
const today = toLocalIsoDate(new Date());

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
    country_code: props.user?.country_code ?? '',
    latitude: props.user?.latitude ?? null,
    longitude: props.user?.longitude ?? null,
    roles: [],
    force_password_change: props.user?.must_change_password ?? false,
});

/* ── Names — letters only, no spaces, auto-capitalized first letter ─────── */
/** Strip everything but Unicode letters, then capitalize the first letter. */
function toNameValue(value: string): string {
    const letters = value.replace(/[^\p{L}]/gu, '');
    return letters ? letters.charAt(0).toUpperCase() + letters.slice(1) : '';
}

const firstNameModel = computed<string>({
    get: () => form.first_name,
    set: (value) => {
        form.first_name = toNameValue(value);
    },
});

const lastNameModel = computed<string>({
    get: () => form.last_name,
    set: (value) => {
        form.last_name = toNameValue(value);
    },
});

/* ── Role (create only) — a single role, submitted as a 0/1-length array ── */
const { hasPermission } = useAuthorization();
const canAssignRoles = computed<boolean>(() => hasPermission('ASSIGN_ROLES_USERS'));

/** Only the roles this admin may actually grant become selectable options. */
const roleOptions = computed<SelectOption[]>(() =>
    props.assignableRoles.map((role) => ({ label: role, value: role })),
);

const showRoles = computed<boolean>(() => !isEdit.value && canAssignRoles.value && roleOptions.value.length > 0);

const roleModel = computed<string | null>({
    get: () => form.roles[0] ?? null,
    set: (value) => {
        form.roles = value ? [value] : [];
    },
});

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
        country_code: form.country_code || null,
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
        // Backend requires uppercase ISO-3166 alpha-2; normalise manual entry too.
        form.country_code = (value.country_code ?? '').toUpperCase();
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
    country_code: form.errors.country_code,
}));

/* ── Realtime username / email availability (color check) ──────────────── */
/** On edit the endpoint excludes the edited user so their own value stays free. */
const ignoreParams = computed<Record<string, string>>(() => {
    const params: Record<string, string> = {};
    if (isEdit.value && props.user?.uuid) {
        params.ignore = props.user.uuid;
    }
    return params;
});

const emailPattern = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;

const { status: usernameStatus } = useFieldAvailability({
    field: 'username',
    endpoint: '/users/availability',
    source: () => form.username,
    original: () => props.user?.username,
    extraParams: () => ignoreParams.value,
    validate: isValidUsername,
});

const { status: emailStatus } = useFieldAvailability({
    field: 'email',
    endpoint: '/users/availability',
    source: () => form.email,
    original: () => props.user?.email,
    extraParams: () => ignoreParams.value,
    validate: (value) => emailPattern.test(value),
});

const usernameSuccess = computed<string | undefined>(() =>
    usernameStatus.value === 'available' ? '✓ Username is available' : undefined,
);
const usernameError = computed<string | undefined>(() => {
    if (form.errors.username) {
        return form.errors.username;
    }
    if (usernameStatus.value === 'taken') {
        return 'This username is already taken';
    }
    return usernameStatus.value === 'invalid' ? USERNAME_RULE_MESSAGE : undefined;
});

const emailSuccess = computed<string | undefined>(() =>
    emailStatus.value === 'available' ? '✓ Email is available' : undefined,
);
const emailError = computed<string | undefined>(() => {
    if (form.errors.email) {
        return form.errors.email;
    }
    return emailStatus.value === 'taken' ? 'This email is already registered' : undefined;
});
const emailFieldHint = computed<string | undefined>(() => {
    if (emailStatus.value === 'checking') {
        return 'Checking availability…';
    }
    return isEdit.value ? undefined : 'An activation link will be emailed here — the invitee sets their own password.';
});

/* ── Username auto-generation (create only) ────────────────────────────────
 * Suggest `firstnamelastname###` (3 random digits) as the names are typed, and
 * re-roll the digits whenever the suggestion collides — until the admin edits
 * the field themselves, after which we never overwrite their choice. */
const usernameTouched = ref<boolean>(isEdit.value);
const regenAttempts = ref<number>(0);
const MAX_REGEN = 5;

/** Username the user edits directly — typing here freezes auto-generation.
 * Input is sanitised on every keystroke: non-alphanumerics are dropped and the
 * value is hard-capped at USERNAME_MAX, so it can never exceed the limit. */
const usernameModel = computed<string>({
    get: () => form.username,
    set: (value) => {
        const next = sanitizeUsername(value);
        // A no-op emit (same value, or an invalid keystroke that sanitises away)
        // must NOT freeze auto-generation — only a real edit takes over.
        if (next === form.username) {
            return;
        }
        usernameTouched.value = true;
        form.username = next;
    },
});

function alphaNumeric(value: string): string {
    return value.toLowerCase().replace(/[^a-z0-9]/g, '');
}

function randomDigits(count: number): string {
    let digits = '';
    for (let i = 0; i < count; i += 1) {
        digits += String(Math.floor(Math.random() * 10));
    }
    return digits;
}

/**
 * Build `firstnamelastname` + random digits, guaranteed to land within
 * [USERNAME_MIN, USERNAME_MAX]: the name base is capped so at least 3 digits
 * still fit, and enough digits are appended to reach the minimum length.
 */
function generatedUsername(): string {
    const base = `${alphaNumeric(form.first_name)}${alphaNumeric(form.last_name)}`.slice(0, USERNAME_MAX - 3);
    if (base === '') {
        return '';
    }
    const maxDigits = USERNAME_MAX - base.length;
    const digitCount = Math.min(maxDigits, Math.max(3, USERNAME_MIN - base.length));
    return `${base}${randomDigits(digitCount)}`;
}

// Names changed → refresh the suggestion (unless the admin has taken over).
watch([() => form.first_name, () => form.last_name], () => {
    if (usernameTouched.value) {
        return;
    }
    regenAttempts.value = 0;
    form.username = generatedUsername();
});

// Suggestion collided → re-roll the digits a bounded number of times.
watch(usernameStatus, (status) => {
    if (status === 'taken' && !usernameTouched.value && regenAttempts.value < MAX_REGEN) {
        regenAttempts.value += 1;
        form.username = generatedUsername();
    }
});

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

    // Block on a known-taken email/username — the backend stays authoritative,
    // but this spares an obviously-doomed round-trip.
    if (emailStatus.value === 'taken') {
        form.setError('email', 'This email is already registered');
        return;
    }
    if (usernameStatus.value === 'taken') {
        form.setError('username', 'This username is already taken');
        return;
    }

    const edit = isEdit.value;
    form.transform((data) => {
        const payload: Record<string, unknown> = {
            first_name: data.first_name.trim(),
            last_name: data.last_name.trim(),
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
            country_code: emptyToNull(data.country_code),
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
        <!-- ── Personal information ── -->
        <section class="user-form__section">
            <header class="user-form__section-head">
                <h3 class="user-form__section-title">Personal information</h3>
            </header>
            <div class="user-form__grid">
                <TextField
                    v-model="firstNameModel"
                    name="first_name"
                    label="First name"
                    placeholder="e.g. Ada"
                    required
                    :maxlength="255"
                    autocomplete="given-name"
                    :error="form.errors.first_name"
                />
                <TextField
                    v-model="lastNameModel"
                    name="last_name"
                    label="Last name"
                    placeholder="e.g. Lovelace"
                    required
                    :maxlength="255"
                    autocomplete="family-name"
                    :error="form.errors.last_name"
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
                    :options="genderOptions"
                    :error="form.errors.gender"
                />
            </div>
        </section>

        <!-- ── Account ── -->
        <section class="user-form__section">
            <header class="user-form__section-head">
                <h3 class="user-form__section-title">Account</h3>
            </header>
            <div class="user-form__grid">
                <TextField
                    v-model="form.email"
                    name="email"
                    label="Email"
                    type="email"
                    placeholder="name@example.com"
                    required
                    :maxlength="255"
                    autocomplete="email"
                    :error="emailError"
                    :success="emailSuccess"
                    :hint="emailFieldHint"
                    class="user-form__email"
                />
                <TextField
                    v-model="usernameModel"
                    name="username"
                    label="Username"
                    placeholder="Auto-generated from name"
                    :maxlength="USERNAME_MAX"
                    autocomplete="username"
                    :error="usernameError"
                    :success="usernameSuccess"
                    :hint="usernameStatus === 'checking' ? 'Checking availability…' : undefined"
                />

                <PhoneField
                    v-model="phoneModel"
                    name="phone"
                    label="Phone"
                    default-country="US"
                    :error="form.errors.phone"
                />
            </div>
        </section>

        <!-- ── Address ── -->
        <section class="user-form__section">
            <header class="user-form__section-head">
                <h3 class="user-form__section-title">Address</h3>
            </header>
            <AddressAutocomplete
                v-model="addressModel"
                :api-key="page.props.config.google_maps_api_key"
                :errors="addressErrors"
                with-country-code
            />
        </section>

        <!-- ── Access (invite only) ── -->
        <section v-if="showRoles" class="user-form__section">
            <header class="user-form__section-head">
                <h3 class="user-form__section-title">Access</h3>
                <p class="user-form__section-hint">
                    Assign a starter role now — fine-tune permissions later from the user's Access screen.
                </p>
            </header>
            <div class="user-form__grid">
                <SelectField
                    v-model="roleModel"
                    name="role"
                    label="Role"
                    placeholder="Select a role"
                    :options="roleOptions"
                    :error="form.errors.roles"
                />
            </div>
        </section>

        <!-- ── Security (edit only) ── -->
        <section v-if="isEdit" class="user-form__section">
            <header class="user-form__section-head">
                <h3 class="user-form__section-title">Security</h3>
            </header>
            <div class="user-form__toggle">
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
        </section>

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
    gap: var(--space-7);
}

.user-form__section {
    display: flex;
    flex-direction: column;
    gap: var(--space-6);
}

.user-form__section-head {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
    padding-bottom: var(--space-3);
    border-bottom: 1px solid var(--border-default);
}

.user-form__section-title {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    margin: 0;
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
    letter-spacing: 0.01em;
    color: var(--accent-primary);
}

/* Leading gradient accent bar — anchors the title and adds colour. */
.user-form__section-title::before {
    content: '';
    width: 3px;
    height: 1.1em;
    flex-shrink: 0;
    border-radius: var(--radius-full, 99px);
    background: var(--grad-primary, var(--accent-primary));
}

.user-form__section-hint {
    margin: 0;
    padding-left: calc(3px + var(--space-2));
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.user-form__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-3);
}

/* Highlight the activation-link notice on the invite form in accent yellow. */
.user-form__email :deep(.form-field__hint) {
    color: var(--accent-warning);
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
