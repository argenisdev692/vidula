<script setup lang="ts">
/**
 * Video Export panel — merge / clean / AI modes, direct-to-R2 uploads, job poll.
 * Tokens only (FRONTEND §0–§2). No DataTable CRUD.
 */
import { Head } from '@inertiajs/vue3';
import { computed, onUnmounted, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import Button from '@/volt/Button.vue';
import Card from '@/volt/Card.vue';
import Select from '@/volt/Select.vue';
import Tag from '@/volt/Tag.vue';
import Message from '@/volt/Message.vue';
import {
    enqueueExport,
    fetchJobStatus,
    presignUpload,
    putToR2,
    uploadSources,
} from '@/modules/video-export/api';
import type {
    AiProvider,
    AudioEnhanceMode,
    ExportMode,
    JobStatus,
    JobStatusResponse,
    UploadItem,
} from '@/modules/video-export/types';
import { isTerminalStatus } from '@/modules/video-export/types';
import { HttpError } from '@/lib/http';

defineOptions({ layout: AppLayout });

const toast = useToast();

const mode = ref<ExportMode>('clean');
const aiProvider = ref<AiProvider>('gemini');
const silenceThreshold = ref<number>(1);
const audioEnhanceMode = ref<AudioEnhanceMode>('dsp');
const files = ref<UploadItem[]>([]);
const scriptFile = ref<File | null>(null);
const scriptUrl = ref<string | null>(null);
const scriptUploading = ref<boolean>(false);
const isDragOver = ref<boolean>(false);
const submitting = ref<boolean>(false);
const jobUuid = ref<string | null>(null);
const jobStatus = ref<JobStatus | null>(null);
const jobPayload = ref<JobStatusResponse | null>(null);
const pollTimer = ref<ReturnType<typeof setInterval> | null>(null);
const scriptInput = ref<HTMLInputElement | null>(null);

const silenceOptions = [
    { label: '1 second', value: 1 },
    { label: '2 seconds', value: 2 },
    { label: '3 seconds', value: 3 },
];

const enhanceOptions = [
    { label: 'Off', value: 'off' },
    { label: 'DSP (local FFmpeg)', value: 'dsp' },
    { label: 'AI denoise', value: 'ai' },
];

const providerOptions = [
    { label: 'Gemini', value: 'gemini' },
    { label: 'OpenAI', value: 'openai' },
    { label: 'Anthropic', value: 'anthropic' },
];

const modeCards: Array<{ value: ExportMode; title: string; blurb: string; icon: string }> = [
    {
        value: 'merge',
        title: 'Merge only',
        blurb: 'Concatenate clips into one HD 1080p export — no cleaning.',
        icon: 'pi pi-objects-column',
    },
    {
        value: 'clean',
        title: 'Cleaning only',
        blurb: 'Remove long silences locally with FFmpeg. Optional audio polish.',
        icon: 'pi pi-volume-off',
    },
    {
        value: 'ai',
        title: 'AI integrate',
        blurb: 'Silence + fillers, stutters, PAUSA cuts. Optional script review.',
        icon: 'pi pi-sparkles',
    },
];

const canSubmit = computed<boolean>(() => {
    if (submitting.value || files.value.length === 0) {
        return false;
    }
    if (files.value.some((f) => f.status === 'error')) {
        return false;
    }
    return true;
});

const showCleanOptions = computed<boolean>(() => mode.value === 'clean' || mode.value === 'ai');
const showScript = computed<boolean>(() => mode.value === 'ai');

function uid(): string {
    return crypto.randomUUID();
}

function onDragOver(event: DragEvent): void {
    event.preventDefault();
    isDragOver.value = true;
}

function onDragLeave(event: DragEvent): void {
    event.preventDefault();
    isDragOver.value = false;
}

function onDrop(event: DragEvent): void {
    event.preventDefault();
    isDragOver.value = false;
    const list = event.dataTransfer?.files;
    if (list) {
        addFiles(Array.from(list));
    }
}

function onFilesSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files) {
        addFiles(Array.from(input.files));
        input.value = '';
    }
}

function addFiles(selected: File[]): void {
    const videos = selected.filter((f) => f.type.startsWith('video/') || /\.(mp4|mov|mkv|webm)$/i.test(f.name));
    for (const file of videos) {
        files.value.push({
            id: uid(),
            file,
            status: 'pending',
            progress: 0,
        });
    }
}

function removeFile(id: string): void {
    files.value = files.value.filter((f) => f.id !== id);
}

