<script setup lang="ts">
/**
 * Create / edit modal for a billable product. No dedicated create/edit routes —
 * store/update return back(), so the form lives in an AppModal on Index.
 *
 *   · create → POST /products
 *   · edit   → PUT  /products/{uuid}
 *
 * Type drives which detail block is sent (classroom vs video_course).
 */
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import TextField from '@/common/form/TextField.vue';
import TextareaField from '@/common/form/TextareaField.vue';
import SelectField from '@/common/form/SelectField.vue';
import AppModal from '@/common/ui/AppModal.vue';
import { productFormSchema, type ProductFormValues } from '@/modules/products/schemas/productFormSchema';
import type { Product, ProductClientOption, ProductType } from '@/modules/products/types';
import type { SelectOption } from '@/common/form/types';

const visible = defineModel<boolean>('visible', { default: false });

const props = withDefaults(
    defineProps<{
        mode?: 'create' | 'edit';
        product?: Product | null;
        clients?: ProductClientOption[];
    }>(),
    { mode: 'create', product: null, clients: () => [] },
);

const emit = defineEmits<{ saved: [] }>();

const emptyForm = (): ProductFormValues => ({
    type: 'classroom',
    title: '',
    description: '',
    price: 0,
    currency: 'EUR',
    status: 'draft',
    level: 'beginner',
    language: 'es',
    client_uuid: '',
    modality: 'online',
    total_hours: '',
    total_sessions: '',
    notes: '',
    classroom_max_students: '',
    classroom_meet_url: '',
    classroom_objectives: '',
    classroom_requirements: '',
    video_platform: '',
    video_playlist_url: '',
    video_total_videos: '',
    video_total_duration_minutes: '',
    video_target_audience: '',
});

const form = useForm<ProductFormValues>(emptyForm());

const isEdit = computed<boolean>(() => props.mode === 'edit');
const dialogTitle = computed<string>(() => (isEdit.value ? 'Edit product' : 'New product'));
const isClassroom = computed<boolean>(() => form.type === 'classroom');
const isVideo = computed<boolean>(() => form.type === 'video_tutorial' || form.type === 'video_pill');

const typeOptions: SelectOption[] = [
    { label: 'Classroom', value: 'classroom' },
    { label: 'Video tutorial', value: 'video_tutorial' },
    { label: 'Video pill', value: 'video_pill' },
];

const statusOptions: SelectOption[] = [
    { label: 'Draft', value: 'draft' },
    { label: 'Published', value: 'published' },
    { label: 'Archived', value: 'archived' },
];

const modalityOptions: SelectOption[] = [
    { label: 'Online', value: 'online' },
    { label: 'Presential', value: 'presential' },
    { label: 'Hybrid', value: 'hybrid' },
];

const platformOptions: SelectOption[] = [
    { label: 'YouTube', value: 'youtube' },
    { label: 'Vimeo', value: 'vimeo' },
    { label: 'Local', value: 'local' },
    { label: 'Other', value: 'other' },
];

const clientOptions = computed<SelectOption[]>(() => [
    { label: '— No client —', value: '' },
    ...props.clients.map((client) => ({ label: client.client_name, value: client.uuid })),
]);

const typeModel = computed<string | null>({
    get: () => form.type,
    set: (value) => {
        form.type = (value as ProductType) || 'classroom';
    },
});

const statusModel = computed<string | null>({
    get: () => form.status,
    set: (value) => {
        form.status = (value as ProductFormValues['status']) || 'draft';
    },
});

const modalityModel = computed<string | null>({
    get: () => form.modality || null,
    set: (value) => {
        form.modality = (value as ProductFormValues['modality']) || '';
    },
});

const clientModel = computed<string | null>({
    get: () => form.client_uuid || null,
    set: (value) => {
        form.client_uuid = value ?? '';
    },
});

const platformModel = computed<string | null>({
    get: () => form.video_platform || null,
    set: (value) => {
        form.video_platform = (value as ProductFormValues['video_platform']) || '';
    },
});

