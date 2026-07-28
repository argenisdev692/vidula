<script setup lang="ts">
/**
 * Seed markdown → start async generation → live/poll progress → package download.
 * Seed shapes:
 *   classroom → indice-curso-copilot.md
 *   video_*   → pildoras_video_claude_usuarios.md
 */
import { computed, onUnmounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import TextareaField from '@/common/form/TextareaField.vue';
import GradientButton from '@/common/form/GradientButton.vue';
import AiProgressBar from '@/common/ai/AiProgressBar.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import Button from '@/volt/Button.vue';
import SecondaryButton from '@/volt/SecondaryButton.vue';
import ToggleSwitch from '@/volt/ToggleSwitch.vue';
import { apiFetch, HttpError } from '@/lib/http';
import { useAuthorization } from '@/modules/auth/composables/useAuthorization';
import { useProductGenerationProgress } from '@/modules/products/composables/useProductGenerationProgress';
import type {
    Product,
    ProductGenerationStatus,
    ProductType,
} from '@/modules/products/types';

const props = defineProps<{
    product: Product;
    generation: ProductGenerationStatus | null;
}>();

const emit = defineEmits<{ refreshed: [] }>();

const toast = useToast();
const { hasPermission } = useAuthorization();
const canGenerate = computed<boolean>(() => hasPermission('GENERATE_PRODUCTS'));
const canDownload = computed<boolean>(() => hasPermission('DOWNLOAD_PRODUCTS'));

const markdown = ref<string>('');
const fileInput = ref<HTMLInputElement | null>(null);
const submitting = ref<boolean>(false);
const downloading = ref<boolean>(false);
const forceReplace = ref<boolean>(false);
const localStatus = ref<ProductGenerationStatus | null>(props.generation);
const pollTimer = ref<ReturnType<typeof setInterval> | null>(null);

const { progress, reset: resetProgress } = useProductGenerationProgress();

watch(
    () => props.generation,
    (value) => {
        localStatus.value = value;
    },
);

const seedHint = computed<string>(() => {
    const type: ProductType = props.product.type;
    if (type === 'classroom') {
        return 'Paste a classroom index (### Sesión N | title → - **Tema N:** …) — e.g. indice-curso-copilot.md';
    }
    return 'Paste a video/pills index (### BLOQUE N + video table) — e.g. pildoras_video_claude_usuarios.md';
});

const isInFlight = computed<boolean>(() => {
    const status = localStatus.value?.status;
    return status !== undefined && !['completed', 'failed'].includes(status);
});

const liveProgress = computed(() => {
    const event = progress.value;
    if (!event || event.product_uuid !== props.product.uuid) {
        return null;
    }
    if (localStatus.value && event.generation_uuid !== localStatus.value.uuid) {
        return null;
    }
    return event;
});

const displayPercent = computed<number>(() => liveProgress.value?.progress ?? localStatus.value?.progress ?? 0);
const displayMessage = computed<string>(() => {
    if (liveProgress.value?.message) {
        return liveProgress.value.message;
    }
    if (localStatus.value?.status === 'failed') {
        return localStatus.value.error || 'Generation failed.';
    }
    if (localStatus.value?.status === 'completed') {
        return 'Generation completed.';
    }
    return localStatus.value ? `Status: ${localStatus.value.status}` : 'No generation yet.';
});

const hasPackage = computed<boolean>(() => Boolean(localStatus.value?.has_package));
const actionsDisabled = computed<boolean>(() => isInFlight.value || submitting.value);

function stopPolling(): void {
    if (pollTimer.value !== null) {
        clearInterval(pollTimer.value);
        pollTimer.value = null;
    }
}

async function pollStatus(generationUuid: string): Promise<void> {
    try {
        const response = await apiFetch<{ data: ProductGenerationStatus }>(
            'GET',
            `/products/${props.product.uuid}/generations/${generationUuid}`,
        );
        localStatus.value = response.data;
        if (['completed', 'failed'].includes(response.data.status)) {
            stopPolling();
            emit('refreshed');
            router.reload({ only: ['generation', 'sessions'], preserveScroll: true });
        }
    } catch {
        // Keep polling; transient network blips should not abort the UI.
    }
}

function startPolling(generationUuid: string): void {
    stopPolling();
    void pollStatus(generationUuid);
    pollTimer.value = setInterval(() => {
        void pollStatus(generationUuid);
    }, 2500);
}

watch(
    isInFlight,
    (flying) => {
        if (flying && localStatus.value?.uuid) {
            startPolling(localStatus.value.uuid);
        } else if (!flying) {
            stopPolling();
        }
    },
    { immediate: true },
);

onUnmounted(() => {
    stopPolling();
});

function onFileChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) {
        return;
    }
    const reader = new FileReader();
    reader.onload = () => {
        markdown.value = typeof reader.result === 'string' ? reader.result : '';
    };
    reader.readAsText(file);
}

function openFilePicker(): void {
    fileInput.value?.click();
}

