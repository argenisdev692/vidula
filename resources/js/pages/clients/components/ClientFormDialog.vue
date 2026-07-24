<script setup lang="ts">
/**
 * Create / edit modal for a CRM client. No dedicated create/edit routes —
 * store/update return back(), so the form lives in an AppModal on Index.
 *
 *   · create → POST /clients
 *   · edit   → PUT  /clients/{uuid}
 *
 * PhoneField emits E.164; empty optional strings are mapped to null on submit.
 */
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import TextField from '@/common/form/TextField.vue';
import TextareaField from '@/common/form/TextareaField.vue';
import PhoneField from '@/common/form/PhoneField.vue';
import SelectField from '@/common/form/SelectField.vue';
import AppModal from '@/common/ui/AppModal.vue';
import { clientFormSchema, type ClientFormValues } from '@/modules/clients/schemas/clientFormSchema';
import type { Client } from '@/modules/clients/types';
import type { SelectOption } from '@/common/form/types';

const visible = defineModel<boolean>('visible', { default: false });

const props = withDefaults(
    defineProps<{
        mode?: 'create' | 'edit';
        client?: Client | null;
    }>(),
    { mode: 'create', client: null },
);

const emit = defineEmits<{ saved: [] }>();

const form = useForm<ClientFormValues>({
    client_name: '',
    email: '',
    status: 'DRAFT',
    phone: '',
    address: '',
    tax_id: '',
    nif: '',
    website: '',
    facebook_link: '',
    instagram_link: '',
    linkedin_link: '',
    twitter_link: '',
    notes: '',
});

const isEdit = computed<boolean>(() => props.mode === 'edit');
const dialogTitle = computed<string>(() => (isEdit.value ? 'Edit client' : 'New client'));

const phoneModel = computed<string | null>({
    get: () => form.phone || null,
    set: (value) => {
        form.phone = value ?? '';
    },
});

const statusModel = computed<string | null>({
    get: () => form.status,
    set: (value) => {
        form.status = (value as ClientFormValues['status']) || 'DRAFT';
    },
});

const lifecycleOptions: SelectOption[] = [
    { label: 'Draft', value: 'DRAFT' },
    { label: 'Active', value: 'ACTIVE' },
    { label: 'Archived', value: 'ARCHIVED' },
];

watch(visible, (open) => {
    if (!open) {
        return;
    }
    form.clearErrors();
    form.client_name = props.client?.client_name ?? '';
    form.email = props.client?.email ?? '';
    form.status = props.client?.status ?? 'DRAFT';
    form.phone = props.client?.phone ?? '';
    form.address = props.client?.address ?? '';
    form.tax_id = props.client?.tax_id ?? '';
    form.nif = props.client?.nif ?? '';
    form.website = props.client?.website ?? '';
    form.facebook_link = props.client?.facebook_link ?? '';
    form.instagram_link = props.client?.instagram_link ?? '';
    form.linkedin_link = props.client?.linkedin_link ?? '';
    form.twitter_link = props.client?.twitter_link ?? '';
    form.notes = props.client?.notes ?? '';
});

function close(): void {
    visible.value = false;
}

function emptyToNull(value: string): string | null {
    const trimmed = value.trim();
    return trimmed === '' ? null : trimmed;
}

