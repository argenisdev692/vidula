<script setup lang="ts">
/**
 * Create / edit modal for a CV upload.
 *
 *   · create → POST /cvs                          (forceFormData)
 *   · edit   → POST /cvs/{uuid} + `_method: 'put'` (multipart spoof)
 */
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import TextField from '@/common/form/TextField.vue';
import SelectField from '@/common/form/SelectField.vue';
import FileField from '@/common/form/FileField.vue';
import ToggleSwitch from '@/volt/ToggleSwitch.vue';
import AppModal from '@/common/ui/AppModal.vue';
import { cvFormSchemaForMode, type CvFormValues } from '@/modules/cvs/schemas/cvFormSchema';
import type { Cv } from '@/modules/cvs/types';
import type { SelectOption } from '@/common/form/types';

const visible = defineModel<boolean>('visible', { default: false });

const props = withDefaults(
    defineProps<{
        mode?: 'create' | 'edit';
        cv?: Cv | null;
    }>(),
    { mode: 'create', cv: null },
);

const emit = defineEmits<{ saved: [] }>();

interface CvFormState {
    title: string;
    niche: CvFormValues['niche'];
    is_primary: boolean;
    file: File | null;
}

const form = useForm<CvFormState>({
    title: '',
    niche: 'fullstack',
    is_primary: false,
    file: null,
});

const isEdit = computed<boolean>(() => props.mode === 'edit');
const dialogTitle = computed<string>(() => (isEdit.value ? 'Edit CV' : 'Upload CV'));

const nicheModel = computed<string | null>({
    get: () => form.niche,
    set: (value) => {
        form.niche = (value as CvFormValues['niche']) || 'fullstack';
    },
});

const nicheOptions: SelectOption[] = [
    { label: 'Fullstack (web)', value: 'fullstack' },
    { label: 'Other', value: 'other' },
];

watch(visible, (open) => {
    if (!open) {
        return;
    }
    form.clearErrors();
    form.title = props.cv?.title ?? '';
    form.niche = props.cv?.niche ?? 'fullstack';
    form.is_primary = props.cv?.is_primary ?? false;
    form.file = null;
});

function close(): void {
    visible.value = false;
}

function submit(): void {
    const schema = cvFormSchemaForMode(isEdit.value ? 'edit' : 'create');
    const parsed = schema.safeParse({
        title: form.title,
        niche: form.niche,
        is_primary: form.is_primary,
        file: form.file,
    });

    if (!parsed.success) {
        form.clearErrors();
        for (const issue of parsed.error.issues) {
            const key = issue.path[0];
            if (typeof key === 'string') {
                form.setError(key as keyof CvFormState, issue.message);
            }
        }
        return;
    }

    const url = isEdit.value ? `/cvs/${props.cv!.uuid}` : '/cvs';

    form
        .transform((data) => {
            const payload: Record<string, unknown> = {
                title: data.title.trim(),
                niche: data.niche,
                is_primary: data.is_primary,
            };
            if (data.file) {
                payload.file = data.file;
            }
            if (isEdit.value) {
                payload._method = 'put';
            }
            return payload;
        })
        .post(url, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                emit('saved');
                close();
            },
        });
}
</script>

<template>
    <AppModal
        v-model:visible="visible"
        :title="dialogTitle"
        :subtitle="isEdit ? 'Update this CV’s metadata or replace the file.' : 'Upload a PDF or Markdown resume.'"
        icon="pi pi-file"
        :confirm-label="isEdit ? 'Save changes' : 'Upload CV'"
        confirm-icon="pi pi-check"
        :loading="form.processing"
        :dismissable="!form.processing"
        width="36rem"
        @confirm="submit"
        @cancel="close"
    >
        <form class="cv-form" @submit.prevent="submit">
            <TextField
                v-model="form.title"
                name="title"
                label="Title"
                placeholder="e.g. Fullstack CV 2026"
                required
                :maxlength="255"
                :error="form.errors.title"
            />

            <SelectField
                v-model="nicheModel"
                name="niche"
                label="Niche"
                required
                :options="nicheOptions"
                :error="form.errors.niche"
            />

            <div class="cv-form__toggle">
                <label class="cv-form__toggle-label" for="cv-is-primary">Primary CV</label>
                <ToggleSwitch
                    input-id="cv-is-primary"
                    v-model="form.is_primary"
                    :aria-label="'Mark as primary CV'"
                />
                <p class="cv-form__hint">Only one primary CV per user. Setting this clears the previous primary.</p>
            </div>

            <FileField
                v-model="form.file"
                label="CV file"
                :required="!isEdit"
                accept=".pdf,.md,.markdown,application/pdf,text/markdown,text/plain"
                :max-size-mb="5"
                icon="pi pi-file"
                :hint="isEdit ? `Current: ${cv?.original_filename ?? '—'}. Leave empty to keep it.` : 'PDF or Markdown, max 5 MB.'"
                :error="form.errors.file"
            />

            <button type="submit" class="cv-form__enter" tabindex="-1" aria-hidden="true" />
        </form>
    </AppModal>
</template>

<style scoped>
.cv-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.cv-form__toggle {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-3);
}

.cv-form__toggle-label {
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--text-secondary);
}

.cv-form__hint {
    flex-basis: 100%;
    margin: 0;
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.cv-form__enter {
    display: none;
}
</style>
