<script setup lang="ts">
/**
 * Start-studio modal — POST /resume-studio/runs with mode-dependent fields.
 */
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import TextField from '@/common/form/TextField.vue';
import SelectField from '@/common/form/SelectField.vue';
import TextareaField from '@/common/form/TextareaField.vue';
import AppModal from '@/common/ui/AppModal.vue';
import ToggleSwitch from '@/volt/ToggleSwitch.vue';
import SecondaryButton from '@/volt/SecondaryButton.vue';
import Message from '@/volt/Message.vue';
import { HttpError } from '@/lib/http';
import { useGithubReposMutation } from '@/modules/resume-studio/composables/useGithubReposMutation';
import { startStudioRunSchema } from '@/modules/resume-studio/schemas/startStudioRunSchema';
import type {
    AiProvider,
    CvOption,
    GithubRepo,
    LocationScope,
    ResumeLanguage,
    SearchLanguage,
    StudioMode,
} from '@/modules/resume-studio/types';
import type { SelectOption } from '@/common/form/types';
import { AI_PROVIDER_OPTIONS } from '@/modules/resume-studio/helpers/options';
import {
    LOCATION_SCOPE_OPTIONS,
    RESUME_LANGUAGE_OPTIONS,
    SEARCH_LANGUAGE_OPTIONS,
} from '@/modules/resume-studio/helpers/locationScopes';

const visible = defineModel<boolean>('visible', { default: false });

const props = defineProps<{
    mode: StudioMode;
    cvs: CvOption[];
}>();

const emit = defineEmits<{ started: [] }>();

const toast = useToast();
const { mutateAsync: fetchGithubRepos, asyncStatus: githubAsyncStatus } = useGithubReposMutation();

interface StartFormState {
    cv_uuid: string;
    mode: StudioMode;
    provider: AiProvider;
    keywords: string;
    targeting_prompt: string;
    github_username: string;
    github_selected_repos: string[];
    github_extra_prompt: string;
    deep_extract: boolean;
    target_job_title: string;
    location_scope: LocationScope;
    search_language: SearchLanguage;
    resume_language: ResumeLanguage;
}

const form = useForm<StartFormState>({
    cv_uuid: '',
    mode: 'career',
    provider: 'openai',
    keywords: '',
    targeting_prompt: '',
    github_username: '',
    github_selected_repos: [],
    github_extra_prompt: '',
    deep_extract: false,
    target_job_title: '',
    location_scope: 'remote',
    search_language: 'both',
    resume_language: 'en',
});

const githubRepos = ref<GithubRepo[]>([]);
const reposError = ref<string | null>(null);

const reposLoading = computed<boolean>(() => githubAsyncStatus.value === 'loading');

const isCareer = computed<boolean>(() => form.mode === 'career');
const isOther = computed<boolean>(() => form.mode === 'other');

const nicheFilter = computed<'fullstack' | 'other'>(() => (isCareer.value ? 'fullstack' : 'other'));

const filteredCvs = computed<CvOption[]>(() =>
    props.cvs.filter((cv) => cv.niche === nicheFilter.value),
);

const cvOptions = computed<SelectOption[]>(() =>
    filteredCvs.value.map((cv) => ({
        label: cv.is_primary ? `${cv.title} (primary)` : cv.title,
        value: cv.uuid,
    })),
);

const cvModel = computed<string | null>({
    get: () => form.cv_uuid || null,
    set: (value) => {
        form.cv_uuid = value ?? '';
    },
});

const dialogTitle = computed<string>(() =>
    isCareer.value ? 'Start career studio run' : 'Start other-niche studio run',
);

function errorMessage(error: unknown): string {
    if (error instanceof HttpError) {
        const body = error.body as { message?: string; errors?: Record<string, string[]> } | undefined;
        const firstFieldError = body?.errors ? Object.values(body.errors)[0]?.[0] : undefined;
        return firstFieldError ?? body?.message ?? `Request failed (HTTP ${error.status}).`;
    }
    return 'Something went wrong. Please try again.';
}

function resetGithubState(): void {
    githubRepos.value = [];
    reposError.value = null;
}

function seedForm(): void {
    form.clearErrors();
    form.mode = props.mode;
    form.provider = 'openai';
    form.keywords = '';
    form.targeting_prompt = '';
    form.github_username = '';
    form.github_selected_repos = [];
    form.github_extra_prompt = '';
    form.deep_extract = false;
    form.target_job_title = '';
    form.location_scope = 'remote';
    form.search_language = 'both';
    form.resume_language = 'en';

    const primary = filteredCvs.value.find((cv) => cv.is_primary);
    form.cv_uuid = primary?.uuid ?? filteredCvs.value[0]?.uuid ?? '';
    resetGithubState();
}