async function onScriptSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    input.value = '';
    if (!file) {
        return;
    }
    scriptFile.value = file;
    scriptUploading.value = true;
    try {
        const slot = await presignUpload(file);
        await putToR2(slot.upload_url, file, slot.headers);
        scriptUrl.value = slot.public_url;
        toast.add({ severity: 'success', summary: 'Script uploaded', life: 3000 });
    } catch {
        scriptFile.value = null;
        scriptUrl.value = null;
        toast.add({ severity: 'error', summary: 'Script upload failed', life: 5000 });
    } finally {
        scriptUploading.value = false;
    }
}

function clearScript(): void {
    scriptFile.value = null;
    scriptUrl.value = null;
}

function stopPolling(): void {
    if (pollTimer.value !== null) {
        clearInterval(pollTimer.value);
        pollTimer.value = null;
    }
}

async function pollOnce(): Promise<void> {
    if (!jobUuid.value) {
        return;
    }
    try {
        const payload = await fetchJobStatus(jobUuid.value);
        jobPayload.value = payload;
        jobStatus.value = payload.status;
        if (isTerminalStatus(payload.status)) {
            stopPolling();
            if (payload.status === 'completed') {
                toast.add({ severity: 'success', summary: 'Export ready', life: 4000 });
            }
            if (payload.status === 'failed') {
                toast.add({
                    severity: 'error',
                    summary: payload.error ?? 'Export failed',
                    life: 6000,
                });
            }
        }
    } catch {
        // keep polling; transient errors
    }
}

function startPolling(uuid: string): void {
    stopPolling();
    jobUuid.value = uuid;
    jobStatus.value = 'queued';
    jobPayload.value = null;
    void pollOnce();
    pollTimer.value = setInterval(() => {
        void pollOnce();
    }, 2500);
}

async function onSubmit(): Promise<void> {
    if (!canSubmit.value) {
        return;
    }
    submitting.value = true;
    try {
        const pending = files.value.filter((f) => f.status !== 'done' || !f.publicUrl);
        const doneUrls = files.value
            .filter((f) => f.status === 'done' && f.publicUrl)
            .map((f) => f.publicUrl as string);

        const uploaded = pending.length > 0 ? await uploadSources(pending) : [];
        const videoPaths = [...doneUrls, ...uploaded];

        const jobId = crypto.randomUUID();
        const body: Parameters<typeof enqueueExport>[0] = {
            job_uuid: jobId,
            mode: mode.value,
            video_paths: videoPaths,
            silence_threshold_seconds: silenceThreshold.value,
            audio_enhancement_enabled:
                mode.value === 'merge' ? false : audioEnhanceMode.value !== 'off',
            audio_enhance_mode: mode.value === 'merge' ? 'off' : audioEnhanceMode.value,
        };
        if (mode.value === 'ai') {
            body.ai_provider = aiProvider.value;
        }
        if (mode.value === 'ai' && scriptUrl.value) {
            body.script_path = scriptUrl.value;
            body.script_format = scriptFile.value?.name.toLowerCase().endsWith('.pdf')
                ? 'pdf'
                : 'markdown';
        }

        const enqueued = await enqueueExport(body);
        startPolling(enqueued.job_uuid);
        toast.add({
            severity: 'info',
            summary: enqueued.status === 'duplicate' ? 'Job already queued' : 'Export queued',
            life: 3500,
        });
    } catch (error) {
        const message =
            error instanceof HttpError
                ? (typeof error.body === 'object' &&
                  error.body !== null &&
                  'message' in error.body
                    ? String((error.body as { message: string }).message)
                    : `Request failed (${error.status})`)
                : error instanceof Error
                  ? error.message
                  : 'Could not start export';
        toast.add({ severity: 'error', summary: message, life: 6000 });
    } finally {
        submitting.value = false;
    }
}

onUnmounted(() => {
    stopPolling();
});
</script>

