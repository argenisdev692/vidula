<script setup lang="ts">
/**
 * Company settings — a single screen for the seeded company_data singleton:
 *   · Company details (name, contact, website, social links, phone + Google
 *     Maps address) saved via PUT /company-data/{uuid}
 *   · Branding: three logo variants (logo / logo_white / mark) uploaded via
 *     POST /settings/company/logo and removed via DELETE /settings/company/logo/{type}
 *   · Signature: draw it (SignaturePad) OR upload a PNG/PDF, saved via
 *     POST /settings/company/signature, removed via DELETE /settings/company/signature
 *
 * The record is never created or deleted from the UI. Every mutating control is
 * gated by the UPDATE_COMPANY_DATA permission. Resolved brand URLs come from the
 * shared `company` prop; asset uploads reuse Inertia multipart requests so page
 * props refresh (and the branding cache busts) on success. Built from the
 * reusable common/form + common/address + common/media kits.
 */
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import { useAuthorization } from '@/modules/auth/composables/useAuthorization';
import TextField from '@/common/form/TextField.vue';
import TextareaField from '@/common/form/TextareaField.vue';
import PhoneField from '@/common/form/PhoneField.vue';
import AddressAutocomplete from '@/common/address/AddressAutocomplete.vue';
import SubmitButton from '@/common/form/SubmitButton.vue';
import SignaturePad from '@/common/media/SignaturePad.vue';
import FileUpload from '@/common/media/FileUpload.vue';
import Button from '@/volt/Button.vue';
import type { AddressValue } from '@/common/address/types';
import type { SharedProps } from '@/types/inertia';
import { companyFormSchema, type CompanyFormValues } from '@/modules/company/schemas/companyFormSchema';
import type { CompanyData, CompanyLogoType, PaginatedResponse } from '@/modules/company/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    companies: PaginatedResponse<CompanyData>;
}>();

const page = usePage<SharedProps>();
const toast = useToast();
const { hasPermission } = useAuthorization();

/** Settings singleton — the one and only record lives at data[0]. */
const company = computed<CompanyData | null>(() => props.companies.data[0] ?? null);
const canUpdate = computed<boolean>(() => hasPermission('UPDATE_COMPANY_DATA'));

/* ── Company details form ─────────────────────────────────────────────── */
const form = useForm<CompanyFormValues>({
    company_name: company.value?.company_name ?? '',
    name: company.value?.name ?? '',
    description: company.value?.description ?? '',
    email: company.value?.email ?? '',
    phone: company.value?.phone ?? '',
    address: company.value?.address ?? '',
    address_2: company.value?.address_2 ?? '',
    zip_code: company.value?.zip_code ?? '',
    city: company.value?.city ?? '',
    state: company.value?.state ?? '',
    country: company.value?.country ?? '',
    country_code: company.value?.country_code ?? '',
    website: company.value?.website ?? '',
    facebook_link: company.value?.facebook_link ?? '',
    instagram_link: company.value?.instagram_link ?? '',
    linkedin_link: company.value?.linkedin_link ?? '',
    twitter_link: company.value?.twitter_link ?? '',
    tiktok_link: company.value?.tiktok_link ?? '',
    latitude: company.value?.latitude ?? null,
    longitude: company.value?.longitude ?? null,
});

const canSubmit = computed<boolean>(() => canUpdate.value && companyFormSchema.safeParse(form.data()).success);

/* Bridges between the flat form and the reusable field components. */
const phoneModel = computed<string | null>({
    get: () => form.phone || null,
    set: (value) => {
        form.phone = value ?? '';
    },
});

/* company_data persists the full address slice (street, line 2, city, state,
   ZIP, country + ISO country code) alongside the silent lat/lng captured from
   the picked place. */
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

function saveDetails(): void {
    if (!company.value || !canSubmit.value) {
        return;
    }

    const parsed = companyFormSchema.safeParse(form.data());
    if (!parsed.success) {
        form.clearErrors();
        for (const issue of parsed.error.issues) {
            const key = issue.path[0];
            if (typeof key === 'string') {
                form.setError(key as keyof CompanyFormValues, issue.message);
            }
        }
        return;
    }

    form
        .transform((data) => {
            const payload: Record<string, string | number | null> = { ...data };
            for (const key of Object.keys(payload)) {
                if (payload[key] === '') {
                    payload[key] = null;
                }
            }
            payload.company_name = data.company_name;
            return payload;
        })
        .put(`/company-data/${company.value.uuid}`, {
            preserveScroll: true,
            onSuccess: () => toast.add({ severity: 'success', summary: 'Company data updated', life: 4000 }),
        });
}

/* ── Branding logos ───────────────────────────────────────────────────── */
interface LogoSlot {
    type: CompanyLogoType;
    label: string;
    description: string;
    currentUrl: string;
    dark?: boolean;
}

