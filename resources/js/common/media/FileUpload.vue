<script setup lang="ts">
/**
 * Domain-agnostic file upload with drag-and-drop, click-to-browse, inline
 * client-side validation (accept + max size) and an image thumbnail / file chip
 * preview. `v-model` is the selected `File | null`; the parent uploads it (e.g.
 * via Inertia `router.post` with `forceFormData`). Purely presentational — it
 * never touches the network itself, so it stays reusable across modules.
 */
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import FormField from '@/common/form/FormField.vue';

const model = defineModel<File | null>({ default: null });

const props = withDefaults(
    defineProps<{
        label?: string;
        /** Native accept string, e.g. `image/png` or `.png,.pdf`. */
        accept?: string;
        /** Max file size in megabytes. */
        maxSizeMb?: number;
        hint?: string;
        /** Server-side error passed down from the page. */
        error?: string;
        disabled?: boolean;
        /** URL of an already-stored file to preview when nothing is selected. */
        currentUrl?: string | null;
        /** Icon shown in the empty dropzone. */
        icon?: string;
    }>(),
    { accept: '', maxSizeMb: 2, disabled: false, currentUrl: null, icon: 'pi pi-cloud-upload' },
);

const emit = defineEmits<{ select: [file: File]; clear: [] }>();

const inputRef = ref<HTMLInputElement | null>(null);
const dragging = ref<boolean>(false);
const localError = ref<string>('');
const previewUrl = ref<string | null>(null);
const currentBroken = ref<boolean>(false);

const displayError = computed<string>(() => localError.value || props.error || '');
const isImage = computed<boolean>(() => !!model.value && model.value.type.startsWith('image/'));

const constraints = computed<string>(() => {
    const label = props.accept
        ? props.accept
              .split(',')
              .map((part) => part.trim().replace(/^.*\//, '').replace(/^\./, '').toUpperCase())
              .filter(Boolean)
              .join(', ')
        : '';
    const size = `up to ${props.maxSizeMb} MB`;
    return label ? `${label} · ${size}` : size;
});

watch(model, (file) => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }
    if (file && file.type.startsWith('image/')) {
        previewUrl.value = URL.createObjectURL(file);
    }
});

function openPicker(): void {
    if (props.disabled) {
        return;
    }
    inputRef.value?.click();
}

function acceptMatches(file: File): boolean {
    if (!props.accept) {
        return true;
    }
    const patterns = props.accept.split(',').map((pattern) => pattern.trim().toLowerCase());
    const name = file.name.toLowerCase();
    const mime = file.type.toLowerCase();
    return patterns.some((pattern) => {
        if (pattern.startsWith('.')) {
            return name.endsWith(pattern);
        }
        if (pattern.endsWith('/*')) {
            return mime.startsWith(pattern.slice(0, -1));
        }
        return mime === pattern;
    });
}

function validate(file: File): string {
    if (!acceptMatches(file)) {
        return 'Unsupported file type.';
    }
    if (file.size > props.maxSizeMb * 1024 * 1024) {
        return `File must be ${props.maxSizeMb} MB or smaller.`;
    }
    return '';
}

function handleFiles(files: FileList | null): void {
    const file = files?.[0];
    if (!file) {
        return;
    }
    const message = validate(file);
    if (message) {
        localError.value = message;
        model.value = null;
        return;
    }
    localError.value = '';
    model.value = file;
    emit('select', file);
}

function onInputChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    handleFiles(input.files);
    // Reset so selecting the same file again re-triggers change.
    input.value = '';
}

function onDrop(event: DragEvent): void {
    dragging.value = false;
    if (props.disabled) {
        return;
    }
    handleFiles(event.dataTransfer?.files ?? null);
}

function clearSelection(): void {
    model.value = null;
    localError.value = '';
    emit('clear');
}

function formatSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }
    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

onBeforeUnmount(() => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
});
</script>