<template>
    <Head title="Video Export" />

    <AppHeader
        title="Video Export"
        subtitle="Merge, clean silences, or AI-edit talking-head clips — HD 1080p @ 30 fps"
    />

    <PermissionGuard permission="VIEW_ANY_VIDEO_EXPORTS">
        <template #fallback>
            <div class="ve-empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to use Video Export.</p>
            </div>
        </template>

        <div class="ve-page">
            <section class="ve-modes" aria-label="Export mode">
                <button
                    v-for="card in modeCards"
                    :key="card.value"
                    type="button"
                    class="ve-mode"
                    :class="{ 've-mode--active': mode === card.value }"
                    :aria-pressed="mode === card.value"
                    @click="mode = card.value"
                >
                    <i :class="card.icon" aria-hidden="true" />
                    <span class="ve-mode__title">{{ card.title }}</span>
                    <span class="ve-mode__blurb">{{ card.blurb }}</span>
                </button>
            </section>

            <Card class="ve-panel">
                <template #title>Source clips</template>
                <template #subtitle>Upload goes straight to R2 — the app never streams multi‑GB bodies.</template>
                <template #content>
                    <PermissionGuard permission="CREATE_VIDEO_EXPORTS">
                        <label
                            class="ve-dropzone"
                            :class="{ 've-dropzone--active': isDragOver }"
                            @dragover="onDragOver"
                            @dragleave="onDragLeave"
                            @drop="onDrop"
                        >
                            <input
                                type="file"
                                accept="video/*"
                                multiple
                                hidden
                                :disabled="submitting"
                                @change="onFilesSelected"
                            />
                            <i class="pi pi-cloud-upload" aria-hidden="true" />
                            <span class="ve-dropzone__title">Drop videos here</span>
                            <span class="ve-dropzone__hint">or click to browse — order is top → bottom</span>
                        </label>

                        <ul v-if="files.length" class="ve-files">
                            <li v-for="item in files" :key="item.id" class="ve-file" :data-status="item.status">
                                <i
                                    class="pi"
                                    :class="{
                                        'pi-video': item.status !== 'done' && item.status !== 'error',
                                        'pi-check-circle': item.status === 'done',
                                        'pi-exclamation-circle': item.status === 'error',
                                    }"
                                    aria-hidden="true"
                                />
                                <div class="ve-file__meta">
                                    <span class="ve-file__name">{{ item.file.name }}</span>
                                    <div
                                        v-if="item.status === 'uploading'"
                                        class="ve-progress"
                                        role="progressbar"
                                        :aria-valuenow="item.progress"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                    >
                                        <div class="ve-progress__bar" :style="{ width: `${item.progress}%` }" />
                                    </div>
                                    <span v-else-if="item.status === 'error'" class="ve-file__error">
                                        {{ item.error }}
                                    </span>
                                    <span v-else-if="item.status === 'done'" class="ve-file__ok">Uploaded</span>
                                </div>
                                <Button
                                    type="button"
                                    icon="pi pi-times"
                                    text
                                    rounded
                                    severity="secondary"
                                    aria-label="Remove file"
                                    :disabled="submitting"
                                    @click="removeFile(item.id)"
                                />
                            </li>
                        </ul>

                        <div v-if="showCleanOptions" class="ve-options">
                            <div class="ve-field">
                                <label for="silence-threshold">Silence longer than</label>
                                <Select
                                    input-id="silence-threshold"
                                    v-model="silenceThreshold"
                                    :options="silenceOptions"
                                    option-label="label"
                                    option-value="value"
                                    class="ve-select"
                                />
                            </div>
                            <div class="ve-field">
                                <label for="audio-enhance">Audio enhancement</label>
                                <Select
                                    input-id="audio-enhance"
                                    v-model="audioEnhanceMode"
                                    :options="enhanceOptions"
                                    option-label="label"
                                    option-value="value"
                                    class="ve-select"
                                />
                                <span class="ve-hint">
                                    DSP = local FFmpeg noise polish. AI denoise = neural cleaner
                                    (arnndn model or HTTP). Fillers need AI integrate mode + Whisper.
                                </span>
                            </div>
                        </div>

                        <div v-if="showScript" class="ve-options">
                            <div class="ve-field">
                                <label for="ai-provider">Script review AI provider</label>
                                <Select
                                    input-id="ai-provider"
                                    v-model="aiProvider"
                                    :options="providerOptions"
                                    option-label="label"
                                    option-value="value"
                                    class="ve-select"
                                />
                                <span class="ve-hint">
                                    Speech cuts always use OpenAI Whisper. This select is for guion review only.
                                </span>
                            </div>
                        </div>

                        <div v-if="showScript" class="ve-script">
                            <label class="ve-script__label">Script / guion (optional PDF or Markdown)</label>
                            <div class="ve-script__row">
                                <input
                                    ref="scriptInput"
                                    type="file"
                                    accept=".pdf,.md,.markdown,application/pdf,text/markdown"
                                    hidden
                                    :disabled="scriptUploading || submitting"
                                    @change="onScriptSelected"
                                />
                                <Button
                                    type="button"
                                    label="Upload script"
                                    icon="pi pi-file"
                                    outlined
                                    :loading="scriptUploading"
                                    :disabled="submitting"
                                    @click="scriptInput?.click()"
                                />
                                <span v-if="scriptFile" class="ve-script__name">{{ scriptFile.name }}</span>
                                <Button
                                    v-if="scriptUrl"
                                    type="button"
                                    icon="pi pi-times"
                                    text
                                    rounded
                                    aria-label="Remove script"
                                    @click="clearScript"
                                />
                            </div>
                        </div>

                        <div class="ve-actions">
                            <Button
                                type="button"
                                label="Start export"
                                icon="pi pi-play"
                                :loading="submitting"
                                :disabled="!canSubmit"
                                @click="onSubmit"
                            />
                        </div>
                    </PermissionGuard>
                </template>
            </Card>

            <Card v-if="jobUuid" class="ve-job">
                <template #title>Job</template>
                <template #content>
                    <div class="ve-job__header">
                        <code class="ve-job__id">{{ jobUuid }}</code>
                        <Tag
                            v-if="jobStatus"
                            :value="jobStatus"
                            :severity="
                                jobStatus === 'completed'
                                    ? 'success'
                                    : jobStatus === 'failed'
                                      ? 'danger'
                                      : 'info'
                            "
                        />
                        <Button
                            type="button"
                            label="Refresh"
                            icon="pi pi-refresh"
                            text
                            size="small"
                            @click="pollOnce"
                        />
                    </div>

                    <Message
                        v-if="jobStatus === 'failed'"
                        severity="error"
                        :closable="false"
                        class="ve-msg"
                    >
                        {{ jobPayload?.error ?? 'The export job failed.' }}
                    </Message>

                    <div
                        v-else-if="jobStatus !== 'completed'"
                        class="ve-job__progress"
                        role="status"
                    >
                        <i class="pi pi-spin pi-spinner" aria-hidden="true" />
                        <span>Processing… this updates automatically.</span>
                    </div>

                    <div v-else-if="jobPayload?.result" class="ve-result">
                        <div class="ve-result__row">
                            <span>Duration</span>
                            <strong>{{ jobPayload.result.duration_seconds }}s</strong>
                        </div>
                        <div class="ve-result__row">
                            <span>Output</span>
                            <a
                                v-if="jobPayload.result.storage_url"
                                class="ve-result__link"
                                :href="jobPayload.result.storage_url"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Download / view export
                            </a>
                            <span v-else>No storage URL returned</span>
                        </div>
                        <div v-if="jobPayload.result.diagnostics" class="ve-diags">
                            <h3>Diagnostics</h3>
                            <dl>
                                <template
                                    v-for="(value, key) in jobPayload.result.diagnostics"
                                    :key="key"
                                >
                                    <dt>{{ key }}</dt>
                                    <dd>{{ value }}</dd>
                                </template>
                            </dl>
                        </div>
                        <Message
                            v-if="jobPayload.result.review"
                            severity="info"
                            :closable="false"
                            class="ve-msg"
                        >
                            <pre class="ve-review">{{ jobPayload.result.review }}</pre>
                        </Message>
                    </div>
                </template>
            </Card>
        </div>
    </PermissionGuard>
