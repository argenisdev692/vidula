<script setup lang="ts">
/**
 * Authenticated app-shell layout. Applied per page via
 * `defineOptions({ layout: AppLayout })`. Hosts the overlay sidebar Drawer, the
 * scroll-reveal floating menu button, and the single app-wide <Toast/>. Pages
 * render their own <AppHeader> at the top of the default slot.
 */
import Toast from '@/volt/Toast.vue';
import AppSidebar from '@/modules/app/components/AppSidebar.vue';
import FloatingMenuButton from '@/modules/app/components/FloatingMenuButton.vue';
import { useThemeStore } from '@/modules/app/stores/useThemeStore';

// Instantiate the theme controller app-wide (activates useColorMode's .dark sync).
useThemeStore();
</script>

<template>
    <div class="app-shell">
        <AppSidebar />

        <main class="app-main">
            <div class="app-main__inner">
                <slot />
            </div>
        </main>

        <FloatingMenuButton />
        <Toast />
    </div>
</template>

<style scoped>
.app-shell {
    min-height: 100vh;
    background: var(--bg-app);
    color: var(--text-primary);
    font-family: var(--font-sans);
}

.app-main {
    min-height: 100vh;
}

.app-main__inner {
    max-width: 1400px;
    margin: 0 auto;
    padding: 1.5rem;
}

@media (max-width: 768px) {
    .app-main__inner {
        padding: 1rem;
    }
}
</style>
