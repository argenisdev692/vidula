<script setup lang="ts">
/**
 * Create / edit modal for a portfolio project. There is no `GET /create` or
 * `GET /{uuid}/edit` route — the backend store/update return `back()`
 * redirects. Cover/video are uploaded direct to Cloudflare R2 via
 * `POST /portfolios/uploads/presign` + PUT, then the form submits only R2 keys:
 *
 *   · create → POST /portfolios
 *   · edit   → POST /portfolios/{uuid} + `_method: 'put'` spoof
 *
 * Gallery images are a separate, per-uuid concern (`POST /{uuid}/gallery`) that
 * only makes sense once the project exists — they are managed on the Show page
 * via {@see PortfolioGallery}, not here.
 *
 * `live_url` / `published_at` are omitted from the payload when blank so the
 * backend's fused `PortfolioData` receives `null` (clears the column) instead
 * of failing its `url`/`date` rule against an empty string. `remove_cover` /
 * `remove_video` only apply on edit, and only when no replacement file was
 * picked in the same submission (an upload always wins server-side).
 *
 * Built from the reusable common/form kit (TextField, TextareaField, DateField,
 * FileField) plus the Volt ToggleSwitch. Client-side Zod validation mirrors the
 * backend but the server stays authoritative; server errors surface through
 * `form.errors`.
 */
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import TextField from '@/common/form/TextField.vue';
import TextareaField from '@/common/form/TextareaField.vue';
import DateField from '@/common/form/DateField.vue';
import FileField from '@/common/form/FileField.vue';
import ToggleSwitch from '@/volt/ToggleSwitch.vue';
import AppModal from '@/common/ui/AppModal.vue';
import { HttpError } from '@/lib/http';
import { uploadPortfolioMedia } from '@/modules/portfolio/api';
import { portfolioFormSchema, parseTechStack, type PortfolioFormValues } from '@/modules/portfolio/schemas/portfolioFormSchema';
import type { Portfolio } from '@/modules/portfolio/types';

const visible = defineModel<boolean>('visible', { default: false });

const props = withDefaults(
    defineProps<{
        mode?: 'create' | 'edit';
        portfolio?: Portfolio | null;
    }>(),
    { mode: 'create', portfolio: null },
);

const emit = defineEmits<{ saved: [] }>();

interface PortfolioForm extends Omit<PortfolioFormValues, 'tech_stack'> {
    tech_stack_text: string;
    cover: File | null;
    video: File | null;
    remove_cover: boolean;
    remove_video: boolean;
}

const form = useForm<PortfolioForm>({
    title: '',
    client_name: '',
    project_type: '',
    tech_stack_text: '',
    live_url: '',
    published_at: '',
    is_public: true,
    description: '',
    sort_order: '0',
    cover: null,
    video: null,
    remove_cover: false,
    remove_video: false,
});

const uploading = ref(false);
const isBusy = computed<boolean>(() => form.processing || uploading.value);

const isEdit = computed<boolean>(() => props.mode === 'edit');
const dialogTitle = computed<string>(() => (isEdit.value ? 'Edit portfolio project' : 'New portfolio project'));

const currentCoverUrl = computed<string | null>(() => props.portfolio?.cover_url ?? null);
const currentVideoUrl = computed<string | null>(() => props.portfolio?.video_url ?? null);

/** Object URL for a freshly-picked video so the admin can preview it before saving. */
let videoObjectUrl: string | null = null;

function releaseVideoObjectUrl(): void {
    if (videoObjectUrl) {
        URL.revokeObjectURL(videoObjectUrl);
        videoObjectUrl = null;
    }
}

watch(
    () => form.video,
    (file) => {
        releaseVideoObjectUrl();
        if (file) {
            videoObjectUrl = URL.createObjectURL(file);
        }
    },
);

const videoPreviewSrc = computed<string | null>(() => {
    if (videoObjectUrl) {
        return videoObjectUrl;
    }
    return !form.remove_video ? currentVideoUrl.value : null;
});

onBeforeUnmount(releaseVideoObjectUrl);

/** Re-seed the form each time the dialog opens (never carry stale state). */
watch(visible, (open) => {
    if (!open) {
        return;
    }
    form.clearErrors();
    form.title = props.portfolio?.title ?? '';
    form.client_name = props.portfolio?.client_name ?? '';
    form.project_type = props.portfolio?.project_type ?? '';
    form.tech_stack_text = (props.portfolio?.tech_stack ?? []).join(', ');
    form.live_url = props.portfolio?.live_url ?? '';
    form.published_at = props.portfolio?.published_at?.slice(0, 10) ?? '';
    form.is_public = props.portfolio?.is_public ?? true;
    form.description = props.portfolio?.description ?? '';
    form.sort_order = String(props.portfolio?.sort_order ?? 0);
    form.cover = null;
    form.video = null;
    form.remove_cover = false;
    form.remove_video = false;
    uploading.value = false;
});

