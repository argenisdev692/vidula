<script setup lang="ts">
/**
 * Create / edit modal for a service. There is no `GET /create` or
 * `GET /{uuid}/edit` route — the backend store/update return `back()`
 * redirects — so the form lives in a Volt Dialog on the Index page and
 * submits plain JSON via Inertia `useForm`:
 *
 *   · create → POST /services
 *   · edit   → PUT  /services/{uuid}
 *
 * Built from the reusable common/form kit (TextField, TextareaField) plus the
 * Volt ToggleSwitch for the catalog-visibility flag. The slug auto-fills from
 * the name on create until the user edits it directly (mirrors the username
 * auto-generation in UserForm, without the server-side availability check —
 * uniqueness stays server-validated on submit). Client-side Zod validation
 * mirrors the backend but the server stays authoritative; server errors
 * surface through `form.errors`.
 */
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import TextField from '@/common/form/TextField.vue';
import TextareaField from '@/common/form/TextareaField.vue';
import ToggleSwitch from '@/volt/ToggleSwitch.vue';
import AppModal from '@/common/ui/AppModal.vue';
import { serviceFormSchema, type ServiceFormValues } from '@/modules/services/schemas/serviceFormSchema';
import { slugify } from '@/modules/services/helpers/slugify';
import type { Service } from '@/modules/services/types';

const visible = defineModel<boolean>('visible', { default: false });

const props = withDefaults(
    defineProps<{
        mode?: 'create' | 'edit';
        service?: Service | null;
    }>(),
    { mode: 'create', service: null },
);

const emit = defineEmits<{ saved: [] }>();

const form = useForm<ServiceFormValues>({
    name: '',
    slug: '',
    description: '',
    is_active: true,
    sort_order: '0',
});

const isEdit = computed<boolean>(() => props.mode === 'edit');
const dialogTitle = computed<string>(() => (isEdit.value ? 'Edit service' : 'New service'));

/** Typing the slug directly freezes auto-generation from the name. */
const slugTouched = ref<boolean>(isEdit.value);

const slugModel = computed<string>({
    get: () => form.slug,
    set: (next) => {
        if (next !== slugify(form.name)) {
            slugTouched.value = true;
        }
        form.slug = next;
    },
});

watch(
    () => form.name,
    (name) => {
        if (!slugTouched.value) {
            form.slug = slugify(name);
        }
    },
);

/** Re-seed the form each time the dialog opens (never carry stale state). */
watch(visible, (open) => {
    if (!open) {
        return;
    }
    form.clearErrors();
    slugTouched.value = isEdit.value;
    form.name = props.service?.name ?? '';
    form.slug = props.service?.slug ?? '';
    form.description = props.service?.description ?? '';
    form.is_active = props.service?.is_active ?? true;
    form.sort_order = String(props.service?.sort_order ?? 0);
});

function close(): void {
    visible.value = false;
}

function submit(): void {
    const parsed = serviceFormSchema.safeParse({
        name: form.name,
        slug: form.slug,
        description: form.description,
        is_active: form.is_active,
        sort_order: form.sort_order,
    });

    if (!parsed.success) {
        form.clearErrors();
        for (const issue of parsed.error.issues) {
            const key = issue.path[0];
            if (typeof key === 'string') {
                form.setError(key as keyof ServiceFormValues, issue.message);
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
        name: data.name,
        slug: data.slug,
        description: data.description,
        is_active: data.is_active,
        sort_order: data.sort_order === '' ? 0 : Number(data.sort_order),
    }));

    if (isEdit.value) {
        form.put(`/services/${props.service!.uuid}`, options);
    } else {
        form.post('/services', options);
    }
}
</script>

<template>
    <AppModal
        v-model:visible="visible"
        :title="dialogTitle"
        :subtitle="isEdit ? 'Update this service’s details.' : 'Add a service to the catalog.'"
        icon="pi pi-briefcase"
        :confirm-label="isEdit ? 'Save changes' : 'Create service'"
        confirm-icon="pi pi-check"
        :loading="form.processing"
        :dismissable="!form.processing"
        width="34rem"
        @confirm="submit"
        @cancel="close"
    >
        <form class="svc-form" @submit.prevent="submit">
            <TextField
                v-model="form.name"
                name="name"
                label="Name"
                placeholder="e.g. Landing Page"
                required
                :maxlength="255"
                :error="form.errors.name"
            />

            <TextField
                v-model="slugModel"
                name="slug"
                label="Slug"
                placeholder="e.g. landing_page"
                required
                :maxlength="100"
                :error="form.errors.slug"
                hint="Machine key used by the landing page — letters, numbers, dashes and underscores only."
            />

            <TextareaField
                v-model="form.description"
                name="description"
                label="Description"
                placeholder="Short summary of this service…"
                :rows="4"
                :maxlength="500"
                :error="form.errors.description"
                hint="Optional — up to 500 characters."
            />

            <TextField
                v-model="form.sort_order"
                name="sort_order"
                label="Sort order"
                type="number"
                inputmode="numeric"
                placeholder="0"
                :error="form.errors.sort_order"
                hint="Lower numbers appear first in the catalog."
            />

            <div class="svc-form__toggle">
                <ToggleSwitch v-model="form.is_active" input-id="is_active" aria-label="Visible in catalog" />
                <label class="svc-form__toggle-label" for="is_active">
                    Visible in the public service catalog.
                </label>
            </div>

            <!-- Hidden submit lets Enter submit the form from any field. -->
            <button type="submit" class="svc-form__enter" tabindex="-1" aria-hidden="true" />
        </form>
    </AppModal>
</template>

<style scoped>
.svc-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.svc-form__toggle {
    display: flex;
    align-items: center;
    gap: var(--space-3);
}

.svc-form__toggle-label {
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

.svc-form__enter {
    display: none;
}
</style>
