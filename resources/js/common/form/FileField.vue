<script setup lang="ts">
/**
 * Labelled file-upload field for the reusable form kit. Composes the
 * domain-agnostic FileUpload (drag-and-drop, click-to-browse, client-side
 * accept + size validation, image thumbnail / file-chip preview) so uploads
 * look and behave like every other field in `common/form`. `v-model` binds the
 * selected `File | null`; the parent uploads it (e.g. via Inertia `useForm` /
 * `router.post` with `forceFormData`). Purely presentational — it never touches
 * the network itself, staying reusable across every module's forms.
 */
import FileUpload from '@/common/media/FileUpload.vue';

const model = defineModel<File | null>({ default: null });

withDefaults(
    defineProps<{
        label?: string;
        error?: string;
        hint?: string;
        required?: boolean;
        disabled?: boolean;
        /** Native accept string, e.g. `image/png` or `.png,.pdf`. */
        accept?: string;
        /** Max file size in megabytes. */
        maxSizeMb?: number;
        /** URL of an already-stored file to preview when nothing is selected. */
        currentUrl?: string | null;
        /** primeicons class shown in the empty dropzone. */
        icon?: string;
    }>(),
    { accept: '', maxSizeMb: 2, required: false, disabled: false, currentUrl: null, icon: 'pi pi-cloud-upload' },
);
</script>

<template>
    <FileUpload
        v-model="model"
        :label="label"
        :required="required"
        :error="error"
        :hint="hint"
        :disabled="disabled"
        :accept="accept"
        :max-size-mb="maxSizeMb"
        :current-url="currentUrl"
        :icon="icon"
    />
</template>
