<script setup lang="ts">
/**
 * Reusable image cropper. Wraps `vue-advanced-cropper` inside a Volt Dialog and
 * emits a cropped `Blob` (512×512 JPEG) ready to upload as multipart form data.
 * Use `circular` for round avatars (profile photos) or a numeric `aspectRatio`
 * for fixed-ratio rectangular crops. Toolbar offers rotate / zoom / reset.
 * Fully token-styled for dark & light.
 */
import { computed, markRaw, ref } from 'vue';
import { CircleStencil, Cropper, RectangleStencil } from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';
import Dialog from '@/volt/Dialog.vue';
import Button from '@/volt/Button.vue';

const visible = defineModel<boolean>('visible', { required: true });

const props = withDefaults(
    defineProps<{
        imageUrl: string | null;
        /** Ignored when `circular` (circle is always 1:1). */
        aspectRatio?: number;
        circular?: boolean;
        title?: string;
        /** Output edge length in px (square). */
        size?: number;
    }>(),
    { aspectRatio: 1, circular: false, title: 'Crop photo', size: 512 },
);

const emit = defineEmits<{ cropped: [blob: Blob] }>();

/** Minimal typed surface of the cropper instance we drive imperatively. */
interface CropperInstance {
    getResult(): { canvas: HTMLCanvasElement | null };
    rotate(angle: number): void;
    zoom(factor: number): void;
    reset(): void;
}

const cropperRef = ref<CropperInstance | null>(null);

const stencilComponent = computed(() =>
    markRaw(props.circular ? CircleStencil : RectangleStencil),
);

const stencilProps = computed(() => (props.circular ? {} : { aspectRatio: props.aspectRatio }));

function apply(): void {
    const result = cropperRef.value?.getResult();
    const canvas = result?.canvas;
    if (!canvas) {
        return;
    }
    canvas.toBlob(
        (blob) => {
            if (blob) {
                emit('cropped', blob);
                visible.value = false;
            }
        },
        'image/jpeg',
        0.92,
    );
}

function rotate(angle: number): void {
    cropperRef.value?.rotate(angle);
}

function zoom(factor: number): void {
    cropperRef.value?.zoom(factor);
}

function reset(): void {
    cropperRef.value?.reset();
}

function cancel(): void {
    visible.value = false;
}
</script>

<template>
    <Dialog v-model:visible="visible" modal :header="title" :draggable="false">
        <div class="cropper">
            <Cropper
                v-if="imageUrl"
                ref="cropperRef"
                class="cropper__canvas"
                :src="imageUrl"
                :stencil-component="stencilComponent"
                :stencil-props="stencilProps"
                :canvas="{ maxWidth: size, maxHeight: size }"
                image-restriction="fit-area"
            />
        </div>

        <div class="cropper__toolbar">
            <Button text rounded icon="pi pi-undo" aria-label="Rotate left" @click="rotate(-90)" />
            <Button text rounded icon="pi pi-refresh" aria-label="Reset" @click="reset" />
            <Button text rounded icon="pi pi-redo" aria-label="Rotate right" @click="rotate(90)" />
            <Button text rounded icon="pi pi-search-minus" aria-label="Zoom out" @click="zoom(0.9)" />
            <Button text rounded icon="pi pi-search-plus" aria-label="Zoom in" @click="zoom(1.1)" />
        </div>

        <template #footer>
            <Button text label="Cancel" @click="cancel" />
            <Button label="Apply" icon="pi pi-check" @click="apply" />
        </template>
    </Dialog>
</template>

<style scoped>
.cropper {
    width: min(460px, calc(100vw - 6rem));
    height: 340px;
    background: var(--bg-void);
    border-radius: var(--radius-md);
    overflow: hidden;
}

.cropper__canvas {
    height: 100%;
}

.cropper__toolbar {
    display: flex;
    justify-content: center;
    gap: var(--space-2);
    margin-top: var(--space-4);
}

/* Theme the vue-advanced-cropper handles/lines with the brand accent. */
.cropper :deep(.vue-advanced-cropper__foreground) {
    opacity: 0.55;
}

.cropper :deep(.vue-simple-handler) {
    background: var(--accent-primary);
}

.cropper :deep(.vue-simple-line) {
    border-color: color-mix(in srgb, var(--accent-primary) 70%, transparent);
}
</style>