function submit(): void {
    const parsed = clientFormSchema.safeParse({
        client_name: form.client_name,
        email: form.email,
        status: form.status,
        phone: form.phone,
        address: form.address,
        tax_id: form.tax_id,
        nif: form.nif,
        website: form.website,
        facebook_link: form.facebook_link,
        instagram_link: form.instagram_link,
        linkedin_link: form.linkedin_link,
        twitter_link: form.twitter_link,
        notes: form.notes,
    });

    if (!parsed.success) {
        form.clearErrors();
        for (const issue of parsed.error.issues) {
            const key = issue.path[0];
            if (typeof key === 'string') {
                form.setError(key as keyof ClientFormValues, issue.message);
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

    form.transform((data) => ({
        client_name: data.client_name.trim(),
        email: emptyToNull(data.email),
        status: data.status,
        phone: data.phone.trim(),
        address: emptyToNull(data.address),
        tax_id: emptyToNull(data.tax_id),
        nif: emptyToNull(data.nif),
        website: emptyToNull(data.website),
        facebook_link: emptyToNull(data.facebook_link),
        instagram_link: emptyToNull(data.instagram_link),
        linkedin_link: emptyToNull(data.linkedin_link),
        twitter_link: emptyToNull(data.twitter_link),
        notes: emptyToNull(data.notes),
    }));

    if (isEdit.value) {
        form.put(`/clients/${props.client!.uuid}`, options);
    } else {
        form.post('/clients', options);
    }
}
</script>

<template>
    <AppModal
        v-model:visible="visible"
        :title="dialogTitle"
        :subtitle="isEdit ? 'Update this client’s details.' : 'Add a CRM contact.'"
        icon="pi pi-briefcase"
        :confirm-label="isEdit ? 'Save changes' : 'Create client'"
        confirm-icon="pi pi-check"
        :loading="form.processing"
        :dismissable="!form.processing"
        width="40rem"
        @confirm="submit"
        @cancel="close"
    >
        <form class="client-form" @submit.prevent="submit">
            <TextField
                v-model="form.client_name"
                name="client_name"
                label="Name"
                placeholder="e.g. Acme Corp"
                required
                :maxlength="255"
                :error="form.errors.client_name"
            />

            <div class="client-form__row">
                <SelectField
                    v-model="statusModel"
                    name="status"
                    label="Lifecycle"
                    required
                    :options="lifecycleOptions"
                    :error="form.errors.status"
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
                v-model="form.email"
                name="email"
                label="Email"
                type="email"
                placeholder="ops@acme.test"
                :maxlength="255"
                :error="form.errors.email"
            />

            <TextField
                v-model="form.address"
                name="address"
                label="Address"
                placeholder="Street address"
                :maxlength="255"
                :error="form.errors.address"
            />

            <div class="client-form__row">
                <TextField
                    v-model="form.tax_id"
                    name="tax_id"
                    label="Tax ID"
                    :maxlength="255"
                    :error="form.errors.tax_id"
                />
                <TextField
                    v-model="form.nif"
                    name="nif"
                    label="NIF"
                    :maxlength="255"
                    :error="form.errors.nif"
                />
            </div>

            <TextField
                v-model="form.website"
                name="website"
                label="Website"
                placeholder="https://"
                :maxlength="255"
                :error="form.errors.website"
            />

            <div class="client-form__row">
                <TextField
                    v-model="form.facebook_link"
                    name="facebook_link"
                    label="Facebook"
                    placeholder="https://"
                    :maxlength="255"
                    :error="form.errors.facebook_link"
                />
                <TextField
                    v-model="form.instagram_link"
                    name="instagram_link"
                    label="Instagram"
                    placeholder="https://"
                    :maxlength="255"
                    :error="form.errors.instagram_link"
                />
            </div>

            <div class="client-form__row">
                <TextField
                    v-model="form.linkedin_link"
                    name="linkedin_link"
                    label="LinkedIn"
                    placeholder="https://"
                    :maxlength="255"
                    :error="form.errors.linkedin_link"
                />
                <TextField
                    v-model="form.twitter_link"
                    name="twitter_link"
                    label="Twitter / X"
                    placeholder="https://"
                    :maxlength="255"
                    :error="form.errors.twitter_link"
                />
            </div>

            <TextareaField
                v-model="form.notes"
                name="notes"
                label="Notes"
                placeholder="Internal notes…"
                :rows="3"
                :maxlength="5000"
                :error="form.errors.notes"
            />

            <button type="submit" class="client-form__enter" tabindex="-1" aria-hidden="true" />
        </form>
    </AppModal>
</template>

<style scoped>
.client-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.client-form__row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-4);
}

@media (max-width: 640px) {
    .client-form__row {
        grid-template-columns: 1fr;
    }
}

.client-form__enter {
    display: none;
}
</style>
