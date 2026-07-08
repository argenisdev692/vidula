<script setup lang="ts">
/**
 * Create / edit modal for a blog category. There is no `GET /create` or
 * `GET /{uuid}/edit` route — the backend store/update return `back()` redirects
 * and accept an optional image upload — so the form lives in a Volt Dialog on
 * the Index page and submits multipart via Inertia `useForm`.
 *
 *   · create → POST   /blog-categories          (forceFormData)
 *   · edit   → POST   /blog-categories/{uuid}    with `_method: 'put'` spoof
 *              (Inertia cannot send multipart over a native PUT)
 *
 * Built from the reusable common/form kit (TextField, TextareaField, FileField).
 * Client-side Zod validation mirrors the backend but the server stays
 * authoritative; server errors surface through `form.errors`.
 */
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import TextField from '@/common/form/TextField.vue';
import TextareaField from '@/common/form/TextareaField.vue';
import FileField from '@/common/form/FileField.vue';
import Dialog from '@/volt/Dialog.vue';
import Button from '@/volt/Button.vue';
import { blogCategoryFormSchema, type BlogCategoryFormValues } from '@/modules/blog/schemas/blogCategoryFormSchema';
import type { BlogCategory } from '@/modules/blog/types';

const visible = defineModel<boolean>('visible', { default: false });

const props = withDefaults(
    defineProps<{
        mode?: 'create' | 'edit';
        category?: BlogCategory | null;
    }>(),
    { mode: 'create', category: null },
);

const emit = defineEmits<{ saved: [] }>();

interface BlogCategoryForm extends BlogCategoryFormValues {
    image: File | null;
}

const form = useForm<BlogCategoryForm>({
    name: '',
    description: '',
    image: null,
});

const isEdit = computed<boolean>(() => props.mode === 'edit');
const currentImageUrl = computed<string | null>(() => props.category?.image_url ?? null);
const dialogTitle = computed<string>(() => (isEdit.value ? 'Edit blog category' : 'New blog category'));

/** Re-seed the form each time the dialog opens (never carry stale state). */
watch(visible, (open) => {
    if (!open) {
        return;
    }
    form.clearErrors();
    form.name = props.category?.blog_category_name ?? '';
    form.description = props.category?.blog_category_description ?? '';
    form.image = null;
});

function close(): void {
    visible.value = false;
}

function submit(): void {
    const parsed = blogCategoryFormSchema.safeParse({ name: form.name, description: form.description });
    if (!parsed.success) {
        form.clearErrors();
        for (const issue of parsed.error.issues) {
            const key = issue.path[0];
            if (typeof key === 'string') {
                form.setError(key as keyof BlogCategoryFormValues, issue.message);
            }
        }
        return;
    }

    const url = isEdit.value ? `/blog-categories/${props.category!.uuid}` : '/blog-categories';

    form
        .transform((data) => {
            const payload: Record<string, unknown> = {
                name: data.name,
                description: data.description ?? '',
            };
            if (data.image) {
                payload.image = data.image;
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
    <Dialog
        v-model:visible="visible"
        :header="dialogTitle"
        modal
        :draggable="false"
        :dismissable-mask="!form.processing"
        :style="{ width: '34rem', maxWidth: '94vw' }"
    >
        <form class="cat-form" @submit.prevent="submit">
            <TextField
                v-model="form.name"
                name="blog_category_name"
                label="Name"
                placeholder="e.g. Product updates"
                required
                :maxlength="255"
                :error="form.errors.name"
            />

            <TextareaField
                v-model="form.description"
                name="blog_category_description"
                label="Description"
                placeholder="Short summary of what this category groups…"
                :rows="4"
                :maxlength="1000"
                :error="form.errors.description"
                hint="Optional — up to 1000 characters."
            />

            <FileField
                v-model="form.image"
                label="Cover image"
                accept="image/jpeg,image/png,image/webp"
                :max-size-mb="2"
                icon="pi pi-image"
                :current-url="currentImageUrl"
                :error="form.errors.image"
                hint="JPG, PNG or WEBP · up to 2 MB · max 4096×4096."
            />

            <!-- Hidden submit lets Enter submit the form from any field. -->
            <button type="submit" class="cat-form__enter" tabindex="-1" aria-hidden="true" />
        </form>

        <template #footer>
            <Button label="Cancel" text severity="secondary" :disabled="form.processing" @click="close" />
            <Button
                type="button"
                :label="isEdit ? 'Save changes' : 'Create category'"
                :icon="form.processing ? undefined : 'pi pi-check'"
                :loading="form.processing"
                @click="submit"
            />
        </template>
    </Dialog>
</template>

<style scoped>
.cat-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.cat-form__enter {
    display: none;
}
</style>
