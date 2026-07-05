<script setup lang="ts">
/**
 * Self-service profile — a single screen that fuses everything the GUIDE profile
 * exposes so the user never has to leave the page:
 *   · Profile Information (view ⇄ edit toggle) with avatar upload + crop
 *   · Security: inline two-factor management (enable/confirm/disable + recovery
 *     codes, all password-confirmed), change password, active sessions, and
 *     trusted devices
 *   · Roles & Permissions (read-only)
 *
 * Profile fields save via Fortify's PUT /user/profile-information (unique
 * email/username validation returns through the `updateProfileInformation`
 * error bag); the password via PUT /user/password (`updatePassword` bag); the
 * cropped photo via POST /profile/photo. Two-factor enrollment (QR / secret /
 * recovery codes) is loaded on demand from Fortify's JSON endpoints and every
 * destructive 2FA action is gated by POST /user/confirm-password. Built entirely
 * from the reusable common/form kit + common/media/ImageCropper.
 */
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import TextField from '@/common/form/TextField.vue';
import SelectField from '@/common/form/SelectField.vue';
import DateField from '@/common/form/DateField.vue';
import SubmitButton from '@/common/form/SubmitButton.vue';
import ImageCropper from '@/common/media/ImageCropper.vue';
import Button from '@/volt/Button.vue';
import SecondaryButton from '@/volt/SecondaryButton.vue';
import InputText from '@/volt/InputText.vue';
import Dialog from '@/volt/Dialog.vue';
import { apiFetch, HttpError } from '@/lib/http';
import { genderOptions } from '@/modules/profile/helpers/genderOptions';
import { profileFormSchema } from '@/modules/profile/schemas/profileFormSchema';
import { passwordFormSchema } from '@/modules/profile/schemas/passwordFormSchema';
import type {
    ActiveSession,
    ProfileData,
    ProfileFormValues,
    TrustedDevice,
    TwoFactorState,
} from '@/modules/profile/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    profile: ProfileData;
    sessions: ActiveSession[];
    twoFactor: TwoFactorState;
    trustedDevices: TrustedDevice[];
}>();

const toast = useToast();

const editing = ref<boolean>(false);
const today = new Date().toISOString().slice(0, 10);

const initials = computed<string>(() => {
    const first = props.profile.first_name?.charAt(0) ?? '';
    const last = props.profile.last_name?.charAt(0) ?? '';
    return (first + last).toUpperCase() || props.profile.email.charAt(0).toUpperCase();
});

const memberSince = computed<string>(() =>
    props.profile.created_at
        ? new Date(props.profile.created_at).toLocaleDateString(undefined, { dateStyle: 'medium' })
        : '—',
);

const location = computed<string>(
    () => [props.profile.city, props.profile.state, props.profile.country].filter(Boolean).join(', ') || 'Not set',
);

const genderLabel = computed<string>(
    () => genderOptions.find((option) => option.value === props.profile.gender)?.label ?? 'Not set',
);

/* ── Device / date formatting (shared by sessions + trusted devices) ────── */
function deviceLabel(userAgent: string | null): string {
    if (!userAgent) {
        return 'Unknown device';
    }
    // Order matters: Edge/Opera UAs also contain "Chrome"/"Safari".
    const browser = /Edg\//.test(userAgent)
        ? 'Microsoft Edge'
        : /OPR\/|Opera/.test(userAgent)
          ? 'Opera'
          : /Chrome\//.test(userAgent)
            ? 'Chrome'
            : /Firefox\//.test(userAgent)
              ? 'Firefox'
              : /Safari\//.test(userAgent)
                ? 'Safari'
                : 'Unknown browser';
    const os = /Windows/.test(userAgent)
        ? 'Windows'
        : /Mac OS X|Macintosh/.test(userAgent)
          ? 'macOS'
          : /Android/.test(userAgent)
            ? 'Android'
            : /iPhone|iPad|iPod|iOS/.test(userAgent)
              ? 'iOS'
              : /Linux/.test(userAgent)
                ? 'Linux'
                : '';
    return os ? `${browser} · ${os}` : browser;
}

function formatDate(iso: string | null): string {
    return iso
        ? new Date(iso).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })
        : '—';
}

/* ── Profile form ─────────────────────────────────────────────────────── */
const form = useForm<ProfileFormValues>({
    first_name: props.profile.first_name ?? '',
    last_name: props.profile.last_name ?? '',
    username: props.profile.username ?? '',
    email: props.profile.email ?? '',
    phone: props.profile.phone ?? '',
    date_of_birth: (props.profile.date_of_birth ?? '').slice(0, 10),
    gender: props.profile.gender ?? '',
    address: props.profile.address ?? '',
    address_2: props.profile.address_2 ?? '',
    city: props.profile.city ?? '',
    state: props.profile.state ?? '',
    zip_code: props.profile.zip_code ?? '',
    country: props.profile.country ?? '',
});