watch(visible, (open) => {
    if (!open) {
        return;
    }
    form.clearErrors();
    const product = props.product;
    if (product && props.mode === 'edit') {
        form.type = product.type;
        form.title = product.title ?? '';
        form.description = product.description ?? '';
        form.price = Number(product.price) || 0;
        form.currency = product.currency ?? 'EUR';
        form.status = product.status;
        form.level = product.level ?? 'beginner';
        form.language = product.language ?? 'es';
        form.client_uuid = product.client?.uuid ?? '';
        form.modality = product.modality ?? '';
        form.total_hours = product.total_hours === null || product.total_hours === undefined ? '' : Number(product.total_hours);
        form.total_sessions = product.total_sessions ?? '';
        form.notes = product.notes ?? '';
        form.classroom_max_students = product.classroom?.max_students ?? '';
        form.classroom_meet_url = product.classroom?.meet_url ?? '';
        form.classroom_objectives = product.classroom?.objectives ?? '';
        form.classroom_requirements = product.classroom?.requirements ?? '';
        form.video_platform = (product.video_course?.platform as ProductFormValues['video_platform']) ?? '';
        form.video_playlist_url = product.video_course?.playlist_url ?? '';
        form.video_total_videos = product.video_course?.total_videos ?? '';
        form.video_total_duration_minutes = product.video_course?.total_duration_minutes ?? '';
        form.video_target_audience = product.video_course?.target_audience ?? '';
    } else {
        Object.assign(form, emptyForm());
    }
});

function close(): void {
    visible.value = false;
}

function emptyToNull(value: string): string | null {
    const trimmed = value.trim();
    return trimmed === '' ? null : trimmed;
}

function optionalNumber(value: number | ''): number | null {
    return value === '' ? null : value;
}

function submit(): void {
    const parsed = productFormSchema.safeParse({ ...form.data() });

    if (!parsed.success) {
        form.clearErrors();
        for (const issue of parsed.error.issues) {
            const key = issue.path[0];
            if (typeof key === 'string') {
                form.setError(key as keyof ProductFormValues, issue.message);
            }
        }
        return;
    }

    const data = parsed.data;

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            close();
        },
    };

    form.transform(() => {
        const payload: Record<string, unknown> = {
            type: data.type,
            title: data.title.trim(),
            description: emptyToNull(data.description),
            price: data.price,
            currency: data.currency.toUpperCase(),
            status: data.status,
            level: data.level.trim(),
            language: data.language.trim(),
            client_uuid: emptyToNull(data.client_uuid),
            modality: emptyToNull(data.modality),
            total_hours: optionalNumber(data.total_hours),
            total_sessions: optionalNumber(data.total_sessions),
            notes: emptyToNull(data.notes),
        };

        if (data.type === 'classroom') {
            payload.classroom = {
                max_students: optionalNumber(data.classroom_max_students),
                meet_url: emptyToNull(data.classroom_meet_url),
                objectives: emptyToNull(data.classroom_objectives),
                requirements: emptyToNull(data.classroom_requirements),
            };
        } else {
            payload.video_course = {
                platform: emptyToNull(data.video_platform),
                playlist_url: emptyToNull(data.video_playlist_url),
                total_videos: optionalNumber(data.video_total_videos) ?? 0,
                total_duration_minutes: optionalNumber(data.video_total_duration_minutes),
                target_audience: emptyToNull(data.video_target_audience),
            };
        }

        return payload;
    });

    if (isEdit.value) {
        form.put(`/products/${props.product!.uuid}`, options);
    } else {
        form.post('/products', options);
    }
}
</script>