<template>
    <FormField :label="label" :error="displayError" :hint="hint">
        <div
            class="fileup"
            :class="{
                'fileup--dragging': dragging,
                'fileup--disabled': disabled,
                'fileup--invalid': !!displayError,
            }"
            role="button"
            :tabindex="disabled ? -1 : 0"
            :aria-label="label ? `Upload ${label}` : 'Upload file'"
            @click="openPicker"
            @keydown.enter.prevent="openPicker"
            @keydown.space.prevent="openPicker"
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDrop"
        >
            <input
                ref="inputRef"
                type="file"
                class="fileup__input"
                :accept="accept"
                :disabled="disabled"
                @change="onInputChange"
            />

            <!-- Newly selected file -->
            <div v-if="model" class="fileup__selected" @click.stop="openPicker">
                <img v-if="isImage && previewUrl" :src="previewUrl" alt="" class="fileup__thumb" />
                <span v-else class="fileup__fileicon" aria-hidden="true"><i class="pi pi-file" /></span>
                <span class="fileup__meta">
                    <span class="fileup__name">{{ model.name }}</span>
                    <span class="fileup__size">{{ formatSize(model.size) }}</span>
                </span>
                <button
                    type="button"
                    class="fileup__remove"
                    :disabled="disabled"
                    aria-label="Remove selected file"
                    @click.stop="clearSelection"
                >
                    <i class="pi pi-times" aria-hidden="true" />
                </button>
            </div>

            <!-- Existing stored file -->
            <div v-else-if="currentUrl && !currentBroken" class="fileup__selected" @click.stop="openPicker">
                <img :src="currentUrl" alt="" class="fileup__thumb" @error="currentBroken = true" />
                <span class="fileup__meta">
                    <span class="fileup__name">Current file</span>
                    <span class="fileup__size">Click to replace</span>
                </span>
            </div>

            <!-- Empty dropzone -->
            <div v-else class="fileup__empty">
                <i :class="icon" class="fileup__icon" aria-hidden="true" />
                <span class="fileup__prompt"><strong>Click to upload</strong> or drag and drop</span>
                <span class="fileup__constraints">{{ constraints }}</span>
            </div>
        </div>
    </FormField>
</template>

<style scoped>
.fileup {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 8rem;
    padding: var(--space-4);
    border: 1px dashed var(--border-default);
    border-radius: var(--radius-md);
    background: var(--bg-surface);
    cursor: pointer;
    transition: border-color var(--transition), background var(--transition);
}

.fileup:hover:not(.fileup--disabled),
.fileup:focus-visible {
    border-color: var(--accent-primary);
    background: color-mix(in srgb, var(--accent-primary) 5%, var(--bg-surface));
}

.fileup--dragging {
    border-color: var(--accent-primary);
    background: color-mix(in srgb, var(--accent-primary) 10%, var(--bg-surface));
}

.fileup--invalid {
    border-color: var(--accent-error);
}

.fileup--disabled {
    cursor: not-allowed;
    opacity: 0.6;
}

.fileup__input {
    display: none;
}

.fileup__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-2);
    text-align: center;
    color: var(--text-muted);
}

.fileup__icon {
    font-size: var(--text-2xl);
    color: var(--accent-primary);
}

.fileup__prompt {
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

.fileup__prompt strong {
    color: var(--accent-primary);
    font-weight: var(--font-semibold);
}

.fileup__constraints {
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.fileup__selected {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    width: 100%;
    min-width: 0;
}

.fileup__thumb {
    width: 56px;
    height: 56px;
    flex-shrink: 0;
    object-fit: contain;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-subtle);
    background: var(--bg-elevated);
}

.fileup__fileicon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 56px;
    height: 56px;
    flex-shrink: 0;
    border-radius: var(--radius-sm);
    background: color-mix(in srgb, var(--accent-primary) 12%, transparent);
    color: var(--accent-primary);
    font-size: var(--text-xl);
}

.fileup__meta {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
    flex: 1;
}

.fileup__name {
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--text-primary);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.fileup__size {
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.fileup__remove {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    flex-shrink: 0;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-subtle);
    background: transparent;
    color: var(--accent-error);
    cursor: pointer;
    transition: border-color var(--transition);
}

.fileup__remove:hover:not(:disabled) {
    border-color: currentColor;
}

.fileup__remove:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