function submitGeneration(): void {
    if (!canGenerate.value || submitting.value) {
        return;
    }
    const seed = markdown.value.trim();
    if (seed === '') {
        toast.add({ severity: 'warn', summary: 'Seed required', detail: 'Paste or upload a markdown index.', life: 4000 });
        return;
    }

    submitting.value = true;
    resetProgress();

    router.post(
        `/products/${props.product.uuid}/generate-content`,
        {
            markdown: seed,
            mode: forceReplace.value ? 'force_replace' : 'replace',
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Queued',
                    detail: 'Content generation started.',
                    life: 3000,
                });
                router.reload({
                    only: ['generation', 'sessions'],
                    preserveScroll: true,
                    onSuccess: () => {
                        if (localStatus.value?.uuid || props.generation?.uuid) {
                            const uuid = props.generation?.uuid ?? localStatus.value?.uuid;
                            if (uuid) {
                                startPolling(uuid);
                            }
                        }
                    },
                });
            },
            onError: (errors) => {
                const message = errors.markdown || errors.file || Object.values(errors)[0] || 'Could not start generation.';
                toast.add({ severity: 'error', summary: 'Generation blocked', detail: String(message), life: 5000 });
            },
            onFinish: () => {
                submitting.value = false;
            },
        },
    );
}

async function downloadPackage(): Promise<void> {
    if (!canDownload.value || downloading.value) {
        return;
    }
    downloading.value = true;
    try {
        const response = await apiFetch<{ data: { url: string } }>(
            'GET',
            `/products/${props.product.uuid}/package/download`,
        );
        window.open(response.data.url, '_blank', 'noopener,noreferrer');
    } catch (error) {
        const detail =
            error instanceof HttpError && error.status === 404
                ? 'Package not ready yet. Wait for generation to finish.'
                : 'Could not get package download link.';
        toast.add({ severity: 'warn', summary: 'Download unavailable', detail, life: 5000 });
    } finally {
        downloading.value = false;
    }
}
</script>

<template>
    <section class="gen-panel">
        <header class="gen-panel__header">
            <div>
                <h2>Content generation</h2>
                <p class="muted">{{ seedHint }}</p>
            </div>
            <div v-if="localStatus" class="gen-panel__meta">
                <span class="chip">{{ localStatus.status }}</span>
                <span class="muted">
                    {{ localStatus.sessions_count }} sessions · {{ localStatus.topics_count }} topics ·
                    {{ localStatus.scripts_count }} scripts
                </span>
            </div>
        </header>

        <AiProgressBar v-if="isInFlight || liveProgress" :message="displayMessage" :percent="displayPercent" />
        <p v-else-if="localStatus?.status === 'failed'" class="error">{{ displayMessage }}</p>
        <p v-else-if="localStatus?.status === 'completed'" class="ok">{{ displayMessage }}</p>

        <PermissionGuard permission="GENERATE_PRODUCTS">
            <TextareaField
                v-model="markdown"
                name="markdown"
                label="Seed markdown"
                :placeholder="seedHint"
                :rows="10"
                :disabled="actionsDisabled"
            />

            <div class="gen-panel__actions">
                <input
                    ref="fileInput"
                    type="file"
                    accept=".md,.markdown,text/markdown,text/plain"
                    class="sr-only"
                    :disabled="actionsDisabled"
                    @change="onFileChange"
                />

                <SecondaryButton
                    type="button"
                    icon="pi pi-upload"
                    label="Upload .md"
                    :disabled="actionsDisabled"
                    aria-label="Upload markdown seed file"
                    @click="openFilePicker"
                />

                <label class="force-toggle" for="products-force-replace">
                    <ToggleSwitch
                        v-model="forceReplace"
                        input-id="products-force-replace"
                        :disabled="actionsDisabled"
                        aria-label="Force replace verified scripts"
                    />
                    <span>Force replace verified scripts</span>
                </label>

                <GradientButton
                    type="button"
                    icon="pi pi-sparkles"
                    :label="submitting ? 'Starting…' : 'Generate content'"
                    :disabled="actionsDisabled || markdown.trim() === ''"
                    :loading="submitting"
                    @click="submitGeneration"
                />
            </div>
        </PermissionGuard>

        <PermissionGuard permission="DOWNLOAD_PRODUCTS">
            <div class="gen-panel__download">
                <Button
                    type="button"
                    outlined
                    icon="pi pi-download"
                    :label="downloading ? 'Opening…' : 'Download ZIP'"
                    :disabled="!hasPackage || downloading"
                    :loading="downloading"
                    aria-label="Download generated ZIP package"
                    @click="downloadPackage"
                />
            </div>
        </PermissionGuard>
    </section>
</template>

<style scoped>
.gen-panel {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}

.gen-panel__header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-3);
}

.gen-panel__header h2 {
    margin: 0;
    font-size: var(--text-lg);
    font-family: var(--font-sans);
}

.muted {
    margin: 0.25rem 0 0;
    color: var(--text-muted);
    font-size: var(--text-sm);
}

.gen-panel__meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: var(--space-1);
}

.chip {
    display: inline-flex;
    padding: 0.15rem 0.55rem;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-default);
    background: var(--bg-card);
    color: var(--text-secondary);
    font-size: var(--text-xs);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.gen-panel__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-3);
}

.gen-panel__download {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-3);
}

.force-toggle {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    font-size: var(--text-sm);
    color: var(--text-secondary);
    cursor: pointer;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
}

.error {
    margin: 0;
    color: var(--accent-error);
    font-size: var(--text-sm);
}

.ok {
    margin: 0;
    color: var(--accent-success);
    font-size: var(--text-sm);
}
</style>