const logoSlots = computed<LogoSlot[]>(() => [
    {
        type: 'logo',
        label: 'Primary logo',
        description: 'Shown on light backgrounds.',
        currentUrl: page.props.company.logo_url,
    },
    {
        type: 'logo_white',
        label: 'White logo',
        description: 'Shown on dark backgrounds (hero, emails).',
        currentUrl: page.props.company.logo_white_url,
        dark: true,
    },
    {
        type: 'mark',
        label: 'Mark / icon',
        description: 'Compact square icon.',
        currentUrl: page.props.company.mark_url,
    },
]);

const logoFiles = ref<Record<CompanyLogoType, File | null>>({ logo: null, logo_white: null, mark: null });
const uploadingLogo = ref<CompanyLogoType | null>(null);

function uploadLogo(type: CompanyLogoType): void {
    const file = logoFiles.value[type];
    if (!file) {
        return;
    }
    const data = new FormData();
    data.append('type', type);
    data.append('logo', file);
    uploadingLogo.value = type;
    router.post('/settings/company/logo', data, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            logoFiles.value[type] = null;
            toast.add({ severity: 'success', summary: 'Logo updated', life: 4000 });
        },
        onError: () => toast.add({ severity: 'error', summary: 'Could not upload the logo', life: 5000 }),
        onFinish: () => {
            uploadingLogo.value = null;
        },
    });
}

function removeLogo(type: CompanyLogoType): void {
    router.delete(`/settings/company/logo/${type}`, {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Logo removed', life: 4000 }),
    });
}

/* ── Signature ────────────────────────────────────────────────────────── */
type SignatureMode = 'draw' | 'upload';

const signatureMode = ref<SignatureMode>('draw');
const signaturePad = ref<InstanceType<typeof SignaturePad> | null>(null);
const signatureEmpty = ref<boolean>(true);
const signatureFile = ref<File | null>(null);
const savingSignature = ref<boolean>(false);

const hasSignature = computed<boolean>(() => !!company.value?.signature_path);

function postSignature(payload: FormData, onDone: () => void): void {
    savingSignature.value = true;
    router.post('/settings/company/signature', payload, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            onDone();
            toast.add({ severity: 'success', summary: 'Signature updated', life: 4000 });
        },
        onError: () => toast.add({ severity: 'error', summary: 'Could not save the signature', life: 5000 }),
        onFinish: () => {
            savingSignature.value = false;
        },
    });
}

async function saveDrawnSignature(): Promise<void> {
    const blob = await signaturePad.value?.toBlob();
    if (!blob) {
        return;
    }
    const data = new FormData();
    data.append('signature', blob, 'signature.png');
    postSignature(data, () => signaturePad.value?.clear());
}

function saveUploadedSignature(): void {
    if (!signatureFile.value) {
        return;
    }
    const data = new FormData();
    data.append('signature', signatureFile.value);
    postSignature(data, () => {
        signatureFile.value = null;
    });
}

function removeSignature(): void {
    router.delete('/settings/company/signature', {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Signature removed', life: 4000 }),
    });
}
</script>

