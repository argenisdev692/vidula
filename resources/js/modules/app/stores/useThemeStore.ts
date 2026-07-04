import { defineStore } from 'pinia';
import { computed, watch } from 'vue';
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

    // Keep the inline `color-scheme` in sync with the resolved mode. The FOUC
    // killer sets it once inline (which outranks the CSS `.dark { color-scheme }`
    // rule), so without this watcher native controls and the themed scrollbar
    // keep the pre-toggle scheme until a reload (FRONTEND/SKILL.md §1.5 #3).
    watch(
        () => mode.value,
        (next): void => {
            if (typeof document !== 'undefined') {
                document.documentElement.style.colorScheme = next === 'dark' ? 'dark' : 'light';
            }
        },
        { immediate: true },
    );

    function toggle(): void {
        mode.value = isDark.value ? 'light' : 'dark';
    }

    function set(dark: boolean): void {
        mode.value = dark ? 'dark' : 'light';
    }

    return { mode, isDark, toggle, set };
});