<template>
    <AppModal
        v-model:visible="visible"
        :title="dialogTitle"
        :subtitle="isEdit ? 'Update catalog details.' : 'Add a classroom or video product.'"
        icon="pi pi-box"
        :confirm-label="isEdit ? 'Save changes' : 'Create product'"
        confirm-icon="pi pi-check"
        :loading="form.processing"
        :dismissable="!form.processing"
        width="44rem"
        @confirm="submit"
        @cancel="close"
    >
        <form class="product-form" @submit.prevent="submit">
            <div class="product-form__row">
                <SelectField
                    v-model="typeModel"
                    name="type"
                    label="Type"
                    required
                    :options="typeOptions"
                    :disabled="isEdit"
                    :error="form.errors.type"
                />
                <SelectField
                    v-model="statusModel"
                    name="status"
                    label="Lifecycle"
                    required
                    :options="statusOptions"
                    :error="form.errors.status"
                />
            </div>

            <TextField
                v-model="form.title"
                name="title"
                label="Title"
                placeholder="e.g. GitHub Copilot Classroom"
                required
                :maxlength="255"
                :error="form.errors.title"
            />

            <TextareaField
                v-model="form.description"
                name="description"
                label="Description"
                placeholder="What this product delivers…"
                :rows="3"
                :maxlength="20000"
                :error="form.errors.description"
            />

            <div class="product-form__row">
                <TextField
                    :model-value="String(form.price)"
                    name="price"
                    label="Price"
                    type="number"
                    required
                    :error="form.errors.price"
                    @update:model-value="(v: string) => (form.price = v === '' ? 0 : Number(v))"
                />
                <TextField
                    v-model="form.currency"
                    name="currency"
                    label="Currency"
                    required
                    :maxlength="3"
                    :error="form.errors.currency"
                />
            </div>

            <div class="product-form__row">
                <TextField
                    v-model="form.level"
                    name="level"
                    label="Level"
                    required
                    :maxlength="50"
                    :error="form.errors.level"
                />
                <TextField
                    v-model="form.language"
                    name="language"
                    label="Language"
                    required
                    :maxlength="10"
                    :error="form.errors.language"
                />
            </div>

            <div class="product-form__row">
                <SelectField
                    v-model="clientModel"
                    name="client_uuid"
                    label="Client (billing)"
                    :options="clientOptions"
                    placeholder="Optional"
                    :error="form.errors.client_uuid"
                />
                <SelectField
                    v-model="modalityModel"
                    name="modality"
                    label="Modality"
                    :options="modalityOptions"
                    placeholder="Optional"
                    :error="form.errors.modality"
                />
            </div>

            <div class="product-form__row">
                <TextField
                    :model-value="form.total_sessions === '' ? '' : String(form.total_sessions)"
                    name="total_sessions"
                    label="Sessions"
                    type="number"
                    :error="form.errors.total_sessions"
                    @update:model-value="(v: string) => (form.total_sessions = v === '' ? '' : Number(v))"
                />
                <TextField
                    :model-value="form.total_hours === '' ? '' : String(form.total_hours)"
                    name="total_hours"
                    label="Hours"
                    type="number"
                    :error="form.errors.total_hours"
                    @update:model-value="(v: string) => (form.total_hours = v === '' ? '' : Number(v))"
                />
            </div>

            <template v-if="isClassroom">
                <p class="section-label">Classroom detail</p>
                <div class="product-form__row">
                    <TextField
                        :model-value="form.classroom_max_students === '' ? '' : String(form.classroom_max_students)"
                        name="classroom_max_students"
                        label="Max students"
                        type="number"
                        :error="form.errors.classroom_max_students"
                        @update:model-value="(v: string) => (form.classroom_max_students = v === '' ? '' : Number(v))"
                    />
                    <TextField
                        v-model="form.classroom_meet_url"
                        name="classroom_meet_url"
                        label="Meet URL"
                        placeholder="https://"
                        :error="form.errors.classroom_meet_url"
                    />
                </div>
                <TextareaField
                    v-model="form.classroom_objectives"
                    name="classroom_objectives"
                    label="Objectives"
                    :rows="2"
                    :error="form.errors.classroom_objectives"
                />
                <TextareaField
                    v-model="form.classroom_requirements"
                    name="classroom_requirements"
                    label="Requirements"
                    :rows="2"
                    :error="form.errors.classroom_requirements"
                />
            </template>

            <template v-if="isVideo">
                <p class="section-label">Video detail</p>
                <div class="product-form__row">
                    <SelectField
                        v-model="platformModel"
                        name="video_platform"
                        label="Platform"
                        :options="platformOptions"
                        placeholder="Optional"
                        :error="form.errors.video_platform"
                    />
                    <TextField
                        v-model="form.video_playlist_url"
                        name="video_playlist_url"
                        label="Playlist URL"
                        placeholder="https://"
                        :error="form.errors.video_playlist_url"
                    />
                </div>
                <div class="product-form__row">
                    <TextField
                        :model-value="form.video_total_videos === '' ? '' : String(form.video_total_videos)"
                        name="video_total_videos"
                        label="Total videos"
                        type="number"
                        :error="form.errors.video_total_videos"
                        @update:model-value="(v: string) => (form.video_total_videos = v === '' ? '' : Number(v))"
                    />
                    <TextField
                        :model-value="
                            form.video_total_duration_minutes === '' ? '' : String(form.video_total_duration_minutes)
                        "
                        name="video_total_duration_minutes"
                        label="Duration (min)"
                        type="number"
                        :error="form.errors.video_total_duration_minutes"
                        @update:model-value="
                            (v: string) => (form.video_total_duration_minutes = v === '' ? '' : Number(v))
                        "
                    />
                </div>
                <TextareaField
                    v-model="form.video_target_audience"
                    name="video_target_audience"
                    label="Target audience"
                    :rows="2"
                    :error="form.errors.video_target_audience"
                />
            </template>

            <TextareaField
                v-model="form.notes"
                name="notes"
                label="Notes"
                placeholder="Internal notes…"
                :rows="2"
                :error="form.errors.notes"
            />

            <button type="submit" class="product-form__enter" tabindex="-1" aria-hidden="true" />
        </form>
    </AppModal>
</template>

<style scoped>
.product-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.product-form__row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-4);
}

.section-label {
    margin: 0;
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
}

@media (max-width: 640px) {
    .product-form__row {
        grid-template-columns: 1fr;
    }
}

.product-form__enter {
    display: none;
}
</style>