</template>

<style scoped>
.ve-page {
    display: flex;
    flex-direction: column;
    gap: var(--space-6);
    padding: var(--space-4) var(--space-4) var(--space-10);
    max-width: 72rem;
    margin-inline: auto;
    font-family: var(--font-sans);
}

.ve-empty {
    display: grid;
    place-items: center;
    gap: var(--space-3);
    min-height: 40vh;
    color: var(--text-secondary);
}

.ve-modes {
    display: grid;
    gap: var(--space-3);
    grid-template-columns: 1fr;
}

@media (min-width: 768px) {
    .ve-modes {
        grid-template-columns: repeat(3, 1fr);
    }
}

.ve-mode {
    text-align: left;
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    padding: var(--space-5);
    border-radius: var(--radius-xl);
    border: 1px solid var(--border-default);
    background:
        linear-gradient(
            145deg,
            color-mix(in srgb, var(--accent-primary) 8%, var(--bg-card)),
            var(--bg-card)
        );
    color: var(--text-primary);
    cursor: pointer;
    transition:
        border-color var(--transition),
        transform var(--transition),
        box-shadow var(--transition);
}

.ve-mode:hover {
    border-color: color-mix(in srgb, var(--accent-primary) 45%, var(--border-default));
    transform: translateY(-1px);
}