<template>
    <Head title="Company Data" />

    <AppHeader title="Company Data" subtitle="Manage your organisation's details, branding and signature" />

    <div v-if="!company" class="empty">
        <i class="pi pi-building" aria-hidden="true" />
        <p>No company record found.</p>
    </div>

    <div v-else class="company-grid">
        <!-- ══ Company details ══ -->
        <section class="card card--wide">
            <h2 class="card__title">Company Details</h2>

            <form class="details-form" @submit.prevent="saveDetails">
                <div class="form-grid">
                    <TextField
                        v-model="form.company_name"
                        name="company_name"
                        label="Company Name"
                        required
                        :disabled="!canUpdate"
                        :error="form.errors.company_name"
                    />
                    <TextField
                        v-model="form.name"
                        name="name"
                        label="Display / Legal Name"
                        :disabled="!canUpdate"
                        :error="form.errors.name"
                    />
                    <TextField
                        v-model="form.email"
                        name="email"
                        type="email"
                        label="Support Email"
                        autocomplete="email"
                        :disabled="!canUpdate"
                        :error="form.errors.email"
                    />
                    <PhoneField
                        v-model="phoneModel"
                        name="phone"
                        label="Phone"
                        default-country="US"
                        :disabled="!canUpdate"
                        :error="form.errors.phone"
                    />
                    <TextField
                        v-model="form.website"
                        name="website"
                        type="url"
                        label="Website"
                        placeholder="https://example.com"
                        :disabled="!canUpdate"
                        :error="form.errors.website"
                    />
                </div>

                <TextareaField
                    v-model="form.description"
                    name="description"
                    label="Description"
                    hint="Shown internally to describe what this platform does."
                    :rows="3"
                    :disabled="!canUpdate"
                    :error="form.errors.description"
                />

                <div class="section-label">Address</div>
                <AddressAutocomplete
                    v-model="addressModel"
                    :api-key="page.props.config.google_maps_api_key"
                    :errors="addressErrors"
                    :disabled="!canUpdate"
                    with-country-code
                />

                <div class="section-label">Social Links</div>
                <div class="form-grid">
                    <TextField
                        v-model="form.facebook_link"
                        name="facebook_link"
                        type="url"
                        label="Facebook"
                        placeholder="https://facebook.com/…"
                        :disabled="!canUpdate"
                        :error="form.errors.facebook_link"
                    />
                    <TextField
                        v-model="form.instagram_link"
                        name="instagram_link"
                        type="url"
                        label="Instagram"
                        placeholder="https://instagram.com/…"
                        :disabled="!canUpdate"
                        :error="form.errors.instagram_link"
                    />
                    <TextField
                        v-model="form.linkedin_link"
                        name="linkedin_link"
                        type="url"
                        label="LinkedIn"
                        placeholder="https://linkedin.com/…"
                        :disabled="!canUpdate"
                        :error="form.errors.linkedin_link"
                    />
                    <TextField
                        v-model="form.twitter_link"
                        name="twitter_link"
                        type="url"
                        label="X / Twitter"
                        placeholder="https://x.com/…"
                        :disabled="!canUpdate"
                        :error="form.errors.twitter_link"
                    />
                    <TextField
                        v-model="form.tiktok_link"
                        name="tiktok_link"
                        type="url"
                        label="TikTok"
                        placeholder="https://tiktok.com/@…"
                        :disabled="!canUpdate"
                        :error="form.errors.tiktok_link"
                    />
                </div>

                <PermissionGuard permission="UPDATE_COMPANY_DATA">
                    <div class="form-actions">
                        <SubmitButton
                            label="Save Changes"
                            icon="pi pi-check"
                            :loading="form.processing"
                            :disabled="!canSubmit"
                        />
                    </div>
                </PermissionGuard>
            </form>
        </section>

        <!-- ══ Branding ══ -->
        <section class="card">
            <h2 class="card__title">Branding</h2>
            <p class="card__desc">PNG only, up to 2 MB. Used across emails, the login hero and the navbar.</p>

            <div class="logos">
                <div v-for="slot in logoSlots" :key="slot.type" class="logo-slot">
                    <div class="logo-slot__head">
                        <div>
                            <h3 class="logo-slot__title">{{ slot.label }}</h3>
                            <p class="logo-slot__desc">{{ slot.description }}</p>
                        </div>
                    </div>

                    <div class="logo-slot__preview" :class="{ 'logo-slot__preview--dark': slot.dark }">
                        <img :src="slot.currentUrl" :alt="`${slot.label} preview`" class="logo-slot__img" />
                    </div>

                    <PermissionGuard permission="UPDATE_COMPANY_DATA">
                        <FileUpload
                            v-model="logoFiles[slot.type]"
                            :label="`Replace ${slot.label.toLowerCase()}`"
                            accept="image/png"
                            :max-size-mb="2"
                            icon="pi pi-image"
                            :disabled="uploadingLogo === slot.type"
                        />
                        <div class="logo-slot__actions">
                            <Button
                                size="small"
                                label="Upload"
                                icon="pi pi-upload"
                                :loading="uploadingLogo === slot.type"
                                :disabled="!logoFiles[slot.type]"
                                @click="uploadLogo(slot.type)"
                            />
                            <button
                                type="button"
                                class="link-danger"
                                aria-label="Remove logo"
                                @click="removeLogo(slot.type)"
                            >
                                <i class="pi pi-trash" aria-hidden="true" /> Remove
                            </button>
                        </div>
                    </PermissionGuard>
                </div>
            </div>
        </section>

        <!-- ══ Signature ══ -->
        <section class="card">
            <div class="card__head">
                <h2 class="card__title">Signature</h2>
                <span class="badge" :class="hasSignature ? 'badge--success' : 'badge--neutral'">
                    {{ hasSignature ? 'On file' : 'Not set' }}
                </span>
            </div>
            <p class="card__desc">Rendered on generated documents. Draw it below or upload a PNG/PDF (max 2 MB).</p>

            <PermissionGuard permission="UPDATE_COMPANY_DATA">
                <template #fallback>
                    <p class="muted">You don't have permission to change the signature.</p>
                </template>

                <div class="sig-modes" role="tablist" aria-label="Signature input mode">
                    <Button
                        size="small"
                        :outlined="signatureMode !== 'draw'"
                        label="Draw"
                        icon="pi pi-pencil"
                        role="tab"
                        :aria-selected="signatureMode === 'draw'"
                        @click="signatureMode = 'draw'"
                    />
                    <Button
                        size="small"
                        :outlined="signatureMode !== 'upload'"
                        label="Upload"
                        icon="pi pi-upload"
                        role="tab"
                        :aria-selected="signatureMode === 'upload'"
                        @click="signatureMode = 'upload'"
                    />
                </div>

                <!-- Draw -->
                <div v-if="signatureMode === 'draw'" class="sig-panel">
                    <SignaturePad ref="signaturePad" :height="200" @change="signatureEmpty = $event" />
                    <div class="sig-actions">
                        <Button
                            label="Save signature"
                            icon="pi pi-check"
                            :loading="savingSignature"
                            :disabled="signatureEmpty"
                            @click="saveDrawnSignature"
                        />
                    </div>
                </div>

                <!-- Upload -->
                <div v-else class="sig-panel">
                    <FileUpload
                        v-model="signatureFile"
                        label="Signature file"
                        accept=".png,.pdf"
                        :max-size-mb="2"
                        icon="pi pi-file"
                    />
                    <div class="sig-actions">
                        <Button
                            label="Save signature"
                            icon="pi pi-check"
                            :loading="savingSignature"
                            :disabled="!signatureFile"
                            @click="saveUploadedSignature"
                        />
                    </div>
                </div>

                <div v-if="hasSignature" class="sig-current">
                    <span><i class="pi pi-verified" aria-hidden="true" /> A signature is currently stored.</span>
                    <button type="button" class="link-danger" aria-label="Remove signature" @click="removeSignature">
                        <i class="pi pi-trash" aria-hidden="true" /> Remove
                    </button>
                </div>
            </PermissionGuard>
        </section>
    </div>
