<script setup lang="ts">
/**
 * Authenticated app-shell layout. Applied per page via
 * `defineOptions({ layout: AppLayout })`. Hosts the overlay sidebar Drawer, the
 * scroll-reveal floating menu button, and the single app-wide <Toast/>. Pages
 * render their own <AppHeader> at the top of the default slot.
 */
import Toast from '@/volt/Toast.vue';
import AppSidebar from '@/modules/app/components/AppSidebar.vue';
import GradientBackground from '@/modules/app/components/GradientBackground.vue';
import FloatingMenuButton from '@/modules/app/components/FloatingMenuButton.vue';
import { useThemeStore } from '@/modules/app/stores/useThemeStore';
import type { SharedProps } from '@/types/inertia';
import { usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { watch } from 'vue';

// Instantiate the theme controller app-wide (activates useColorMode's .dark sync).
useThemeStore();

// Surface server flash messages as app-wide toasts on load and every visit.
const page = usePage<SharedProps>();
const toast = useToast();

watch(
    () => page.props.flash,
    (flash): void => {
        if (flash?.success) {
            toast.add({ severity: 'success', summary: flash.success, life: 4000 });
        }
        if (flash?.error) {
            toast.add({ severity: 'error', summary: flash.error, life: 6000 });
        }
    },
    { immediate: true, deep: true },
);
</script>

<template>
    <div class="app-shell">
        <GradientBackground />
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
/* Transparent so the fixed aurora GradientBackground (z-index -1) shows through. */
.app-shell {
    display: flex;
    min-height: 100vh;
    color: var(--text-primary);
    font-family: var(--font-sans);
}

.app-main {
    flex: 1;
    min-width: 0;
    min-height: 100vh;
    position: relative;
    z-index: 1;
    overflow-x: hidden;
}

.app-main__inner {
    padding: 2rem;
}

@media (max-width: 768px) {
    .app-main__inner {
        padding: 1rem;
    }
}
</style>