.ve-mode--active {
    border-color: var(--accent-primary);
    box-shadow: 0 0 0 1px color-mix(in srgb, var(--accent-primary) 35%, transparent);
    background:
        linear-gradient(
            145deg,
            color-mix(in srgb, var(--accent-primary) 16%, var(--bg-card)),
            color-mix(in srgb, var(--accent-secondary) 8%, var(--bg-card))
        );
}

.ve-mode i {
    font-size: 1.25rem;
    color: var(--accent-primary);
}

.ve-mode__title {
    font-weight: var(--font-semibold);
    font-size: var(--text-lg);
}

.ve-mode__blurb {
    color: var(--text-secondary);
    font-size: var(--text-sm);
    line-height: 1.45;
}

.ve-dropzone {
    display: grid;
    place-items: center;
    gap: var(--space-2);
    padding: var(--space-8) var(--space-4);
    border: 1.5px dashed var(--border-strong);
    border-radius: var(--radius-lg);
    background: color-mix(in srgb, var(--accent-primary) 4%, var(--bg-surface));
    cursor: pointer;
    transition: border-color var(--transition), background var(--transition);
}

.ve-dropzone--active,
.ve-dropzone:hover {
    border-color: var(--accent-primary);
    background: color-mix(in srgb, var(--accent-primary) 10%, var(--bg-surface));
}

.ve-dropzone i {
    font-size: 1.75rem;
    color: var(--accent-primary);
}

.ve-dropzone__title {
    font-weight: var(--font-semibold);
}

.ve-dropzone__hint {
    color: var(--text-muted);
    font-size: var(--text-sm);
}

.ve-files {
    list-style: none;
    margin: var(--space-4) 0 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.ve-file {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-3);
    border-radius: var(--radius-md);
    background: var(--bg-hover);
    border: 1px solid var(--border-subtle);
}

.ve-file__meta {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
}

.ve-file__name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: var(--text-sm);
}

.ve-file__error {
    color: var(--accent-error);
    font-size: var(--text-xs);
}

.ve-file__ok {
    color: var(--accent-success);
    font-size: var(--text-xs);
}

.ve-progress {
    height: 0.35rem;
    border-radius: 999px;
    background: var(--border-subtle);
    overflow: hidden;
}

.ve-progress__bar {
    height: 100%;
    background: var(--grad-primary);
    transition: width var(--transition);
}

.ve-options {
    display: grid;
    gap: var(--space-4);
    margin-top: var(--space-5);
    grid-template-columns: 1fr;
}

@media (min-width: 640px) {
    .ve-options {
        grid-template-columns: 1fr 1fr;
        align-items: end;
    }
}

.ve-field {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.ve-field--row {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
}

.ve-field label {
    color: var(--text-secondary);
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
}

.ve-hint {
    color: var(--text-muted);
    font-size: var(--text-xs);
    line-height: 1.4;
}

.ve-select {
    width: 100%;
}

.ve-script {
    margin-top: var(--space-5);
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.ve-script__label {
    color: var(--text-secondary);
    font-size: var(--text-sm);
}

.ve-script__row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-2);
}

.ve-script__name {
    font-size: var(--text-sm);
    color: var(--text-primary);
}

.ve-actions {
    margin-top: var(--space-6);
    display: flex;
    justify-content: flex-end;
}

.ve-job__header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-3);
    margin-bottom: var(--space-4);
}

.ve-job__id {
    font-size: var(--text-xs);
    color: var(--text-muted);
    word-break: break-all;
}

.ve-job__progress {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    color: var(--text-secondary);
}

.ve-result {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
}

.ve-result__row {
    display: flex;
    justify-content: space-between;
    gap: var(--space-3);
    font-size: var(--text-sm);
}

.ve-result__link {
    color: var(--accent-primary);
    text-decoration: underline;
    text-underline-offset: 2px;
}

.ve-diags h3 {
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
    margin: var(--space-2) 0;
}

.ve-diags dl {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: var(--space-1) var(--space-4);
    font-size: var(--text-xs);
    color: var(--text-secondary);
}

.ve-diags dt {
    opacity: 0.85;
}

.ve-diags dd {
    margin: 0;
    text-align: right;
    color: var(--text-primary);
}

.ve-review {
    white-space: pre-wrap;
    font-family: var(--font-mono);
    font-size: var(--text-xs);
    margin: 0;
}

.ve-msg {
    margin-top: var(--space-3);
}
</style>
