<script setup lang="ts">
/**
 * Scroll-reveal floating action button that toggles the sidebar Drawer.
 * Appears once the page is scrolled past 80px. Self-contained: reads/writes the
 * app-shell store directly, so the layout mounts it once with no wiring.
 * Ported from the GUIDE `app-floating-menu-button`.
 */
import { computed } from 'vue';
import { useWindowScroll } from '@vueuse/core';
import { useAppShellStore } from '@/modules/app/stores/useAppShellStore';

const shell = useAppShellStore();
const { y } = useWindowScroll();

const isVisible = computed<boolean>(() => y.value > 80);
</script>

<template>
    <Transition name="fab">
        <button
            v-if="isVisible"
            type="button"
            class="floating-menu-btn"
            :class="{ 'drawer-open': shell.sidebarOpen }"
            :aria-label="shell.sidebarOpen ? 'Close navigation menu' : 'Open navigation menu'"
            :title="shell.sidebarOpen ? 'Close menu' : 'Menu'"
            @click="shell.toggleSidebar()"
        >
            <i class="pi" :class="shell.sidebarOpen ? 'pi-times' : 'pi-bars'" aria-hidden="true" />
        </button>
    </Transition>
</template>

<style scoped>
.floating-menu-btn {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 999;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: color-mix(in srgb, var(--bg-surface) 82%, transparent);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid var(--border-default);
    box-shadow:
        0 8px 32px color-mix(in srgb, var(--bg-app) 40%, transparent),
        0 0 0 1px color-mix(in srgb, var(--accent-primary) 10%, transparent);
    color: var(--text-primary);
    font-size: 1.25rem;
    cursor: pointer;
    transition: transform var(--transition), border-color var(--transition), box-shadow var(--transition),
        color var(--transition);
}

.floating-menu-btn:hover {
    transform: scale(1.06) translateY(-2px);
    border-color: var(--border-hover);
    color: var(--accent-primary);
    box-shadow:
        0 12px 40px color-mix(in srgb, var(--accent-primary) 30%, transparent),
        0 0 0 1px color-mix(in srgb, var(--accent-primary) 25%, transparent);
}

.floating-menu-btn:active {
    transform: scale(0.95);
}

.floating-menu-btn.drawer-open {
    border-color: color-mix(in srgb, var(--accent-error) 40%, transparent);
    color: var(--accent-error);
    box-shadow:
        0 8px 32px color-mix(in srgb, var(--accent-error) 25%, transparent),
        0 0 0 1px color-mix(in srgb, var(--accent-error) 20%, transparent);
}

.fab-enter-active,
.fab-leave-active {
    transition: opacity var(--transition), transform var(--transition);
}

.fab-enter-from,
.fab-leave-to {
    opacity: 0;
    transform: translateY(20px) scale(0.85);
}
</style>