watch(visible, (open) => {
    if (open) {
        seedForm();
    }
});

watch(
    () => props.mode,
    (mode) => {
        if (visible.value) {
            form.mode = mode;
            const stillValid = filteredCvs.value.some((cv) => cv.uuid === form.cv_uuid);
            if (!stillValid) {
                form.cv_uuid = filteredCvs.value[0]?.uuid ?? '';
            }
        }
    },
);

watch(nicheFilter, () => {
    if (!visible.value) {
        return;
    }
    const stillValid = filteredCvs.value.some((cv) => cv.uuid === form.cv_uuid);
    if (!stillValid) {
        form.cv_uuid = filteredCvs.value[0]?.uuid ?? '';
    }
});

function close(): void {
    visible.value = false;
}

function toggleRepo(name: string, checked: boolean): void {
    const set = new Set(form.github_selected_repos);
    if (checked) {
        set.add(name);
    } else {
        set.delete(name);
    }
    form.github_selected_repos = [...set];
}

function isRepoSelected(name: string): boolean {
    return form.github_selected_repos.includes(name);
}

async function loadRepos(): Promise<void> {
    const username = form.github_username.trim();
    if (!username) {
        reposError.value = 'Enter a GitHub username first.';
        return;
    }

    reposError.value = null;
    try {
        const response = await fetchGithubRepos({ github_username: username });
        githubRepos.value = response.data;
        if (response.data.length === 0) {
            reposError.value = 'No public repositories found for that username.';
        }
    } catch (error) {
        reposError.value = errorMessage(error);
        githubRepos.value = [];
    }
}

function submit(): void {
    const payload = {
        cv_uuid: form.cv_uuid,
        mode: form.mode,
        provider: form.provider,
        keywords: form.keywords.trim() || null,
        targeting_prompt: form.targeting_prompt.trim() || null,
        github_username: isCareer.value ? form.github_username.trim() || null : null,
        github_selected_repos:
            isCareer.value && form.github_selected_repos.length > 0 ? form.github_selected_repos : null,
        github_extra_prompt: isCareer.value ? form.github_extra_prompt.trim() || null : null,
        deep_extract: form.deep_extract,
        target_job_title: form.target_job_title.trim() || null,
        location_scope: form.location_scope || 'remote',
        search_language: form.search_language || 'both',
        resume_language: form.resume_language || 'en',
    };

    const parsed = startStudioRunSchema.safeParse(payload);
    if (!parsed.success) {
        form.clearErrors();
        for (const issue of parsed.error.issues) {
            const key = issue.path[0];
            if (typeof key === 'string') {
                form.setError(key as keyof StartFormState, issue.message);
            }
        }
        return;
    }

    form
        .transform(() => payload)
        .post('/resume-studio/runs', {
            preserveScroll: true,
            onSuccess: () => {
                emit('started');
                toast.add({ severity: 'success', summary: 'Studio run queued', life: 4000 });
                close();
            },
        });
}
</script>

