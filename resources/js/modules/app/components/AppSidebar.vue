<script setup lang="ts">
/**
 * Authenticated app-shell sidebar — an overlay Drawer (Volt) toggled from the
 * header menu button and the floating menu button. Renders permission-gated nav
 * groups, the current user, and the theme toggle. Ported from the GUIDE Angular
 * `app-sidebar`, adapted to Inertia links + Pinia shell/theme stores.
 */
import { usePage, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useLocalStorage } from '@vueuse/core';
import Drawer from '@/volt/Drawer.vue';
import ToggleSwitch from '@/volt/ToggleSwitch.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import { useNavGroups, type NavItem } from '@/modules/app/composables/useNavGroups';
import { useAppShellStore } from '@/modules/app/stores/useAppShellStore';
import { useThemeStore } from '@/modules/app/stores/useThemeStore';
import { useCurrentUser } from '@/modules/auth/composables/useCurrentUser';
import { useAuthorization } from '@/modules/auth/composables/useAuthorization';

withDefaults(defineProps<{ companyName?: string }>(), { companyName: 'Vidula' });

const shell = useAppShellStore();
const theme = useThemeStore();
const { navItems } = useNavGroups();
const { user, fullName, initials, profilePhotoUrl } = useCurrentUser();
const { primaryRole } = useAuthorization();

const page = usePage();
const currentUrl = computed<string>(() => page.url);

function isActive(href?: string): boolean {
    if (!href) {
        return false;
    }
    return currentUrl.value === href || currentUrl.value.startsWith(`${href}/`);
}

function groupHasActive(item: NavItem): boolean {
    return item.children?.some((child) => isActive(child.href)) ?? false;
}

/** Persisted set of manually-expanded group labels (union with active group). */
const expandedGroups = useLocalStorage<string[]>('vidula-sidebar-groups', []);

function isGroupOpen(item: NavItem): boolean {
    return expandedGroups.value.includes(item.label) || groupHasActive(item);
}

function toggleGroup(label: string): void {
    expandedGroups.value = expandedGroups.value.includes(label)
        ? expandedGroups.value.filter((l) => l !== label)
        : [...expandedGroups.value, label];
}
</script>

<template>
    <Drawer v-model:visible="shell.sidebarOpen" position="left" :modal="true" :dismissable="true">
        <template #header>
            <div class="sidebar-logo">
                <img src="/img/Mark.png" alt="" aria-hidden="true" class="sidebar-logo-img" />
                <span class="sidebar-logo-name">{{ companyName }}</span>
            </div>
        </template>

        <div class="sidebar-body">
            <nav class="sidebar-nav" aria-label="Main navigation">
                <template v-for="item in navItems" :key="item.label">
                    <PermissionGuard :permission="item.permission">
                        <!-- Group with children -->
                        <div v-if="item.children?.length" class="menu-group">
                            <button
                                type="button"
                                class="menu-item"
                                :class="{ active: groupHasActive(item) }"
                                :aria-expanded="isGroupOpen(item)"
                                :aria-label="item.label"
                                @click="toggleGroup(item.label)"
                            >
                                <i class="menu-icon" :class="item.icon" aria-hidden="true" />
                                <span class="menu-label">{{ item.label }}</span>
                                <i
                                    class="menu-chevron pi pi-chevron-down"
                                    :class="{ rotated: isGroupOpen(item) }"
                                    aria-hidden="true"
                                />
                            </button>

                            <Transition name="submenu">
                                <div v-if="isGroupOpen(item)" class="menu-submenu">
                                    <template
                                        v-for="child in item.children"
                                        :key="child.label"
                                    >
                                        <PermissionGuard :permission="child.permission">
                                            <Link
                                                :href="child.href ?? '#'"
                                                prefetch
                                                class="menu-item menu-subitem"
                                                :class="{ active: isActive(child.href) }"
                                                :aria-label="child.label"
                                                @click="shell.closeSidebar()"
                                            >
                                                <i
                                                    class="menu-icon"
                                                    :class="child.icon"
                                                    aria-hidden="true"
                                                />
                                                <span class="menu-label">{{ child.label }}</span>
                                                <span
                                                    v-if="isActive(child.href)"
                                                    class="menu-indicator"
                                                    aria-hidden="true"
                                                />
                                            </Link>
                                        </PermissionGuard>
                                    </template>
                                </div>
                            </Transition>
                        </div>

                        <!-- Leaf item -->
                        <Link
                            v-else
                            :href="item.href ?? '#'"
                            prefetch
                            class="menu-item"
                            :class="{ active: isActive(item.href) }"
                            :aria-label="item.label"
                            @click="shell.closeSidebar()"
                        >
                            <i class="menu-icon" :class="item.icon" aria-hidden="true" />
                            <span class="menu-label">{{ item.label }}</span>
                            <span
                                v-if="isActive(item.href)"
                                class="menu-indicator"
                                aria-hidden="true"
                            />
                        </Link>
                    </PermissionGuard>
                </template>
            </nav>

            <footer class="sidebar-footer">
                <Link
                    href="/profile"
                    class="user-profile"
                    aria-label="Go to profile"
                    @click="shell.closeSidebar()"
                >
                    <img
                        v-if="profilePhotoUrl"
                        :src="profilePhotoUrl"
                        alt="Profile photo"
                        class="user-avatar-photo"
                    />
                    <div v-else class="user-avatar" aria-hidden="true">
                        <span>{{ initials }}</span>
                    </div>
                    <div class="user-info">
                        <p class="user-name">{{ fullName || user?.email }}</p>
                        <p class="user-role">{{ primaryRole }}</p>
                    </div>
                </Link>

                <div class="theme-toggle">
                    <div class="theme-toggle-label">
                        <i class="pi" :class="theme.isDark ? 'pi-moon' : 'pi-sun'" aria-hidden="true" />
                        <span>{{ theme.isDark ? 'Dark' : 'Light' }} mode</span>
                    </div>
                    <ToggleSwitch
                        :model-value="theme.isDark"
                        aria-label="Toggle dark and light theme"
                        @update:model-value="(v: boolean) => theme.set(v)"
                    />
                </div>
            </footer>
        </div>
    </Drawer>
