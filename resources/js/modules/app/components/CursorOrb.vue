<script setup lang="ts">
/**
 * Soft accent glow that trails the pointer — the GUIDE app-root "cursor orb".
 * The element is styled globally by `.cursor-orb` in globals.css; this component
 * only positions it on `pointermove` (rAF-throttled) and reveals it after the
 * first move. Skipped on coarse/touch pointers and when the user prefers reduced
 * motion, so it never flashes at the 0,0 corner or distracts on mobile.
 */
import { onBeforeUnmount, onMounted, ref, useTemplateRef } from 'vue';

const orb = useTemplateRef<HTMLDivElement>('orb');
const active = ref<boolean>(false);

let frame = 0;
let nextX = 0;
let nextY = 0;

function onPointerMove(event: PointerEvent): void {
    nextX = event.clientX;
    nextY = event.clientY;
    // Coalesce bursts of pointermove into one paint per frame.
    if (frame !== 0) {
        return;
    }
    frame = requestAnimationFrame(() => {
        frame = 0;
        const element = orb.value;
        if (!element) {
            return;
        }
        element.style.left = `${nextX}px`;
        element.style.top = `${nextY}px`;
        if (!active.value) {
            active.value = true;
        }
    });
}

// Decorative only: fine (mouse/trackpad) pointers, and never under reduced motion.
const enabled =
    typeof window !== 'undefined' &&
    window.matchMedia('(pointer: fine)').matches &&
    !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

onMounted(() => {
    if (enabled) {
        window.addEventListener('pointermove', onPointerMove, { passive: true });
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('pointermove', onPointerMove);
    if (frame !== 0) {
        cancelAnimationFrame(frame);
    }
});
</script>

<template>
    <!-- Teleport to <body> so a transformed ancestor can never turn the fixed
         orb into an offset-positioned element (which would break clientX/Y). -->
    <Teleport to="body">
        <div
            v-if="enabled"
            ref="orb"
            class="cursor-orb"
            :class="{ 'cursor-orb--active': active }"
            aria-hidden="true"
        />
    </Teleport>
</template>