</template>

<style scoped>
.company-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--space-6);
    align-items: start;
}

.card {
    background: color-mix(in srgb, var(--bg-surface) 60%, transparent);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-2xl);
    padding: var(--space-6) var(--space-8);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

.card__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
    margin-bottom: var(--space-2);
}

.card__title {
    font-size: var(--text-lg);
    font-weight: var(--font-bold);
    color: var(--text-primary);
    margin: 0 0 var(--space-5);
}

.card__head .card__title {
    margin: 0;
}

.card__desc {
    margin: 0 0 var(--space-5);
    font-size: var(--text-sm);
    color: var(--text-muted);
}

.section-label {
    margin: var(--space-6) 0 var(--space-4);
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    text-transform: uppercase;
    letter-spacing: 1.2px;
    color: var(--text-secondary);
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-4);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: var(--space-6);
}

/* ── Branding ── */
.logos {
    display: flex;
    flex-direction: column;
    gap: var(--space-6);
}

.logo-slot {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    padding-bottom: var(--space-6);
    border-bottom: 1px solid var(--border-subtle);
}

.logo-slot:last-child {
    padding-bottom: 0;
    border-bottom: none;
}

.logo-slot__title {
    font-size: var(--text-base);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
    margin: 0 0 var(--space-1);
}

.logo-slot__desc {
    margin: 0;
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.logo-slot__preview {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 84px;
    padding: var(--space-3);
    border-radius: var(--radius-md);
    background: var(--bg-elevated);
    border: 1px solid var(--border-subtle);
}

.logo-slot__preview--dark {
    background: var(--bg-void);
}

.logo-slot__img {
    max-height: 100%;
    max-width: 100%;
    object-fit: contain;
}

.logo-slot__actions {
    display: flex;
    align-items: center;
    gap: var(--space-4);
}

/* ── Signature ── */
.sig-modes {
    display: flex;
    gap: var(--space-2);
    margin-bottom: var(--space-4);
}

.sig-panel {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}

.sig-actions {
    display: flex;
    justify-content: flex-end;
}

.sig-current {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
    margin-top: var(--space-5);
    padding-top: var(--space-4);
    border-top: 1px solid var(--border-subtle);
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

.sig-current .pi-verified {
    color: var(--accent-success);
    margin-right: var(--space-1);
}

/* ── Shared bits ── */
.link-danger {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
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

.link-danger:hover {
    color: color-mix(in srgb, var(--accent-error) 70%, var(--text-primary));
    text-decoration: underline;
}

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

.badge--neutral {
    background: color-mix(in srgb, var(--text-muted) 18%, transparent);
    color: var(--text-muted);
}

.muted {
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-muted);
}

.empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-16) var(--space-6);
    color: var(--text-muted);
}

.empty .pi {
    font-size: var(--text-3xl);
}

/* ── Responsive ── */
@media (min-width: 1024px) {
    .company-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .card--wide {
        grid-column: 1 / -1;
    }
}

@media (max-width: 560px) {
    .card {
        padding: var(--space-5) var(--space-4);
    }

    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>