</template>

<style scoped>
.sidebar-logo {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    text-align: center;
    width: 100%;
}

.sidebar-logo-img {
    width: 44px;
    height: 44px;
    object-fit: contain;
}

.sidebar-logo-name {
    font-size: 1.125rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--text-primary);
}

.sidebar-body {
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 0;
}

.sidebar-nav {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid transparent;
    border-radius: var(--radius-lg);
    background: transparent;
    color: var(--text-secondary);
    font-size: 0.875rem;
    font-weight: 500;
    text-align: left;
    cursor: pointer;
    position: relative;
    transition: background var(--transition), color var(--transition), border-color var(--transition);
}

.menu-item:hover {
    background: var(--bg-hover);
    color: var(--text-primary);
}

.menu-item.active {
    background: color-mix(in srgb, var(--accent-primary) 18%, transparent);
    color: var(--text-primary);
    border-color: color-mix(in srgb, var(--accent-primary) 30%, transparent);
}

.menu-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.25rem;
    height: 1.25rem;
    font-size: 1rem;
    color: currentColor;
}

.menu-label {
    flex: 1;
}

.menu-chevron {
    font-size: 0.75rem;
    color: var(--text-muted);
    transition: transform var(--transition);
}

.menu-chevron.rotated {
    transform: rotate(180deg);
}

.menu-indicator {
    position: absolute;
    right: 0.5rem;
    width: 4px;
    height: 1.5rem;
    background: var(--grad-primary);
    border-radius: 4px;
}

.menu-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.menu-submenu {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    margin-left: 1rem;
    padding-left: 0.75rem;
    border-left: 1px solid var(--border-default);
    overflow: hidden;
}

.menu-subitem {
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
}

.submenu-enter-active,
.submenu-leave-active {
    transition: opacity var(--transition), transform var(--transition);
}

.submenu-enter-from,
.submenu-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}

.sidebar-footer {
    padding: 1rem;
    border-top: 1px solid var(--border-default);
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    padding: 0.5rem;
    border-radius: var(--radius-lg);
    transition: background var(--transition);
}

.user-profile:hover {
    background: var(--bg-hover);
}

.user-avatar,
.user-avatar-photo {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: var(--radius-lg);
    flex-shrink: 0;
}

.user-avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--grad-primary);
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--on-accent);
}

.user-avatar-photo {
    object-fit: cover;
    border: 2px solid var(--border-default);
}

.user-info {
    flex: 1;
    min-width: 0;
}

.user-name {
    margin: 0;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-primary);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.user-role {
    margin: 0;
    font-size: 0.75rem;
    color: var(--text-muted);
}

.theme-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--border-default);
}

.theme-toggle-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.theme-toggle-label i {
    font-size: 1rem;
    color: var(--accent-primary);
}
</style>