function close(): void {
    visible.value = false;
}

function applyHttpErrors(error: unknown): void {
    if (!(error instanceof HttpError) || typeof error.body !== 'object' || error.body === null) {
        form.setError('cover', error instanceof Error ? error.message : 'Upload failed.');
        return;
    }

    const body = error.body as { message?: string; errors?: Record<string, string[]> };
    const errors = body.errors ?? {};
    form.clearErrors();

    if (errors.content_type?.[0] || errors.size_bytes?.[0] || errors.kind?.[0]) {
        form.setError('cover', errors.content_type?.[0] ?? errors.size_bytes?.[0] ?? errors.kind?.[0] ?? 'Upload rejected.');
        return;
    }

    if (errors.cover_path?.[0]) {
        form.setError('cover', errors.cover_path[0]);
    }
    if (errors.video_path?.[0]) {
        form.setError('video', errors.video_path[0]);
    }
    if (!errors.cover_path && !errors.video_path) {
        form.setError('cover', body.message ?? 'Upload failed.');
    }
}

async function submit(): Promise<void> {
    if (isBusy.value) {
        return;
    }

    const techStack = parseTechStack(form.tech_stack_text);
    const parsed = portfolioFormSchema.safeParse({
        title: form.title,
        client_name: form.client_name,
        project_type: form.project_type,
        tech_stack: techStack,
        live_url: form.live_url,
        published_at: form.published_at,
        is_public: form.is_public,
        description: form.description,
        sort_order: form.sort_order,
    });

    if (!parsed.success) {
        form.clearErrors();
        for (const issue of parsed.error.issues) {
            const key = issue.path[0];
            if (key === 'tech_stack') {
                form.setError('tech_stack_text', issue.message);
            } else if (typeof key === 'string') {
                form.setError(key as keyof PortfolioForm, issue.message);
            }
        }
        return;
    }

    const url = isEdit.value ? `/portfolios/${props.portfolio!.uuid}` : '/portfolios';

    let coverPath: string | null = null;
    let videoPath: string | null = null;

    try {
        uploading.value = true;
        form.clearErrors();

        if (form.cover) {
            coverPath = await uploadPortfolioMedia('cover', form.cover);
        }
        if (form.video) {
            videoPath = await uploadPortfolioMedia('video', form.video);
        }
    } catch (error) {
        applyHttpErrors(error);
        uploading.value = false;
        return;
    } finally {
        uploading.value = false;
    }

    form
        .transform((data) => {
            const payload: Record<string, unknown> = {
                title: data.title,
                client_name: data.client_name,
                project_type: data.project_type,
                tech_stack: parseTechStack(data.tech_stack_text),
                is_public: data.is_public,
                description: data.description ?? '',
                sort_order: data.sort_order === '' ? 0 : Number(data.sort_order),
            };
            if (data.live_url) {
                payload.live_url = data.live_url;
            }
            if (data.published_at) {
                payload.published_at = data.published_at;
            }
            if (coverPath) {
                payload.cover_path = coverPath;
            }
            if (videoPath) {
                payload.video_path = videoPath;
            }
            if (isEdit.value) {
                payload._method = 'put';
                if (data.remove_cover && !coverPath) {
                    payload.remove_cover = true;
                }
                if (data.remove_video && !videoPath) {
                    payload.remove_video = true;
                }
            }
            return payload;
        })
        .post(url, {
            preserveScroll: true,
            onSuccess: () => {
                emit('saved');
                close();
            },
            onError: (errors) => {
                if (errors.cover_path) {
                    form.setError('cover', errors.cover_path);
                }
                if (errors.video_path) {
                    form.setError('video', errors.video_path);
                }
            },
        });
}
</script>