const canSubmitProfile = computed<boolean>(() => profileFormSchema.safeParse(form.data()).success);

function toggleEdit(): void {
    if (editing.value) {
        form.reset();
        form.clearErrors();
    }
    editing.value = !editing.value;
}

function saveProfile(): void {
    if (!canSubmitProfile.value) {
        return;
    }

    const parsed = profileFormSchema.safeParse(form.data());
    if (!parsed.success) {
        form.clearErrors();
        for (const issue of parsed.error.issues) {
            const key = issue.path[0];
            if (typeof key === 'string') {
                form.setError(key as keyof ProfileFormValues, issue.message);
            }
        }
        return;
    }

    form
        .transform((data) => {
            const payload: Record<string, string | null> = { ...data };
            for (const key of Object.keys(payload)) {
                if (payload[key] === '') {
                    payload[key] = null;
                }
            }
            // Required fields stay as strings even if somehow blank.
            payload.first_name = data.first_name;
            payload.email = data.email;
            return payload;
        })
        .put('/user/profile-information', {
            errorBag: 'updateProfileInformation',
            preserveScroll: true,
            onSuccess: () => {
                editing.value = false;
                toast.add({ severity: 'success', summary: 'Profile updated', life: 4000 });
            },
        });
}

/* ── Password form ────────────────────────────────────────────────────── */
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const canSubmitPassword = computed<boolean>(() => passwordFormSchema.safeParse(passwordForm.data()).success);

function changePassword(): void {
    if (!canSubmitPassword.value) {
        return;
    }

    passwordForm.put('/user/password', {
        errorBag: 'updatePassword',
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            toast.add({ severity: 'success', summary: 'Password updated', life: 4000 });
        },
    });
}

/* ── Avatar upload + crop ─────────────────────────────────────────────── */
const fileInput = ref<HTMLInputElement | null>(null);
const cropperVisible = ref<boolean>(false);
const cropperImageUrl = ref<string | null>(null);
const uploadingPhoto = ref<boolean>(false);

function openFilePicker(): void {
    fileInput.value?.click();
}

function onPhotoSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) {
        return;
    }
    const reader = new FileReader();
    reader.onload = (): void => {
        cropperImageUrl.value = reader.result as string;
        cropperVisible.value = true;
    };
    reader.readAsDataURL(file);
    // Reset so selecting the same file again re-triggers change.
    input.value = '';
}

function onCropped(blob: Blob): void {
    const data = new FormData();
    data.append('photo', blob, 'profile-photo.jpg');
    uploadingPhoto.value = true;
    router.post('/profile/photo', data, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Profile photo updated', life: 4000 }),
        onFinish: () => {
            uploadingPhoto.value = false;
            cropperImageUrl.value = null;
        },
    });
}

/* ── Two-factor authentication ────────────────────────────────────────── */
type TwoFactorStatus = 'disabled' | 'pending' | 'enabled';

const tfaStatus = ref<TwoFactorStatus>(
    props.twoFactor.confirmed ? 'enabled' : props.twoFactor.enabled ? 'pending' : 'disabled',
);
const tfaBusy = ref<boolean>(false);
const setupOpen = ref<boolean>(false);
const qrSvg = ref<string>('');
const secretKey = ref<string>('');
const confirmCode = ref<string>('');
const confirmError = ref<string>('');
const recoveryCodes = ref<string[]>([]);
const showRecovery = ref<boolean>(false);

// Password-confirmation modal — Fortify guards every 2FA mutation behind a
// recent password confirmation (POST /user/confirm-password).
const passwordModalOpen = ref<boolean>(false);
const passwordInput = ref<string>('');
const passwordError = ref<string>('');
let pendingAction: (() => Promise<void>) | null = null;

function failTfa(message: string): void {
    toast.add({ severity: 'error', summary: message, life: 5000 });
}

function requirePassword(action: () => Promise<void>): void {
    pendingAction = action;
    passwordInput.value = '';
    passwordError.value = '';
    passwordModalOpen.value = true;
}

async function submitPassword(): Promise<void> {
    passwordError.value = '';
    try {
        await apiFetch('POST', '/user/confirm-password', { password: passwordInput.value });
        passwordModalOpen.value = false;
        const action = pendingAction;
        pendingAction = null;
        if (action) {
            await action();
        }
    } catch (error) {
        passwordError.value =
            error instanceof HttpError && error.status === 422
                ? 'Incorrect password. Please try again.'
                : 'Could not verify your password.';
    }
}

