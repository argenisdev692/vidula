<script setup lang="ts">
/**
 * Create / edit modal for a contact-support submission. There is no `GET /create`
 * or `GET /{uuid}/edit` route — the backend store/update return `back()`
 * redirects — so the form lives in a Volt Dialog on the Index page and submits
 * plain JSON via Inertia `useForm`:
 *
 *   · create → POST /contact-supports
 *   · edit   → PUT  /contact-supports/{uuid}
 *
 * Built from the reusable common/form kit (TextField, PhoneField, TextareaField)
 * plus the Volt ToggleSwitch for the SMS-consent flag. Client-side Zod validation
 * mirrors the backend but the server stays authoritative; server errors surface
 * through `form.errors`. The `readed` flag is NEVER part of this form.
 */
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import TextField from '@/common/form/TextField.vue';
import PhoneField from '@/common/form/PhoneField.vue';
import TextareaField from '@/common/form/TextareaField.vue';
import ToggleSwitch from '@/volt/ToggleSwitch.vue';
import AppModal from '@/common/ui/AppModal.vue';
import {
    contactSupportFormSchema,
    type ContactSupportFormValues,
} from '@/modules/contact-support/schemas/contactSupportFormSchema';
import type { ContactSupport } from '@/modules/contact-support/types';

const visible = defineModel<boolean>('visible', { default: false });

const props = withDefaults(
    defineProps<{
        mode?: 'create' | 'edit';
        contact?: ContactSupport | null;
        /** The full message body, loaded lazily by the parent when editing. */
        message?: string;
    }>(),
    { mode: 'create', contact: null, message: '' },
);

const emit = defineEmits<{ saved: [] }>();

const form = useForm<ContactSupportFormValues>({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    subject: '',
    message: '',
    sms_consent: false,
});

const isEdit = computed<boolean>(() => props.mode === 'edit');
const dialogTitle = computed<string>(() => (isEdit.value ? 'Edit contact request' : 'New contact request'));

/** Bridge PhoneField's `string | null` E.164 model onto the flat string form. */
const phoneModel = computed<string | null>({
    get: () => form.phone || null,
    set: (value) => {
        form.phone = value ?? '';
    },
});

/** Re-seed the form each time the dialog opens (never carry stale state). */
watch(visible, (open) => {
    if (!open) {
        return;
    }
    form.clearErrors();
    form.first_name = props.contact?.first_name ?? '';
    form.last_name = props.contact?.last_name ?? '';
    form.email = props.contact?.email ?? '';
    form.phone = props.contact?.phone ?? '';
    form.subject = props.contact?.subject ?? '';
    form.message = props.message ?? '';
    form.sms_consent = props.contact?.sms_consent ?? false;
});

function close(): void {
    visible.value = false;
}

function submit(): void {
    const parsed = contactSupportFormSchema.safeParse({
        first_name: form.first_name,
        last_name: form.last_name,
        email: form.email,
        phone: form.phone,
        subject: form.subject,
        message: form.message,
        sms_consent: form.sms_consent,
    });

    if (!parsed.success) {
        form.clearErrors();
        for (const issue of parsed.error.issues) {
            const key = issue.path[0];
            if (typeof key === 'string') {
                form.setError(key as keyof ContactSupportFormValues, issue.message);
            }
        }
        return;
    }

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            close();
        },
    };

    if (isEdit.value) {
        form.put(`/contact-supports/${props.contact!.uuid}`, options);
    } else {
        form.post('/contact-supports', options);
    }
}
</script>

<template>
    <AppModal
        v-model:visible="visible"
        :title="dialogTitle"
        :subtitle="isEdit ? 'Update this contact request’s details.' : 'Log a new contact request.'"
        icon="pi pi-envelope"
        :confirm-label="isEdit ? 'Save changes' : 'Create request'"
        confirm-icon="pi pi-check"
        :loading="form.processing"
        :dismissable="!form.processing"
        width="38rem"
        @confirm="submit"
        @cancel="close"
    >
        <form class="cs-form" @submit.prevent="submit">
            <div class="cs-form__row">
                <TextField
                    v-model="form.first_name"
                    name="first_name"
                    label="First name"
                    placeholder="e.g. Jane"
                    required
                    :maxlength="255"
                    autocomplete="given-name"
                    :error="form.errors.first_name"
                />
                <TextField
                    v-model="form.last_name"
                    name="last_name"
                    label="Last name"
                    placeholder="e.g. Doe"
                    required
                    :maxlength="255"
                    autocomplete="family-name"
                    :error="form.errors.last_name"
                />
            </div>

            <div class="cs-form__row">
                <TextField
                    v-model="form.email"
                    name="email"
                    label="Email"
                    type="email"
                    placeholder="jane@example.com"
                    required
                    :maxlength="255"
                    autocomplete="email"
                    :error="form.errors.email"
                />
                <PhoneField
                    v-model="phoneModel"
                    name="phone"
                    label="Phone"
                    required
                    :error="form.errors.phone"
                />
            </div>

            <TextField
                v-model="form.subject"
                name="subject"
                label="Subject"
                placeholder="What is this about?"
                required
                :maxlength="150"
                :error="form.errors.subject"
            />

            <TextareaField
                v-model="form.message"
                name="message"
                label="Message"
                placeholder="Describe the request…"
                :rows="5"
                :maxlength="5000"
                required
                :error="form.errors.message"
                hint="Up to 5000 characters."
            />

            <div class="cs-form__consent">
                <ToggleSwitch v-model="form.sms_consent" input-id="sms_consent" aria-label="SMS consent" />
                <label class="cs-form__consent-label" for="sms_consent">
                    Contact consents to receiving SMS messages.
                </label>
            </div>

            <!-- Hidden submit lets Enter submit the form from any field. -->
            <button type="submit" class="cs-form__enter" tabindex="-1" aria-hidden="true" />
        </form>
    </AppModal>
</template>

<style scoped>
.cs-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.cs-form__row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-4);
}

.cs-form__consent {
    display: flex;
    align-items: center;
    gap: var(--space-3);
}

.cs-form__consent-label {
    font-size: var(--text-sm);
    color: var(--text-secondary);
    cursor: pointer;
}

.cs-form__enter {
    display: none;
}

@media (max-width: 560px) {
    .cs-form__row {
        grid-template-columns: 1fr;
    }
}
</style>