<template>
    <AppModal
        v-model:visible="visible"
        :title="dialogTitle"
        :subtitle="isEdit ? 'Update this project’s details and media.' : 'Add a project to the portfolio gallery.'"
        icon="pi pi-briefcase"
        :confirm-label="isEdit ? 'Save changes' : 'Create project'"
        confirm-icon="pi pi-check"
        :loading="isBusy"
        :dismissable="!isBusy"
        width="38rem"
        @confirm="submit"
        @cancel="close"
    >
        <form class="pf-form" @submit.prevent="submit">
            <div class="pf-form__row">
                <TextField
                    v-model="form.title"
                    name="title"
                    label="Title"
                    placeholder="e.g. Brand Refresh — Acme Co."
                    required
                    :maxlength="255"
                    :error="form.errors.title"
                />

                <TextField
                    v-model="form.client_name"
                    name="client_name"
                    label="Client name"
                    placeholder="e.g. Acme Co."
                    required
                    :maxlength="255"
                    :error="form.errors.client_name"
                />
            </div>

            <div class="pf-form__row">
                <TextField
                    v-model="form.project_type"
                    name="project_type"
                    label="Project type"
                    placeholder="e.g. web, mobile, branding…"
                    required
                    :maxlength="50"
                    :error="form.errors.project_type"
                    hint="Free text — used as a filter tag on the gallery."
                />

                <TextField
                    v-model="form.sort_order"
                    name="sort_order"
                    label="Sort order"
                    type="number"
                    inputmode="numeric"
                    placeholder="0"
                    :error="form.errors.sort_order"
                    hint="Lower numbers appear first."
                />
            </div>

            <TextField
                v-model="form.tech_stack_text"
                name="tech_stack_text"
                label="Tech stack"
                placeholder="e.g. React, Next.js, PostgreSQL, Stripe"
                :error="form.errors.tech_stack_text"
                hint="Comma-separated — up to 20 items, 50 characters each. Shown as badges on Astro."
            />

            <div class="pf-form__row">
                <TextField
                    v-model="form.live_url"
                    name="live_url"
                    label="Live URL"
                    type="url"
                    placeholder="https://example.com"
                    :error="form.errors.live_url"
                    hint="Optional — link to the live project."
                />

                <DateField
                    v-model="form.published_at"
                    name="published_at"
                    label="Published date"
                    placeholder="Select a date"
                    :error="form.errors.published_at"
                />
            </div>

            <TextareaField
                v-model="form.description"
                name="description"
                label="Description"
                placeholder="Short summary of the project…"
                :rows="4"
                :maxlength="5000"
                :error="form.errors.description"
                hint="Optional — up to 5000 characters."
            />

            <div class="pf-form__toggle">
                <ToggleSwitch v-model="form.is_public" input-id="is_public" aria-label="Visible in the public gallery" />
                <label class="pf-form__toggle-label" for="is_public">Visible in the public portfolio gallery.</label>
            </div>

            <div class="pf-form__media">
                <div class="pf-form__media-field">
                    <FileField
                        v-model="form.cover"
                        label="Cover image"
                        accept="image/jpeg,image/png,image/webp"
                        :max-size-mb="4"
                        icon="pi pi-image"
                        :current-url="!form.remove_cover ? currentCoverUrl : null"
                        :error="form.errors.cover"
                        hint="JPG, PNG or WEBP · up to 4 MB · max 4096×4096."
                    />
                    <label v-if="isEdit && currentCoverUrl && !form.cover" class="pf-form__remove">
                        <input v-model="form.remove_cover" type="checkbox" />
                        Remove current cover on save
                    </label>
                </div>

                <div class="pf-form__media-field">
                    <FileField
                        v-model="form.video"
                        label="Showcase video"
                        accept="video/mp4,video/webm"
                        :max-size-mb="50"
                        icon="pi pi-video"
                        :error="form.errors.video"
                        hint="MP4 or WEBM · up to 50 MB."
                    />
                    <label v-if="isEdit && currentVideoUrl && !form.video" class="pf-form__remove">
                        <input v-model="form.remove_video" type="checkbox" />
                        Remove current video on save
                    </label>
                    <video
                        v-if="videoPreviewSrc"
                        :key="videoPreviewSrc"
                        class="pf-form__video-preview"
                        :src="videoPreviewSrc"
                        controls
                        muted
                        preload="metadata"
                    />
                </div>
            </div>

            <!-- Hidden submit lets Enter submit the form from any field. -->
            <button type="submit" class="pf-form__enter" tabindex="-1" aria-hidden="true" />
        </form>
    </AppModal>
</template>

<style scoped>
.pf-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.pf-form__row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-4);
}

@media (max-width: 560px) {
    .pf-form__row {
        grid-template-columns: 1fr;
    }
}

.pf-form__toggle {
    display: flex;
    align-items: center;
    gap: var(--space-3);
}

.pf-form__toggle-label {
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

.pf-form__media {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-4);
}

@media (max-width: 560px) {
    .pf-form__media {
        grid-template-columns: 1fr;
    }
}

.pf-form__media-field {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.pf-form__remove {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    font-size: var(--text-xs);
    color: var(--text-secondary);
    cursor: pointer;
}

.pf-form__video-preview {
    width: 100%;
    max-height: 10rem;
    border-radius: var(--radius-md);
    border: 1px solid var(--border-subtle);
    background: var(--bg-elevated);
}

.pf-form__enter {
    display: none;
}
</style>