async function loadEnrollment(): Promise<void> {
    const [qr, secret] = await Promise.all([
        apiFetch<{ svg: string }>('GET', '/user/two-factor-qr-code'),
        apiFetch<{ secretKey: string }>('GET', '/user/two-factor-secret-key'),
    ]);
    qrSvg.value = qr.svg;
    secretKey.value = secret.secretKey;
    await loadRecoveryCodes();
}

async function loadRecoveryCodes(): Promise<void> {
    recoveryCodes.value = await apiFetch<string[]>('GET', '/user/two-factor-recovery-codes');
}

function enableTwoFactor(): void {
    requirePassword(async () => {
        tfaBusy.value = true;
        try {
            await apiFetch('POST', '/user/two-factor-authentication');
            await loadEnrollment();
            tfaStatus.value = 'pending';
            confirmCode.value = '';
            confirmError.value = '';
            setupOpen.value = true;
        } catch {
            failTfa('Could not start two-factor setup.');
        } finally {
            tfaBusy.value = false;
        }
    });
}

async function confirmTwoFactor(): Promise<void> {
    if (confirmCode.value.length < 6) {
        return;
    }
    confirmError.value = '';
    tfaBusy.value = true;
    try {
        await apiFetch('POST', '/user/confirmed-two-factor-authentication', { code: confirmCode.value });
        tfaStatus.value = 'enabled';
        confirmCode.value = '';
        setupOpen.value = false;
        showRecovery.value = true;
        toast.add({ severity: 'success', summary: 'Two-factor authentication enabled', life: 4000 });
        router.reload({ only: ['twoFactor', 'profile'] });
    } catch (error) {
        confirmError.value =
            error instanceof HttpError && error.status === 422
                ? 'That code is invalid or expired.'
                : 'Could not confirm the code.';
    } finally {
        tfaBusy.value = false;
    }
}

function disableTwoFactor(): void {
    requirePassword(async () => {
        tfaBusy.value = true;
        try {
            await apiFetch('DELETE', '/user/two-factor-authentication');
            tfaStatus.value = 'disabled';
            qrSvg.value = '';
            secretKey.value = '';
            recoveryCodes.value = [];
            showRecovery.value = false;
            toast.add({ severity: 'success', summary: 'Two-factor authentication disabled', life: 4000 });
            router.reload({ only: ['twoFactor', 'trustedDevices', 'profile'] });
        } catch {
            failTfa('Could not disable two-factor authentication.');
        } finally {
            tfaBusy.value = false;
        }
    });
}

function regenerateRecoveryCodes(): void {
    requirePassword(async () => {
        tfaBusy.value = true;
        try {
            await apiFetch('POST', '/user/two-factor-recovery-codes');
            await loadRecoveryCodes();
            showRecovery.value = true;
            toast.add({ severity: 'success', summary: 'Recovery codes regenerated', life: 4000 });
        } catch {
            failTfa('Could not regenerate recovery codes.');
        } finally {
            tfaBusy.value = false;
        }
    });
}

async function revealRecoveryCodes(): Promise<void> {
    try {
        if (recoveryCodes.value.length === 0) {
            await loadRecoveryCodes();
        }
        showRecovery.value = true;
    } catch {
        failTfa('Could not load recovery codes.');
    }
}

function closeSetup(): void {
    setupOpen.value = false;
    confirmCode.value = '';
    confirmError.value = '';
}

/* ── Active sessions ──────────────────────────────────────────────────── */
const hasOtherSessions = computed<boolean>(() => props.sessions.some((session) => !session.is_current));

function revokeSession(id: string): void {
    router.delete(`/sessions/${id}`, { preserveScroll: true });
}

function logoutOtherDevices(): void {
    router.delete('/sessions/others', {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Signed out of other devices', life: 4000 }),
    });
}

