<script setup lang="ts">
/**
 * Self-service profile page (GET /profile). Three cards: editable Profile
 * Information (view ⇄ edit toggle) with avatar upload + crop, a Security card
 * with change-password and links to the existing 2FA / sessions pages, and a
 * read-only Roles & Permissions card.
 *
 * Profile fields are saved via Fortify's PUT /user/profile-information
 * (unique email/username validation returns through the `updateProfileInformation`
 * error bag); the password via PUT /user/password (`updatePassword` bag); the
 * cropped photo via POST /profile/photo. Built entirely from the reusable
 * common/form kit + common/media/ImageCropper.
 */
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import TextField from '@/common/form/TextField.vue';
import SelectField from '@/common/form/SelectField.vue';
import DateField from '@/common/form/DateField.vue';
import SubmitButton from '@/common/form/SubmitButton.vue';
import ImageCropper from '@/common/media/ImageCropper.vue';
import { genderOptions } from '@/modules/profile/helpers/genderOptions';
import { profileFormSchema } from '@/modules/profile/schemas/profileFormSchema';
import type { ProfileData, ProfileFormValues } from '@/modules/profile/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{ profile: ProfileData }>();

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

function toggleEdit(): void {
    if (editing.value) {
        form.reset();
        form.clearErrors();
    }
    editing.value = !editing.value;
}

function saveProfile(): void {
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

function changePassword(): void {
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
                    <SubmitButton label="Save Changes" icon="pi pi-check" :loading="form.processing" />
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
        <section class="profile-card">
            <h2 class="profile-card__title">Security</h2>

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
                    <SubmitButton label="Update Password" icon="pi pi-key" :loading="passwordForm.processing" />
                </div>
            </form>

            <div class="security__divider" />

            <div class="security__links">
                <Link href="/two-factor/setup" class="security__link">
                    <span class="security__link-main">
                        <i class="pi pi-shield" aria-hidden="true" />
                        <span>Two-factor authentication</span>
                    </span>
                    <span class="badge" :class="profile.two_factor_enabled ? 'badge--success' : 'badge--neutral'">
                        {{ profile.two_factor_enabled ? 'Enabled' : 'Disabled' }}
                    </span>
                </Link>
                <Link href="/sessions" class="security__link">
                    <span class="security__link-main">
                        <i class="pi pi-desktop" aria-hidden="true" />
                        <span>Active sessions</span>
                    </span>
                    <i class="pi pi-chevron-right" aria-hidden="true" />
                </Link>
            </div>
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
    color: var(--text-muted);
}

.edit-toggle--cancel:hover {
    background: color-mix(in srgb, var(--text-primary) 6%, transparent);
    color: var(--text-primary);
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
.security__subtitle {
    font-size: var(--text-base);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
    margin: 0 0 var(--space-4);
}

.password-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}

.security__divider {
    height: 1px;
    background: var(--border-subtle);
    margin: var(--space-6) 0;
}

.security__links {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
}

.security__link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
    padding: var(--space-4);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-lg);
    color: var(--text-secondary);
    transition: background var(--transition), border-color var(--transition), color var(--transition);
}

.security__link:hover {
    background: color-mix(in srgb, var(--text-primary) 5%, transparent);
    border-color: var(--border-strong);
    color: var(--text-primary);
}

.security__link-main {
    display: inline-flex;
    align-items: center;
    gap: var(--space-3);
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
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
