import { defineStore } from 'pinia';
import { computed } from 'vue';
import { useColorMode } from '@vueuse/core';

/**
 * Single source of truth for the light/dark theme.
 *
 * Theme is expressed ONLY as the `.dark` class on <html> (light = absence of it),
 * matching `@custom-variant dark (&:is(.dark *))` in app.css. `useColorMode`
 * persists the choice to localStorage and is the sole persistence layer — no
 * pinia-plugin-persistedstate on top.
 */
export const useThemeStore = defineStore('theme', () => {
    const mode = useColorMode({
        selector: 'html',
        attribute: 'class',
        storageKey: 'vidula-theme',
        // Dark-first: first visit (no stored value) resolves to dark, matching the
        // FOUC killer in app.blade.php — otherwise useColorMode would default to
        // 'auto' (system) and flash against the pre-paint dark class.
        initialValue: 'dark',
        modes: { light: '', dark: 'dark' },
    });

    const isDark = computed<boolean>(() => mode.value === 'dark');

    function toggle(): void {
        mode.value = isDark.value ? 'light' : 'dark';
    }

    function set(dark: boolean): void {
        mode.value = dark ? 'dark' : 'light';
    }

    return { mode, isDark, toggle, set };
});