/* ── Trusted devices ──────────────────────────────────────────────────── */
function revokeTrustedDevice(uuid: string): void {
    router.delete(`/two-factor/trusted-devices/${uuid}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="My Profile" />

    <AppHeader title="My Profile" subtitle="Manage your account and security settings" />

    <div class="profile-grid">
        <!-- ══ Profile Information ══ -->
        <section class="profile-card">
            <header class="profile-card__head">
                <h2 class="profile-card__title">Profile Information</h2>
                <button
                    type="button"
                    class="edit-toggle"
                    :class="{ 'edit-toggle--cancel': editing }"
                    @click="toggleEdit"
                >
                    <i class="pi" :class="editing ? 'pi-times' : 'pi-pencil'" aria-hidden="true" />
                    {{ editing ? 'Cancel' : 'Edit Profile' }}
                </button>
            </header>

            <!-- Avatar -->
            <div class="photo">
                <div class="photo__wrap">
                    <img
                        v-if="profile.profile_photo_url"
                        :src="profile.profile_photo_url"
                        alt="Profile"
                        class="photo__img"
                    />
                    <span v-else class="photo__placeholder" aria-hidden="true">{{ initials }}</span>
                    <button
                        type="button"
                        class="photo__btn"
                        :disabled="uploadingPhoto"
                        aria-label="Change photo"
                        title="Change photo"
                        @click="openFilePicker"
                    >
                        <i class="pi" :class="uploadingPhoto ? 'pi-spin pi-spinner' : 'pi-camera'" aria-hidden="true" />
                    </button>
                </div>
                <input
                    ref="fileInput"
                    type="file"
                    accept="image/*"
                    class="sr-only"
                    @change="onPhotoSelected"
                />
                <p class="photo__hint">Click the camera to upload a new photo</p>
            </div>

            <!-- Edit form -->
            <form v-if="editing" class="profile-form" @submit.prevent="saveProfile">
                <div class="form-grid">
                    <TextField
                        v-model="form.first_name"
                        name="first_name"
                        label="First Name"
                        required
                        autocomplete="given-name"
                        :error="form.errors.first_name"
                    />
                    <TextField
                        v-model="form.last_name"
                        name="last_name"
                        label="Last Name"
                        autocomplete="family-name"
                        :error="form.errors.last_name"
                    />
                    <TextField
                        v-model="form.username"
                        name="username"
                        label="Username"
                        autocomplete="username"
                        :error="form.errors.username"
                    />
                    <TextField
                        v-model="form.email"
                        name="email"
                        type="email"
                        label="Email"
                        required
                        autocomplete="email"
                        :error="form.errors.email"
                    />
                    <TextField
                        v-model="form.phone"
                        name="phone"
                        type="tel"
                        label="Phone"
                        placeholder="555 000 0000"
                        autocomplete="tel"
                        :error="form.errors.phone"
                    />
                    <DateField
                        v-model="form.date_of_birth"
                        name="date_of_birth"
                        label="Date of Birth"
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
                    <TextField
                        v-model="form.country"
                        name="country"
                        label="Country"
                        autocomplete="country-name"
                        :error="form.errors.country"
                    />
                    <TextField
                        v-model="form.address"
                        name="address"
                        label="Address"
                        class="form-grid__full"
                        autocomplete="street-address"
                        :error="form.errors.address"
                    />
                    <TextField
                        v-model="form.address_2"
                        name="address_2"
                        label="Address 2"
                        placeholder="Apartment, suite, etc."
                        class="form-grid__full"
                        :error="form.errors.address_2"
                    />
                    <TextField
                        v-model="form.city"
                        name="city"
                        label="City"
                        autocomplete="address-level2"
                        :error="form.errors.city"
                    />
                    <TextField
                        v-model="form.state"
                        name="state"
                        label="State"
                        autocomplete="address-level1"
                        :error="form.errors.state"
                    />
                    <TextField
                        v-model="form.zip_code"
                        name="zip_code"
                        label="ZIP Code"
                        autocomplete="postal-code"
                        :error="form.errors.zip_code"
                    />
                </div>
                <div class="form-actions">
                    <SubmitButton
                        label="Save Changes"
                        icon="pi pi-check"
                        :loading="form.processing"
                        :disabled="!canSubmitProfile"
                    />
                </div>
            </form>

            <!-- Read-only details -->
            <dl v-else class="details">
                <div class="details__row">
                    <dt>Full Name</dt>
                    <dd>{{ [profile.first_name, profile.last_name].filter(Boolean).join(' ') || 'Not set' }}</dd>
                </div>
                <div class="details__row">
                    <dt>Username</dt>
                    <dd>{{ profile.username || 'Not set' }}</dd>
                </div>
                <div class="details__row">
                    <dt>Email</dt>
                    <dd class="details__email">
                        {{ profile.email }}
                        <span class="badge" :class="profile.email_verified ? 'badge--success' : 'badge--warning'">
                            {{ profile.email_verified ? 'Verified' : 'Pending' }}
                        </span>
                    </dd>
                </div>
                <div class="details__row">
                    <dt>Phone</dt>
                    <dd>{{ profile.phone || 'Not set' }}</dd>
                </div>
                <div class="details__row">
                    <dt>Date of Birth</dt>
                    <dd>{{ profile.date_of_birth || 'Not set' }}</dd>
                </div>
                <div class="details__row">
                    <dt>Gender</dt>
                    <dd>{{ genderLabel }}</dd>
                </div>
                <div class="details__row">
                    <dt>Location</dt>
                    <dd>{{ location }}</dd>
                </div>
                <div class="details__row">
                    <dt>Member Since</dt>
                    <dd>{{ memberSince }}</dd>
                </div>
            </dl>
        </section>

        <!-- ══ Security ══ -->
        <section id="security" class="profile-card">
            <h2 class="profile-card__title">Security</h2>

            <!-- Two-factor authentication -->
            <div class="security__section">
                <div class="security__head">
                    <div>
                        <h3 class="security__subtitle">Two-Factor Authentication</h3>
                        <p class="security__desc">Add an extra layer of security to your account.</p>
                    </div>
                    <span
                        class="badge"
                        :class="tfaStatus === 'enabled' ? 'badge--success' : 'badge--neutral'"
                    >
                        {{ tfaStatus === 'enabled' ? 'Enabled' : tfaStatus === 'pending' ? 'Pending' : 'Disabled' }}
                    </span>
                </div>

                <!-- ENABLED → recovery codes + regenerate/disable -->
                <div v-if="tfaStatus === 'enabled'" class="tfa-panel">
                    <p class="tfa-panel__line">
                        <i class="pi pi-shield" aria-hidden="true" />
                        Two-factor authentication is active on your account.
                    </p>
                    <ul v-if="showRecovery && recoveryCodes.length" class="tfa-codes">
                        <li v-for="code in recoveryCodes" :key="code"><code>{{ code }}</code></li>
                    </ul>
                    <div class="tfa-panel__actions">
                        <SecondaryButton
                            v-if="!showRecovery"
                            label="Show recovery codes"
                            icon="pi pi-eye"
                            @click="revealRecoveryCodes"
                        />
                        <SecondaryButton
                            label="Regenerate codes"
                            icon="pi pi-refresh"
                            :disabled="tfaBusy"
                            @click="regenerateRecoveryCodes"
                        />
                        <button
                            type="button"
                            class="link-danger"
                            :disabled="tfaBusy"
                            @click="disableTwoFactor"
                        >
                            <i class="pi pi-lock-open" aria-hidden="true" />
                            Disable 2FA
                        </button>
                    </div>
                </div>

                <!-- PENDING → resume enrollment -->
                <div v-else-if="tfaStatus === 'pending'" class="tfa-panel">
                    <p class="tfa-panel__line tfa-panel__line--warn">
                        <i class="pi pi-exclamation-triangle" aria-hidden="true" />
                        Setup started but not confirmed yet.
                    </p>
                    <div class="tfa-panel__actions">
                        <Button label="Resume setup" icon="pi pi-qrcode" @click="setupOpen = true" />
                        <button
                            type="button"
                            class="link-danger"
                            :disabled="tfaBusy"
                            @click="disableTwoFactor"
                        >
                            Cancel setup
                        </button>
                    </div>
                </div>

                <!-- DISABLED → enable -->
                <div v-else class="tfa-panel">
                    <p class="tfa-panel__line tfa-panel__line--warn">
                        <i class="pi pi-exclamation-triangle" aria-hidden="true" />
                        Two-factor authentication is not enabled.
                    </p>
                    <div class="tfa-panel__actions">
                        <Button label="Enable 2FA" icon="pi pi-shield" :disabled="tfaBusy" @click="enableTwoFactor" />
                    </div>
                </div>
            </div>

            <div class="security__divider" />

            <!-- Change password -->
            <div class="security__section">
                <h3 class="security__subtitle">Change Password</h3>
                <form class="password-form" @submit.prevent="changePassword">
                    <TextField
                        v-model="passwordForm.current_password"
                        name="current_password"
                        type="password"
                        label="Current Password"
                        autocomplete="current-password"
                        :error="passwordForm.errors.current_password"
                    />
                    <TextField
                        v-model="passwordForm.password"
                        name="password"
                        type="password"
                        label="New Password"
                        autocomplete="new-password"
                        :error="passwordForm.errors.password"
                    />
                    <TextField
                        v-model="passwordForm.password_confirmation"
                        name="password_confirmation"
                        type="password"
                        label="Confirm New Password"
                        autocomplete="new-password"
                        :error="passwordForm.errors.password_confirmation"
                    />
                    <div class="form-actions">
                        <SubmitButton
                            label="Update Password"
                            icon="pi pi-key"
                            :loading="passwordForm.processing"
                            :disabled="!canSubmitPassword"
                        />
                    </div>
                </form>
            </div>

            <div class="security__divider" />

            <!-- Active sessions -->
            <div class="security__section">
                <div class="security__head">
                    <div>
                        <h3 class="security__subtitle">Active Sessions</h3>
                        <p class="security__desc">Devices and browsers currently signed in to your account.</p>
                    </div>
                    <button
                        type="button"
                        class="link-danger"
                        :disabled="!hasOtherSessions"
                        @click="logoutOtherDevices"
                    >
                        <i class="pi pi-sign-out" aria-hidden="true" />
                        Logout All
                    </button>
                </div>

                <p v-if="!sessions.length" class="security__empty">No active sessions to display.</p>
                <ul v-else class="device-list">
                    <li v-for="session in sessions" :key="session.id" class="device-item">
                        <div class="device-item__icon" aria-hidden="true"><i class="pi pi-desktop" /></div>
                        <div class="device-item__body">
                            <span class="device-item__title">
                                <span class="device-item__name" :title="session.user_agent ?? ''">
                                    {{ deviceLabel(session.user_agent) }}
                                </span>
                                <span v-if="session.is_current" class="badge badge--success">This device</span>
                            </span>
                            <span class="device-item__meta">
                                {{ session.ip_address ?? 'Unknown IP' }} · {{ formatDate(session.last_active_at) }}
                            </span>
                        </div>
                        <button
                            v-if="!session.is_current"
                            type="button"
                            class="icon-btn"
                            title="Sign out this device"
                            aria-label="Sign out this device"
                            @click="revokeSession(session.id)"
                        >
                            <i class="pi pi-trash" aria-hidden="true" />
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Trusted devices -->
            <template v-if="trustedDevices.length">
                <div class="security__divider" />
                <div class="security__section">
                    <div class="security__head">
                        <div>
                            <h3 class="security__subtitle">Trusted Devices</h3>
                            <p class="security__desc">Devices that skip two-factor for 30 days.</p>
                        </div>
                    </div>
                    <ul class="device-list">
                        <li v-for="device in trustedDevices" :key="device.uuid" class="device-item">
                            <div class="device-item__icon" aria-hidden="true"><i class="pi pi-mobile" /></div>
                            <div class="device-item__body">
                                <span class="device-item__title">
                                    <span class="device-item__name" :title="device.user_agent ?? ''">
                                        {{ deviceLabel(device.user_agent) }}
                                    </span>
                                </span>
                                <span class="device-item__meta">
                                    {{ device.ip_address ?? 'Unknown IP' }} · Expires {{ formatDate(device.expires_at) }}
                                </span>
                            </div>
                            <button
                                type="button"
                                class="icon-btn"
                                title="Remove trusted device"
                                aria-label="Remove trusted device"
                                @click="revokeTrustedDevice(device.uuid)"
                            >
                                <i class="pi pi-trash" aria-hidden="true" />
                            </button>
                        </li>
                    </ul>
                </div>
            </template>
        </section>

        <!-- ══ Roles & Permissions ══ -->
        <section class="profile-card profile-card--wide">
            <h2 class="profile-card__title">Roles &amp; Permissions</h2>
            <div class="roles">
                <span v-for="role in profile.roles" :key="role" class="role-chip">
                    <i class="pi pi-id-card" aria-hidden="true" />
                    {{ role }}
                </span>
                <p v-if="!profile.roles.length" class="muted">No roles assigned.</p>
            </div>
            <div class="perm-count">
                <i class="pi pi-check-circle" aria-hidden="true" />
                <span>{{ profile.permissions.length }} effective permissions</span>
            </div>
        </section>
    </div>

    <ImageCropper
        v-model:visible="cropperVisible"
        :image-url="cropperImageUrl"
        :aspect-ratio="1"
        circular
        title="Crop profile photo"
        @cropped="onCropped"
    />

    <!-- Two-factor setup -->
    <Dialog v-model:visible="setupOpen" modal header="Set up two-factor authentication">
        <div class="setup">
            <p class="setup__lead">
                Scan this QR code with your authenticator app (Google Authenticator, Authy, 1Password…),
                then enter the 6-digit code.
            </p>
            <div class="setup__qr" v-html="qrSvg" />
            <p v-if="secretKey" class="setup__secret">
                Or enter this key manually: <code>{{ secretKey }}</code>
            </p>
            <form class="setup__confirm" @submit.prevent="confirmTwoFactor">
                <InputText
                    v-model="confirmCode"
                    inputmode="numeric"
                    maxlength="6"
                    placeholder="123456"
                    autocomplete="one-time-code"
                    aria-label="Authentication code"
                />
                <p v-if="confirmError" class="setup__error">{{ confirmError }}</p>
            </form>
        </div>
        <template #footer>
            <SecondaryButton label="Cancel" @click="closeSetup" />
            <Button
                label="Verify & Enable"
                icon="pi pi-check"
                :disabled="tfaBusy || confirmCode.length < 6"
                @click="confirmTwoFactor"
            />
        </template>
    </Dialog>

    <!-- Password confirmation for destructive 2FA actions -->
    <Dialog v-model:visible="passwordModalOpen" modal header="Confirm your password">
        <form class="pw-confirm" @submit.prevent="submitPassword">
            <p class="security__desc">For your security, please confirm your password to continue.</p>
            <InputText
                v-model="passwordInput"
                type="password"
                placeholder="Password"
                autocomplete="current-password"
                aria-label="Password"
                autofocus
            />
            <p v-if="passwordError" class="setup__error">{{ passwordError }}</p>
        </form>
        <template #footer>
            <SecondaryButton label="Cancel" @click="passwordModalOpen = false" />
            <Button label="Confirm" @click="submitPassword" />
        </template>
    </Dialog>
</template>

<style scoped>
.profile-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--space-6);
    align-items: start;
}

.profile-card {
    background: color-mix(in srgb, var(--bg-surface) 60%, transparent);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-2xl);
    padding: var(--space-6) var(--space-8);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

.profile-card__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
    margin-bottom: var(--space-5);
}

.profile-card__title {
    font-size: var(--text-lg);
    font-weight: var(--font-bold);
    color: var(--text-primary);
    margin: 0 0 var(--space-5);
}

.profile-card__head .profile-card__title {
    margin: 0;
}

.edit-toggle {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-2) var(--space-4);
    border-radius: var(--radius-md);
    border: 1px solid var(--border-default);
    background: transparent;
    color: var(--accent-primary);
    font-family: var(--font-sans);
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    cursor: pointer;
    transition: background var(--transition), border-color var(--transition), color var(--transition);
}

.edit-toggle:hover {
    background: color-mix(in srgb, var(--accent-primary) 10%, transparent);
    border-color: color-mix(in srgb, var(--accent-primary) 40%, transparent);
}

.edit-toggle--cancel {
    color: var(--accent-error);
    border-color: var(--accent-error);
}

.edit-toggle--cancel:hover {
    background: color-mix(in srgb, var(--accent-error) 10%, transparent);
    border-color: var(--accent-error);
    color: var(--accent-error);
}

/* ── Avatar ── */
.photo {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-3);
    margin-bottom: var(--space-6);
}

.photo__wrap {
    position: relative;
    width: 112px;
    height: 112px;
}

.photo__img,
.photo__placeholder {
    width: 112px;
    height: 112px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--border-default);
}

.photo__placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--grad-primary);
    color: var(--on-accent);
    font-size: var(--text-3xl);
    font-weight: var(--font-bold);
}

.photo__btn {
    position: absolute;
    right: -2px;
    bottom: -2px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 2px solid var(--bg-surface);
    background: var(--accent-primary);
    color: var(--on-accent);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: opacity var(--transition-fast);
}

.photo__btn:hover {
    opacity: 0.9;
}

.photo__btn:disabled {
    opacity: 0.6;
    pointer-events: none;
}

.photo__hint {
    margin: 0;
    font-size: var(--text-xs);
    color: var(--text-muted);
}

/* ── Edit form ── */
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-4);
}

.form-grid__full {
    grid-column: 1 / -1;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: var(--space-6);
}

/* ── Read-only details ── */
.details {
    margin: 0;
    display: flex;
    flex-direction: column;
}

.details__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
    padding: var(--space-3) 0;
    border-bottom: 1px solid var(--border-subtle);
}

.details__row:last-child {
    border-bottom: none;
}

.details dt {
    font-size: var(--text-sm);
    color: var(--text-muted);
}

.details dd {
    margin: 0;
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--text-primary);
    text-align: right;
    word-break: break-word;
}

.details__email {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    flex-wrap: wrap;
    justify-content: flex-end;
}

/* ── Security ── */
.security__section {
    margin: 0;
}

.security__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-4);
    margin-bottom: var(--space-4);
}

.security__subtitle {
    font-size: var(--text-base);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
    margin: 0 0 var(--space-1);
}

.security__desc {
    font-size: var(--text-sm);
    color: var(--text-muted);
    margin: 0;
}

.security__divider {
    height: 1px;
    background: var(--border-subtle);
    margin: var(--space-6) 0;
}

.security__empty {
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-muted);
}

/* ── Two-factor panel ── */
.tfa-panel {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    padding: var(--space-4);
    border-radius: var(--radius-lg);
    background: color-mix(in srgb, var(--text-primary) 3%, transparent);
}

.tfa-panel__line {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

.tfa-panel__line .pi-shield {
    color: var(--accent-success);
}

.tfa-panel__line--warn .pi {
    color: var(--accent-warning);
}

.tfa-panel__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-3);
}

.tfa-codes {
    list-style: none;
    margin: 0;
    padding: var(--space-4);
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: var(--space-2);
    border-radius: var(--radius-lg);
    background: color-mix(in srgb, var(--bg-elevated) 60%, transparent);
}

.tfa-codes code {
    font-family: var(--font-mono);
    font-size: var(--text-sm);
    color: var(--text-primary);
}

/* ── Password + confirm forms ── */
.password-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}

.pw-confirm {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    width: min(22rem, calc(100vw - 4rem));
}

/* ── Device lists (sessions + trusted) ── */
.device-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    /* Cap the list at ~5 rows and scroll the rest so a long session history
       never blows out the card. Extra right padding keeps the scrollbar off
       the trash buttons. */
    max-height: 320px;
    overflow-y: auto;
    padding-right: var(--space-1);
}

.device-item {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-3);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--bg-elevated) 60%, transparent);
    transition: border-color var(--transition);
}

.device-item:hover {
    border-color: var(--border-default);
}

.device-item__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    flex-shrink: 0;
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--accent-primary) 12%, transparent);
    color: var(--accent-primary);
}

.device-item__body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.device-item__title {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    min-width: 0;
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--text-primary);
}

.device-item__name {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.device-item__title .badge {
    flex-shrink: 0;
}

.device-item__meta {
    font-size: var(--text-xs);
    color: var(--text-muted);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    flex-shrink: 0;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-subtle);
    background: color-mix(in srgb, var(--bg-elevated) 50%, transparent);
    color: var(--accent-error);
    cursor: pointer;
    transition: border-color var(--transition), transform var(--transition);
}

.icon-btn:hover {
    border-color: currentColor;
    transform: scale(1.08);
}

/* ── Danger text link ── */
.link-danger {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    flex-shrink: 0;
    white-space: nowrap;
    background: transparent;
    border: none;
    padding: 0;
    font-family: var(--font-sans);
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--accent-error);
    cursor: pointer;
    transition: color var(--transition);
}

.link-danger:hover:not(:disabled) {
    color: color-mix(in srgb, var(--accent-error) 70%, var(--text-primary));
    text-decoration: underline;
}

.link-danger:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ── Two-factor setup dialog ── */
.setup {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    width: min(24rem, calc(100vw - 4rem));
}

.setup__lead {
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

.setup__qr {
    align-self: center;
    padding: var(--space-4);
    background: #fff;
    border-radius: var(--radius-lg);
    line-height: 0;
}

.setup__qr :deep(svg) {
    display: block;
    width: 180px;
    height: 180px;
}

.setup__secret {
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-secondary);
    word-break: break-all;
}

.setup__secret code {
    font-family: var(--font-mono);
}

.setup__confirm {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.setup__error {
    margin: 0;
    font-size: var(--text-sm);
    color: var(--accent-error);
}

/* ── Roles ── */
.roles {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
    margin-bottom: var(--space-5);
}

.role-chip {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--accent-primary) 12%, transparent);
    color: var(--accent-primary);
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
}

.perm-count {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    color: var(--accent-success);
    font-size: var(--text-sm);
}

.muted {
    margin: 0;
    color: var(--text-muted);
    font-size: var(--text-sm);
}

/* ── Status badges ── */
.badge {
    display: inline-flex;
    align-items: center;
    padding: 2px var(--space-2);
    border-radius: var(--radius-sm);
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
}

.badge--success {
    background: color-mix(in srgb, var(--accent-success) 18%, transparent);
    color: var(--accent-success);
}

.badge--warning {
    background: color-mix(in srgb, var(--accent-warning) 18%, transparent);
    color: var(--accent-warning);
}

.badge--neutral {
    background: color-mix(in srgb, var(--text-muted) 18%, transparent);
    color: var(--text-muted);
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* ── Responsive ── */
@media (min-width: 1024px) {
    .profile-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .profile-card--wide {
        grid-column: 1 / -1;
    }
}

@media (max-width: 560px) {
    .profile-card {
        padding: var(--space-5) var(--space-4);
    }

    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>
