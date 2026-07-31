<script setup lang="ts">
/**
 * Shared create / edit lead form — used by Create/Edit pages and by
 * AppointmentFormDialog (modal). Owns its Inertia `useForm` and submits:
 *
 *   · create → POST /appointments
 *   · edit   → PUT  /appointments/{uuid}
 *
 * When `variant="dialog"`, success/cancel are emitted for the parent modal
 * instead of navigating away.
 */
import { computed, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import TextField from '@/common/form/TextField.vue';
import SelectField from '@/common/form/SelectField.vue';
import TextareaField from '@/common/form/TextareaField.vue';
import PhoneField from '@/common/form/PhoneField.vue';
import DateField from '@/common/form/DateField.vue';
import TimeField from '@/common/form/TimeField.vue';
import SubmitButton from '@/common/form/SubmitButton.vue';
import AddressAutocomplete from '@/common/address/AddressAutocomplete.vue';
import SecondaryButton from '@/volt/SecondaryButton.vue';
import ToggleSwitch from '@/volt/ToggleSwitch.vue';
import type { AddressValue } from '@/common/address/types';
import type { SharedProps } from '@/types/inertia';
import { CLIENT_TYPE_OPTIONS } from '@/modules/appointments/helpers/options';
import { serviceOptionsToSelect } from '@/modules/appointments/helpers/serviceLabel';
import { appointmentFormSchema, NAME_MAX, NAME_MIN, type AppointmentFormValues } from '@/modules/appointments/schemas/appointmentFormSchema';
import type { AppointmentEditData, ServiceOption } from '@/modules/appointments/types';

const props = withDefaults(
    defineProps<{
        mode: 'create' | 'edit';
        appointment?: AppointmentEditData | null;
        serviceOptions: ServiceOption[];
        /** `dialog` hides page actions and emits saved/cancel for AppModal. */
        variant?: 'page' | 'dialog';
    }>(),
    { appointment: null, serviceOptions: () => [], variant: 'page' },
);

const emit = defineEmits<{ saved: []; cancel: [] }>();

const isEdit = computed<boolean>(() => props.mode === 'edit');
const isDialog = computed<boolean>(() => props.variant === 'dialog');
const page = usePage<SharedProps>();
const serviceSelectOptions = computed(() => serviceOptionsToSelect(props.serviceOptions));

const form = useForm<AppointmentFormValues & { scheduled_date: string; scheduled_time: string }>({
    first_name: props.appointment?.first_name ?? '',
    last_name: props.appointment?.last_name ?? '',
    client_type: props.appointment?.client_type ?? 'individual',
    company_name: props.appointment?.company_name ?? '',
    service_uuid: props.appointment?.service_uuid ?? '',
    email: props.appointment?.email ?? '',
    phone: props.appointment?.phone ?? '',
    address: props.appointment?.address ?? '',
    address_2: props.appointment?.address_2 ?? '',
    zip_code: props.appointment?.zip_code ?? '',
    city: props.appointment?.city ?? '',
    state: props.appointment?.state ?? '',
    country: props.appointment?.country ?? '',
    country_code: props.appointment?.country_code ?? '',
    latitude: props.appointment?.latitude ?? null,
    longitude: props.appointment?.longitude ?? null,
    sms_consent: props.appointment?.sms_consent ?? false,
    notes: props.appointment?.notes ?? '',
    owner: props.appointment?.owner ?? '',
    scheduled_at: '',
    scheduled_date: '',
    scheduled_time: '',
});

watch(
    () => props.appointment,
    (appointment) => {
        if (!appointment || !isDialog.value) {
            return;
        }
        form.clearErrors();
        form.first_name = appointment.first_name ?? '';
        form.last_name = appointment.last_name ?? '';
        form.client_type = appointment.client_type ?? 'individual';
        form.company_name = appointment.company_name ?? '';
        form.service_uuid = appointment.service_uuid ?? '';
        form.email = appointment.email ?? '';
        form.phone = appointment.phone ?? '';
        form.address = appointment.address ?? '';
        form.address_2 = appointment.address_2 ?? '';
        form.zip_code = appointment.zip_code ?? '';
        form.city = appointment.city ?? '';
        form.state = appointment.state ?? '';
        form.country = appointment.country ?? '';
        form.country_code = appointment.country_code ?? '';
        form.latitude = appointment.latitude ?? null;
        form.longitude = appointment.longitude ?? null;
        form.sms_consent = appointment.sms_consent ?? false;
        form.notes = appointment.notes ?? '';
        form.owner = appointment.owner ?? '';
        form.scheduled_date = '';
        form.scheduled_time = '';
    },
);

/* ── Names — letters only, no spaces, 3–20 chars, auto-capitalized ──────── */
function toNameValue(value: string): string {
    const letters = value.replace(/[^\p{L}]/gu, '').slice(0, NAME_MAX);
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

/* ── Client type — company_name only applies to a company lead ─────────── */
const isCompany = computed<boolean>(() => form.client_type === 'company');

watch(isCompany, (company) => {
    if (!company) {
        form.company_name = '';
    }
});

/** Bridges SelectField's `string | null` model onto the narrow enum field. */
const clientTypeModel = computed<string | null>({
    get: () => form.client_type,
    set: (value) => {
        form.client_type = value === 'company' ? 'company' : 'individual';
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

/* ── Submit ───────────────────────────────────────────────────────────── */
const submitLabel = computed<string>(() => (isEdit.value ? 'Save changes' : 'Create lead'));
const submitIcon = computed<string>(() => (isEdit.value ? 'pi pi-check' : 'pi pi-user-plus'));

/** Trim + collapse empty optional strings to null for the backend payload. */
function emptyToNull(value: string): string | null {
    const trimmed = value.trim();
    return trimmed === '' ? null : trimmed;
}

function submit(): void {
    const parsed = appointmentFormSchema.safeParse(form.data());
    if (!parsed.success) {
        form.clearErrors();
        for (const issue of parsed.error.issues) {
            const key = issue.path[0];
            if (typeof key === 'string') {
                form.setError(key as keyof AppointmentFormValues, issue.message);
            }
        }
        return;
    }

    const edit = isEdit.value;
    form.transform((data) => {
        const payload: Record<string, unknown> = {
            first_name: data.first_name.trim(),
            last_name: data.last_name.trim(),
            client_type: data.client_type,
            company_name: data.client_type === 'company' ? emptyToNull(data.company_name) : null,
            service_uuid: emptyToNull(data.service_uuid),
            email: data.email.trim(),
            phone: emptyToNull(data.phone),
            address: emptyToNull(data.address),
            address_2: emptyToNull(data.address_2),
            zip_code: emptyToNull(data.zip_code),
            city: emptyToNull(data.city),
            state: emptyToNull(data.state),
            country: emptyToNull(data.country),
            country_code: emptyToNull(data.country_code),
            latitude: data.latitude,
            longitude: data.longitude,
            sms_consent: data.sms_consent,
            notes: emptyToNull(data.notes),
            owner: emptyToNull(data.owner),
        };
        if (!edit && data.scheduled_date) {
            payload.scheduled_at = `${data.scheduled_date}T${data.scheduled_time || '09:00'}:00`;
        }
        return payload;
    });

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            if (isDialog.value) {
                emit('saved');
            }
        },
    };
    if (edit) {
        form.put(`/appointments/${props.appointment!.uuid}`, options);
    } else {
        form.post('/appointments', options);
    }
}

function onCancel(): void {
    if (isDialog.value) {
        emit('cancel');
        return;
    }
    router.visit('/appointments');
}

defineExpose({
    submit,
    processing: computed(() => form.processing),
});
</script>

<template>
    <form class="appt-form" @submit.prevent="submit">
        <!-- ── Lead type ── -->
        <section class="appt-form__section">
            <header class="appt-form__section-head">
                <h3 class="appt-form__section-title">Lead type</h3>
            </header>
            <div class="appt-form__grid">
                <SelectField
                    v-model="clientTypeModel"
                    name="client_type"
                    label="Client type"
                    required
                    :options="CLIENT_TYPE_OPTIONS"
                    :error="form.errors.client_type"
                />
                <TextField
                    v-if="isCompany"
                    v-model="form.company_name"
                    name="company_name"
                    label="Company name"
                    placeholder="e.g. Acme Inc."
                    required
                    :maxlength="255"
                    autocomplete="organization"
                    :error="form.errors.company_name"
                />
                <SelectField
                    v-model="form.service_uuid"
                    name="service_uuid"
                    label="Service"
                    placeholder="Select a service"
                    show-clear
                    :options="serviceSelectOptions"
                    :error="form.errors.service_uuid"
                />
            </div>
        </section>

        <!-- ── Contact ── -->
        <section class="appt-form__section">
            <header class="appt-form__section-head">
                <h3 class="appt-form__section-title">Contact</h3>
            </header>
            <div class="appt-form__grid">
                <TextField
                    v-model="firstNameModel"
                    name="first_name"
                    label="First name"
                    placeholder="e.g. Ada"
                    required
                    :maxlength="NAME_MAX"
                    autocomplete="given-name"
                    :error="form.errors.first_name"
                    :hint="`Letters only, no spaces — ${NAME_MIN} to ${NAME_MAX} characters.`"
                />
                <TextField
                    v-model="lastNameModel"
                    name="last_name"
                    label="Last name"
                    placeholder="e.g. Lovelace"
                    required
                    :maxlength="NAME_MAX"
                    autocomplete="family-name"
                    :error="form.errors.last_name"
                    :hint="`Letters only, no spaces — ${NAME_MIN} to ${NAME_MAX} characters.`"
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
        <section class="appt-form__section">
            <header class="appt-form__section-head">
                <h3 class="appt-form__section-title">Address</h3>
            </header>
            <AddressAutocomplete
                v-model="addressModel"
                :api-key="page.props.config.google_maps_api_key"
                :errors="addressErrors"
                with-country-code
            />
        </section>

        <!-- ── Schedule a meeting (create only, optional) ── -->
        <section v-if="!isEdit" class="appt-form__section">
            <header class="appt-form__section-head">
                <h3 class="appt-form__section-title">Schedule a meeting</h3>
                <p class="appt-form__section-hint">
                    Optional — leave blank to capture the lead without a meeting time yet.
                </p>
            </header>
            <div class="appt-form__grid">
                <DateField
                    v-model="form.scheduled_date"
                    name="scheduled_date"
                    label="Meeting date"
                    placeholder="Select a date"
                    :error="form.errors.scheduled_at"
                />
                <TimeField
                    v-model="form.scheduled_time"
                    name="scheduled_time"
                    label="Meeting time"
                    placeholder="Select a time"
                />
            </div>
        </section>

        <!-- ── Notes & assignment ── -->
        <section class="appt-form__section">
            <header class="appt-form__section-head">
                <h3 class="appt-form__section-title">Notes & assignment</h3>
            </header>
            <div class="appt-form__toggle">
                <div class="appt-form__toggle-copy">
                    <span class="appt-form__toggle-label">SMS consent</span>
                    <span class="appt-form__toggle-hint">This lead agreed to receive SMS messages.</span>
                </div>
                <ToggleSwitch v-model="form.sms_consent" input-id="sms_consent" aria-label="SMS consent" />
            </div>
            <div class="appt-form__grid">
                <TextField
                    v-model="form.owner"
                    name="owner"
                    label="Owner"
                    placeholder="Assignee (optional)"
                    :maxlength="255"
                    :error="form.errors.owner"
                />
            </div>
            <TextareaField
                v-model="form.notes"
                name="notes"
                label="Notes"
                placeholder="Internal notes about this lead…"
                :rows="4"
                :maxlength="5000"
                :error="form.errors.notes"
            />
        </section>

        <div v-if="!isDialog" class="appt-form__actions">
            <SecondaryButton
                type="button"
                label="Cancel"
                :disabled="form.processing"
                @click="onCancel"
            />
            <SubmitButton :label="submitLabel" :icon="submitIcon" :loading="form.processing" />
        </div>
        <button v-else type="submit" class="appt-form__enter" tabindex="-1" aria-hidden="true" />
    </form>
</template>

<style scoped>
.appt-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-7);
}

.appt-form__section {
    display: flex;
    flex-direction: column;
    gap: var(--space-6);
}

.appt-form__section-head {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
    padding-bottom: var(--space-3);
    border-bottom: 1px solid var(--border-default);
}

.appt-form__section-title {
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
.appt-form__section-title::before {
    content: '';
    width: 3px;
    height: 1.1em;
    flex-shrink: 0;
    border-radius: var(--radius-full, 99px);
    background: var(--grad-primary, var(--accent-primary));
}

.appt-form__section-hint {
    margin: 0;
    padding-left: calc(3px + var(--space-2));
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.appt-form__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-3);
}

.appt-form__toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
    padding: var(--space-4);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--bg-elevated) 40%, transparent);
}

.appt-form__toggle-copy {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
}

.appt-form__toggle-label {
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--text-primary);
}

.appt-form__toggle-hint {
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.appt-form__actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: var(--space-3);
    margin-top: var(--space-2);
}

.appt-form__enter {
    display: none;
}

@media (max-width: 640px) {
    .appt-form__grid {
        grid-template-columns: 1fr;
    }
}
</style>