<template>
    <AppModal
        v-model:visible="visible"
        :title="dialogTitle"
        subtitle="Queue ATS refine, job search, scoring, and outreach drafts."
        icon="pi pi-sparkles"
        confirm-label="Start studio"
        confirm-icon="pi pi-play"
        :loading="form.processing"
        :dismissable="!form.processing"
        width="40rem"
        @confirm="submit"
        @cancel="close"
    >
        <form class="studio-form" @submit.prevent="submit">
            <SelectField
                v-model="cvModel"
                name="cv_uuid"
                label="CV"
                required
                :options="cvOptions"
                placeholder="Select a CV"
                :error="form.errors.cv_uuid"
                :disabled="cvOptions.length === 0"
                :hint="
                    cvOptions.length === 0
                        ? `No ${nicheFilter} CVs uploaded yet — add one under CVs first.`
                        : undefined
                "
            />

            <SelectField
                v-model="form.provider"
                name="provider"
                label="AI provider"
                required
                :options="AI_PROVIDER_OPTIONS"
                :error="form.errors.provider"
            />

            <TextField
                v-model="form.keywords"
                name="keywords"
                label="Keywords"
                placeholder="e.g. laravel, vue, remote"
                :maxlength="500"
                :error="form.errors.keywords"
            />

            <TextField
                v-model="form.target_job_title"
                name="target_job_title"
                label="Target job title"
                placeholder="e.g. Senior Fullstack Developer"
                :maxlength="255"
                :error="form.errors.target_job_title"
            />

            <SelectField
                v-model="form.location_scope"
                name="location_scope"
                label="Location scope"
                required
                filter
                show-clear
                :options="LOCATION_SCOPE_OPTIONS"
                placeholder="Search region, country, or preset…"
                hint="LinkedIn-style geography filter — type to search (Schengen, LatAm, countries…)."
                :error="form.errors.location_scope"
            />

            <SelectField
                v-model="form.resume_language"
                name="resume_language"
                label="CV language"
                required
                :options="RESUME_LANGUAGE_OPTIONS"
                hint="Language of the refined CV and PDF export (Portuguese = Portugal / European)."
                :error="form.errors.resume_language"
            />

            <SelectField
                v-model="form.search_language"
                name="search_language"
                label="Job language"
                required
                :options="SEARCH_LANGUAGE_OPTIONS"
                hint="Prefer Spanish, English, or both in openings."
                :error="form.errors.search_language"
            />

            <TextareaField
                v-if="isOther"
                v-model="form.targeting_prompt"
                name="targeting_prompt"
                label="Targeting prompt"
                required
                placeholder="Describe the niche, role, and constraints for this run…"
                :rows="4"
                :maxlength="5000"
                :error="form.errors.targeting_prompt"
            />

            <template v-if="isCareer">
                <TextField
                    v-model="form.github_username"
                    name="github_username"
                    label="GitHub username"
                    placeholder="octocat"
                    :maxlength="255"
                    :error="form.errors.github_username"
                />

                <div class="studio-form__repos">
                    <SecondaryButton
                        type="button"
                        label="Load repos"
                        icon="pi pi-github"
                        :loading="reposLoading"
                        :disabled="form.processing"
                        @click="loadRepos"
                    />
                    <Message v-if="reposError" severity="warn" :closable="false">{{ reposError }}</Message>
                    <ul v-if="githubRepos.length > 0" class="repo-list">
                        <li v-for="repo in githubRepos" :key="repo.name" class="repo-item">
                            <label class="repo-label">
                                <input
                                    type="checkbox"
                                    class="repo-checkbox"
                                    :checked="isRepoSelected(repo.name)"
                                    :disabled="form.processing"
                                    @change="
                                        toggleRepo(
                                            repo.name,
                                            ($event.target as HTMLInputElement).checked,
                                        )
                                    "
                                />
                                <span class="repo-name">{{ repo.name }}</span>
                                <span v-if="repo.language" class="repo-meta">{{ repo.language }}</span>
                                <span class="repo-meta">{{ repo.stars }} ★</span>
                            </label>
                            <p v-if="repo.description" class="repo-desc">{{ repo.description }}</p>
                        </li>
                    </ul>
                </div>

                <TextareaField
                    v-model="form.github_extra_prompt"
                    name="github_extra_prompt"
                    label="GitHub extra prompt"
                    placeholder="Optional notes for portfolio enrichment…"
                    :rows="3"
                    :maxlength="5000"
                    :error="form.errors.github_extra_prompt"
                />
            </template>

            <div class="studio-form__toggle">
                <label class="studio-form__toggle-label" for="studio-deep-extract">Deep extract job pages</label>
                <ToggleSwitch
                    input-id="studio-deep-extract"
                    v-model="form.deep_extract"
                    :disabled="form.processing"
                    aria-label="Enable deep extract for job pages"
                />
                <p class="studio-form__hint">
                    Uses Firecrawl on high-scoring matches for richer snippets (slower).
                </p>
            </div>

            <button type="submit" class="studio-form__enter" tabindex="-1" aria-hidden="true" />
        </form>
    </AppModal>
</template>

<style scoped>
.studio-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.studio-form__repos {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
}

.repo-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    max-height: 14rem;
    overflow: auto;
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-md);
    padding: var(--space-3);
    background: var(--bg-card);
}

.repo-item {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
}

.repo-label {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-2);
    font-size: var(--text-sm);
    color: var(--text-primary);
    cursor: pointer;
}

.repo-checkbox {
    accent-color: var(--accent-primary);
}

.repo-name {
    font-weight: var(--font-medium);
}

.repo-meta {
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.repo-desc {
    margin: 0;
    font-size: var(--text-xs);
    color: var(--text-muted);
    padding-left: calc(1rem + var(--space-2));
}

.studio-form__toggle {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-3);
}

.studio-form__toggle-label {
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--text-secondary);
}

.studio-form__hint {
    flex-basis: 100%;
    margin: 0;
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.studio-form__enter {
    display: none;
}
</style>
