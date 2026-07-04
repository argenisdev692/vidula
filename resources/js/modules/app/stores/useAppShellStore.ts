import { defineStore } from 'pinia';
import { ref } from 'vue';

/**
 * Client-only UI state for the authenticated app shell.
 * The sidebar renders as an overlay Drawer toggled from the header menu button
 * and the scroll-reveal floating menu button.
 */
export const useAppShellStore = defineStore('app-shell', () => {
    const sidebarOpen = ref<boolean>(false);

    function openSidebar(): void {
        sidebarOpen.value = true;
    }

    function closeSidebar(): void {
        sidebarOpen.value = false;
    }

    function toggleSidebar(): void {
        sidebarOpen.value = !sidebarOpen.value;
    }

    return { sidebarOpen, openSidebar, closeSidebar, toggleSidebar };
});
