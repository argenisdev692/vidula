<script setup lang="ts">
/**
 * Domain-agnostic signature pad. Thin Vue 3 wrapper around the battle-tested
 * `signature_pad` engine (the same core `react-signature-canvas` wraps) that
 * exports the drawing as a transparent PNG `Blob` ready to upload as multipart
 * form data.
 *
 * The pad is intentionally rendered as light "paper" with dark ink in BOTH
 * themes (via the theme-independent `--signature-*` tokens) so the exported
 * signature stays legible on white documents/emails. `signature_pad` keeps the
 * canvas background transparent so the PNG carries only the strokes.
 *
 * The parent grabs the drawing via the exposed `toBlob()` method (e.g. on a
 * "Save signature" click) and can `clear()` it. `@change` reports whether the
 * pad is currently empty so callers can enable/disable their save action.
 * Undo is implemented on top of `toData()`/`fromData()` (signature_pad has no
 * built-in undo).
 */
import { onBeforeUnmount, onMounted, useTemplateRef, watch } from 'vue';
import { ref } from 'vue';
import SignaturePadEngine from 'signature_pad';
import Button from '@/volt/Button.vue';

const props = withDefaults(
    defineProps<{
        disabled?: boolean;
        /** Canvas height in px (width is fluid). */
        height?: number;
        /** Maximum stroke thickness in px. */
        maxWidth?: number;
        /** Ink color — defaults to the theme-independent signature ink token. */
        penColor?: string;
    }>(),
    { disabled: false, height: 200, maxWidth: 2.5 },
);

const emit = defineEmits<{ change: [isEmpty: boolean] }>();

const canvasRef = useTemplateRef<HTMLCanvasElement>('canvas');
const isEmpty = ref<boolean>(true);

let pad: SignaturePadEngine | null = null;
let resizeObserver: ResizeObserver | null = null;

function resolveInk(): string {
    if (props.penColor) {
        return props.penColor;
    }
    const token = getComputedStyle(document.documentElement).getPropertyValue('--signature-ink').trim();
    return token || '#1a1a2e';
}

function syncEmpty(): void {
    const empty = pad?.isEmpty() ?? true;
    isEmpty.value = empty;
    emit('change', empty);
}

/**
 * Resize the drawing buffer to the CSS box × devicePixelRatio, preserving the
 * current strokes. signature_pad does not manage HiDPI/resize itself.
 */
function resizeCanvas(): void {
    const canvas = canvasRef.value;
    if (!canvas || !pad) {
        return;
    }
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    const data = pad.toData();
    canvas.width = Math.max(1, canvas.offsetWidth * ratio);
    canvas.height = Math.max(1, canvas.offsetHeight * ratio);
    canvas.getContext('2d')?.scale(ratio, ratio);
    pad.clear();
    if (data.length > 0) {
        pad.fromData(data);
    }
    syncEmpty();
}

function undo(): void {
    if (!pad) {
        return;
    }
    const data = pad.toData();
    data.pop();
    pad.fromData(data);
    syncEmpty();
}

function clear(): void {
    pad?.clear();
    syncEmpty();
}

function toBlob(type = 'image/png'): Promise<Blob | null> {
    return new Promise((resolve) => {
        const canvas = canvasRef.value;
        if (!canvas || !pad || pad.isEmpty()) {
            resolve(null);
            return;
        }
        canvas.toBlob((blob) => resolve(blob), type);
    });
}

watch(
    () => props.disabled,
    (disabled) => {
        if (!pad) {
            return;
        }
        // off()/on() (un)bind signature_pad's own pointer handlers.
        if (disabled) {
            pad.off();
        } else {
            pad.on();
        }
    },
);

onMounted(() => {
    const canvas = canvasRef.value;
    if (!canvas) {
        return;
    }
    pad = new SignaturePadEngine(canvas, {
        penColor: resolveInk(),
        backgroundColor: 'rgba(0,0,0,0)',
        minWidth: Math.max(0.5, props.maxWidth - 1.5),
        maxWidth: props.maxWidth,
    });
    pad.addEventListener('endStroke', syncEmpty);
    resizeCanvas();
    if (props.disabled) {
        pad.off();
    }
    if ('ResizeObserver' in window) {
        resizeObserver = new ResizeObserver(() => resizeCanvas());
        resizeObserver.observe(canvas);
    }
});

onBeforeUnmount(() => {
    resizeObserver?.disconnect();
    pad?.off();
    pad?.removeEventListener('endStroke', syncEmpty);
    pad = null;
});

defineExpose({ toBlob, clear, isEmpty });
</script>

<template>
    <div class="sigpad" :class="{ 'sigpad--disabled': disabled }">
        <div class="sigpad__surface">
            <canvas ref="canvas" class="sigpad__canvas" :style="{ height: `${height}px` }" />
            <p v-if="isEmpty" class="sigpad__placeholder">
                <i class="pi pi-pencil" aria-hidden="true" /> Draw your signature here
            </p>
            <span class="sigpad__baseline" aria-hidden="true" />
        </div>

        <div class="sigpad__toolbar">
            <Button
                text
                size="small"
                icon="pi pi-undo"
                label="Undo"
                :disabled="disabled || isEmpty"
                aria-label="Undo last stroke"
                @click="undo"
            />
            <button
                type="button"
                class="sigpad__clear"
                :disabled="disabled || isEmpty"
                aria-label="Clear signature"
                @click="clear"
            >
                <i class="pi pi-trash" aria-hidden="true" /> Clear
            </button>
        </div>
    </div>
</template>

<style scoped>
.sigpad {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.sigpad__surface {
    position: relative;
    border: 1px dashed var(--border-default);
    border-radius: var(--radius-md);
    /* Always light "paper" so dark ink is visible while drawing and the export
       matches how it renders on white documents (token is theme-independent). */
    background: var(--signature-surface);
    overflow: hidden;
}

.sigpad__canvas {
    display: block;
    width: 100%;
    /* Prevent the page from scrolling while drawing with touch/stylus. */
    touch-action: none;
    cursor: crosshair;
}

.sigpad--disabled .sigpad__canvas {
    cursor: not-allowed;
}

.sigpad__placeholder {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
    margin: 0;
    font-size: var(--text-sm);
    /* Muted ink on the fixed light paper — derived from the signature tokens. */
    color: color-mix(in srgb, var(--signature-ink) 45%, var(--signature-surface));
    pointer-events: none;
}

.sigpad__baseline {
    position: absolute;
    left: var(--space-6);
    right: var(--space-6);
    bottom: 22%;
    height: 1px;
    background: color-mix(in srgb, var(--signature-ink) 15%, var(--signature-surface));
    pointer-events: none;
}

.sigpad__toolbar {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: var(--space-3);
}

.sigpad__clear {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    background: transparent;
    border: none;
    padding: var(--space-1) var(--space-2);
    font-family: var(--font-sans);
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--accent-error);
    cursor: pointer;
    transition: color var(--transition);
}

.sigpad__clear:hover:not(:disabled) {
    color: color-mix(in srgb, var(--accent-error) 70%, var(--text-primary));
    text-decoration: underline;
}

.sigpad__clear:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
