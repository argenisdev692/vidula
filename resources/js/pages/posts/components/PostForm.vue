<script setup lang="ts">
/**
 * Shared create / edit post form — the single source of truth behind the
 * dedicated Create.vue and Edit.vue pages (no modal, since the content field
 * is long-form). Submits multipart (cover image upload) via Inertia
 * `useForm`, spoofing `_method: put` on edit exactly like BlogCategoryFormDialog
 * (Inertia cannot send multipart over a native PUT):
 *
 *   · create → POST /posts
 *   · edit   → POST /posts/{uuid}  with `_method: 'put'`
 *
 * The AI assist panel (right column) is purely additive: applying a generated
 * draft fills the editable fields below, which the user can still hand-edit
 * before saving — nothing is persisted until the normal submit.
 */
import { computed, reactive, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import TextField from '@/common/form/TextField.vue';
import TextareaField from '@/common/form/TextareaField.vue';
import SelectField from '@/common/form/SelectField.vue';
import DateField from '@/common/form/DateField.vue';
import TimeField from '@/common/form/TimeField.vue';
import FileField from '@/common/form/FileField.vue';
import SubmitButton from '@/common/form/SubmitButton.vue';
import SecondaryButton from '@/volt/SecondaryButton.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import AiAssistPanel from './AiAssistPanel.vue';
import { postFormSchema, type PostFormValues } from '@/modules/post/schemas/postFormSchema';
import type { CategoryOption, GeneratedPostContent, PostDetail } from '@/modules/post/types';
import { toLocalIsoDate } from '@/lib/date';
import { useAuthorization } from '@/modules/auth/composables/useAuthorization';

const props = withDefaults(
    defineProps<{
        mode: 'create' | 'edit';
        post?: PostDetail | null;
        categories: CategoryOption[];
    }>(),
    { post: null },
);

const isEdit = computed<boolean>(() => props.mode === 'edit');
const isSuspended = computed<boolean>(() => props.post?.deleted_at != null);

const { hasPermission } = useAuthorization();
const canSubmit = computed<boolean>(() => {
    if (isSuspended.value) {
        return false;
    }
    return isEdit.value ? hasPermission('UPDATE_POSTS') : hasPermission('CREATE_POSTS');
});

function splitScheduledAt(value: string | null): { date: string | null; time: string | null } {
    if (!value) {
        return { date: null, time: null };
    }
    const [date, time] = value.split('T');
    return { date: date ?? null, time: time ? time.slice(0, 5) : null };
}

const initialSchedule = splitScheduledAt(props.post?.scheduled_at ?? null);

interface AiScoresPayload {
    seo_analysis: { primary_keyword: string; lsi_keywords: string[] };
    optimization_suggestions: string[];
}

interface PostFormFields extends PostFormValues {
    category_uuid: string | null;
    cover_image: File | null;
    cover_image_path: string | null;
    scheduled_date: string | null;
    scheduled_time: string | null;
    is_ai_generated: boolean;
    ai_provider: string | null;
    seo_score: number | null;
    eeat_score: number | null;
    human_writing_index: number | null;
    ai_detection_risk: number | null;
    ai_scores: AiScoresPayload | null;
}

const form = useForm<PostFormFields>({
    title: props.post?.post_title ?? '',
    content: props.post?.post_content ?? '',
    excerpt: props.post?.post_excerpt ?? '',
    meta_title: props.post?.meta_title ?? '',
    meta_description: props.post?.meta_description ?? '',
    meta_keywords: props.post?.meta_keywords ?? '',
    category_uuid: props.post?.category?.uuid ?? null,
    status: props.post?.post_status ?? 'draft',
    scheduled_at: props.post?.scheduled_at ?? null,
    scheduled_date: initialSchedule.date,
    scheduled_time: initialSchedule.time,
    cover_image: null,
    cover_image_path: null,
    is_ai_generated: props.post?.is_ai_generated ?? false,
    ai_provider: props.post?.ai_provider ?? null,
    seo_score: props.post?.seo_score ?? null,
    eeat_score: props.post?.eeat_score ?? null,
    human_writing_index: props.post?.human_writing_index ?? null,
    ai_detection_risk: props.post?.ai_detection_risk ?? null,
    ai_scores: null,
});

const currentCoverUrl = computed<string | null>(() => props.post?.cover_image_url ?? null);

const statusOptions = [
    { label: 'Draft', value: 'draft' },
    { label: 'Published', value: 'published' },
    { label: 'Scheduled', value: 'scheduled' },
];

const showScheduling = computed<boolean>(() => form.status === 'scheduled');

/** Scheduling never allows a past date — today onward only. */
const minScheduledDate = toLocalIsoDate(new Date());

watch([() => form.scheduled_date, () => form.scheduled_time], ([date, time]) => {
    form.scheduled_at = date ? `${date}T${time ?? '09:00'}:00` : null;
});

const metaOpen = ref<boolean>(false);

/* ── AI draft applied from the assist panel ─────────────────────────────── */
function applyAiDraft(draft: GeneratedPostContent): void {
    form.title = draft.title;
    form.content = draft.content;
    form.excerpt = draft.excerpt;
    form.meta_title = draft.meta_title;
    form.meta_description = draft.meta_description;
    form.meta_keywords = draft.meta_keywords;
    if (draft.cover_image_path) {
        form.cover_image = null;
        form.cover_image_path = draft.cover_image_path;
    }
    form.is_ai_generated = true;
    form.ai_provider = draft.provider;
    form.seo_score = draft.seo_score;
    form.eeat_score = draft.eeat_score;
    form.human_writing_index = draft.human_writing_index;
    form.ai_detection_risk = draft.ai_detection_risk;
    form.ai_scores = {
        seo_analysis: draft.seo_analysis,
        optimization_suggestions: draft.optimization_suggestions,
        virality_score: draft.virality_score,
        roi_score: draft.roi_score,
        all_scores_pass: draft.all_scores_pass,
        iterations_required: draft.iterations_required,
        quality_warning: draft.quality_warning,
        image_prompts: draft.image_prompts,
    };
    metaOpen.value = true;
}

/** A manual cover-image upload always wins over an AI-generated path. */
watch(
    () => form.cover_image,
    (file) => {
        if (file) {
            form.cover_image_path = null;
        }
    },
);

const submitLabel = computed<string>(() => (isEdit.value ? 'Save changes' : 'Create post'));

function submit(): void {
    if (!canSubmit.value) {
        return;
    }

    const parsed = postFormSchema.safeParse({
        title: form.title,
        content: form.content,
        excerpt: form.excerpt,
        meta_title: form.meta_title,
        meta_description: form.meta_description,
        meta_keywords: form.meta_keywords,
        status: form.status,
        scheduled_at: form.scheduled_at,
    });
    if (!parsed.success) {
        form.clearErrors();
        for (const issue of parsed.error.issues) {
            const key = issue.path[0];
            if (typeof key === 'string') {
                form.setError(key as keyof PostFormFields, issue.message);
            }
        }
        return;
    }

    const url = isEdit.value ? `/posts/${props.post!.uuid}` : '/posts';

    form
        .transform((data) => {
            const payload: Record<string, unknown> = {
                title: data.title,
                content: data.content,
                excerpt: data.excerpt || null,
                meta_title: data.meta_title || null,
                meta_description: data.meta_description || null,
                meta_keywords: data.meta_keywords || null,
                category_uuid: data.category_uuid,
                status: data.status,
                scheduled_at: data.status === 'scheduled' ? data.scheduled_at : null,
                is_ai_generated: data.is_ai_generated,
                ai_provider: data.ai_provider,
                seo_score: data.seo_score,
                eeat_score: data.eeat_score,
                human_writing_index: data.human_writing_index,
                ai_detection_risk: data.ai_detection_risk,
                ai_scores: data.ai_scores,
            };
            if (data.cover_image) {
                payload.cover_image = data.cover_image;
            }
            if (data.cover_image_path) {
                payload.cover_image_path = data.cover_image_path;
            }
            if (isEdit.value) {
                payload._method = 'put';
            }
            return payload;
        })
        .post(url, { forceFormData: true, preserveScroll: true });
}
</script>

<template>
    <form class="post-form" @submit.prevent="submit">
        <div class="post-form__grid">
            <div class="post-form__main">
                <section class="post-form__section">
                    <TextField
                        v-model="form.title"
                        name="title"
                        label="Title"
                        placeholder="e.g. How We Cut Onboarding Time in Half"
                        required
                        :maxlength="255"
                        :error="form.errors.title"
                    />

                    <TextareaField
                        v-model="form.excerpt"
                        name="excerpt"
                        label="Excerpt"
                        placeholder="A short teaser shown in the blog list…"
                        :rows="2"
                        :maxlength="500"
                        :error="form.errors.excerpt"
                        hint="Optional — up to 500 characters."
                    />

                    <TextareaField
                        v-model="form.content"
                        name="content"
                        label="Content"
                        placeholder="Write the full post, or generate a draft with AI →"
                        required
                        :rows="16"
                        :error="form.errors.content"
                    />

                    <FileField
                        v-model="form.cover_image"
                        label="Cover image"
                        accept="image/jpeg,image/png,image/webp"
                        :max-size-mb="4"
                        icon="pi pi-image"
                        :current-url="form.cover_image_path ? null : currentCoverUrl"
                        :error="form.errors.cover_image"
                        hint="JPG, PNG or WEBP · up to 4 MB. Leave empty to keep the AI-generated cover, if any."
                    />
                </section>

                <section class="post-form__section">
                    <div class="post-form__grid-2">
                        <SelectField
                            v-model="form.category_uuid"
                            name="category_uuid"
                            label="Category"
                            placeholder="No category"
                            :options="categories"
                            show-clear
                            filter
                            :error="form.errors.category_uuid"
                        />

                        <SelectField
                            v-model="form.status"
                            name="status"
                            label="Status"
                            :options="statusOptions"
                            :error="form.errors.status"
                        />
                    </div>

                    <div v-if="showScheduling" class="post-form__grid-2">
                        <DateField
                            v-model="form.scheduled_date"
                            name="scheduled_date"
                            label="Publish date"
                            placeholder="Select a date"
                            required
                            :min-date="minScheduledDate"
                            :error="form.errors.scheduled_at"
                        />
                        <TimeField
                            v-model="form.scheduled_time"
                            name="scheduled_time"
                            label="Publish time"
                            placeholder="Select a time"
                        />
                    </div>
                </section>

                <section class="post-form__section">
                    <button type="button" class="post-form__toggle" @click="metaOpen = !metaOpen">
                        <i class="pi" :class="metaOpen ? 'pi-chevron-down' : 'pi-chevron-right'" aria-hidden="true" />
                        SEO metadata
                    </button>

                    <div v-if="metaOpen" class="post-form__grid-2">
                        <TextField
                            v-model="form.meta_title"
                            name="meta_title"
                            label="Meta title"
                            placeholder="50–60 characters"
                            :maxlength="255"
                            :error="form.errors.meta_title"
                        />
                        <TextField
                            v-model="form.meta_keywords"
                            name="meta_keywords"
                            label="Meta keywords"
                            placeholder="comma, separated, keywords"
                            :maxlength="255"
                            :error="form.errors.meta_keywords"
                        />
                    </div>
                    <TextareaField
                        v-if="metaOpen"
                        v-model="form.meta_description"
                        name="meta_description"
                        label="Meta description"
                        placeholder="150–160 characters"
                        :rows="2"
                        :maxlength="500"
                        :error="form.errors.meta_description"
                    />
                </section>

                <div class="post-form__actions">
                    <SecondaryButton
                        type="button"
                        label="Cancel"
                        :disabled="form.processing"
                        @click="router.visit('/posts')"
                    />
                    <PermissionGuard :permission="isEdit ? 'UPDATE_POSTS' : 'CREATE_POSTS'">
                        <SubmitButton :label="submitLabel" icon="pi pi-check" :loading="form.processing" />
                    </PermissionGuard>
                </div>
            </div>

            <PermissionGuard v-if="!isSuspended" permission="CREATE_POSTS">
                <AiAssistPanel @apply="applyAiDraft" />
            </PermissionGuard>
        </div>
    </form>
</template>

<style scoped>
.post-form__grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 22rem;
    gap: var(--space-6);
    align-items: start;
}

.post-form__main {
    display: flex;
    flex-direction: column;
    gap: var(--space-8);
    min-width: 0;
}

.post-form__section {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}

.post-form__grid-2 {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-3);
}

.post-form__toggle {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    width: fit-content;
    padding: 0;
    background: none;
    border: none;
    cursor: pointer;
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
    color: var(--accent-primary);
}

.post-form__actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: var(--space-3);
    margin-top: var(--space-2);
}

@media (max-width: 960px) {
    .post-form__grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .post-form__grid-2 {
        grid-template-columns: 1fr;
    }
}
</style>
