<script setup lang="ts">
/**
 * Per-page app-shell header — a faithful port of the GUIDE Angular
 * `app-page-header`. Two bands inside an animated conic-gradient border:
 *   Band 1: menu toggle · logo · expandable search · notification bells · user menu
 *   Band 2: page title/subtitle · optional animated primary action
 * Each page renders this at the top of its content and passes its own title.
 */
import { Link, router } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import { onClickOutside } from '@vueuse/core';
import { useQueryCache } from '@pinia/colada';
import Button from '@/volt/Button.vue';
import AnimatedButton from '@/modules/app/components/AnimatedButton.vue';
import { useAppShellStore } from '@/modules/app/stores/useAppShellStore';
import { useThemeStore } from '@/modules/app/stores/useThemeStore';
import { useCurrentUser } from '@/modules/auth/composables/useCurrentUser';
import { useAuthorization } from '@/modules/auth/composables/useAuthorization';
import { useHeaderNotifications } from '@/modules/app/composables/useHeaderNotifications';
import { useCompany } from '@/modules/app/composables/useCompany';

withDefaults(
    defineProps<{
        title: string;
        subtitle?: string;
        showAction?: boolean;
        actionLabel?: string;
        actionIcon?: string;
    }>(),
    { subtitle: '', showAction: false, actionLabel: 'New Product', actionIcon: 'pi-plus' },
);

const emit = defineEmits<{ action: [] }>();

const shell = useAppShellStore();
const theme = useThemeStore();
const queryCache = useQueryCache();
const { fullName, initials, profilePhotoUrl } = useCurrentUser();
const { primaryRole } = useAuthorization();
const { feeds } = useHeaderNotifications();
const company = useCompany();

/* ── Notification dropdowns ── */
const openFeedKey = ref<string | null>(null);

function toggleDropdown(key: string): void {
    userMenuOpen.value = false;
    openFeedKey.value = openFeedKey.value === key ? null : key;
}

/* ── Expandable search ── */
const searchOpen = ref<boolean>(false);
const searchQuery = ref<string>('');
const searchInput = ref<HTMLInputElement | null>(null);

function openSearch(): void {
    openFeedKey.value = null;
    searchOpen.value = true;
    void nextTick(() => searchInput.value?.focus());
}

function closeSearch(): void {
    searchOpen.value = false;
    searchQuery.value = '';
}

function onSearchKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        closeSearch();
    }
}

/* ── User menu ── */
const userMenuOpen = ref<boolean>(false);
const userMenuRef = ref<HTMLElement | null>(null);

function toggleUserMenu(): void {
    openFeedKey.value = null;
    userMenuOpen.value = !userMenuOpen.value;
}

function logout(): void {
    userMenuOpen.value = false;
    router.post(
        '/logout',
        {},
        {
            // Invalidate all cached server state so nothing leaks before the
            // full-reload redirect to the login screen tears down the SPA.
            onFinish: () => {
                void queryCache.invalidateQueries();
            },
        },
    );
}

/* Close every open dropdown on an outside click. */
const headerRef = ref<HTMLElement | null>(null);
onClickOutside(headerRef, () => {
    openFeedKey.value = null;
    userMenuOpen.value = false;
});
onClickOutside(userMenuRef, () => {
    userMenuOpen.value = false;
});
</script>

<template>
    <header ref="headerRef" class="page-header">
        <!-- ══ BAND 1: global top bar ══ -->
        <div class="header-bar">
            <Button
                type="button"
                icon="pi pi-bars"
                text
                rounded
                severity="secondary"
                aria-label="Open menu"
                @click="shell.openSidebar()"
            />

            <img
                :src="theme.isDark ? company.logo_white_url : company.logo_url"
                :alt="company.name"
                class="header-logo"
            />

            <div class="header-bar-right">
                <!-- Expandable search -->
                <div class="search-wrapper" :class="{ open: searchOpen }">
                    <button
                        v-if="!searchOpen"
                        type="button"
                        class="notif-btn"
                        aria-label="Search"
                        @click="openSearch"
                    >
                        <i class="pi pi-search notif-icon" aria-hidden="true" />
                    </button>
                    <div v-else class="search-input-wrap" @click.stop>
                        <i class="pi pi-search search-input-icon" aria-hidden="true" />
                        <input
                            ref="searchInput"
                            v-model="searchQuery"
                            type="text"
                            class="search-input"
                            placeholder="Search products, contacts..."
                            aria-label="Search input"
                            @keydown="onSearchKeydown"
                        />
                        <button
                            type="button"
                            class="search-close"
                            aria-label="Close search"
                            @click="closeSearch"
                        >
                            <i class="pi pi-times" aria-hidden="true" />
                        </button>
                    </div>
                </div>

                <!-- Notification bells -->
                <div class="header-notifications">
                    <div v-for="feed in feeds" :key="feed.key" class="notif-wrapper">
                        <button
                            type="button"
                            class="notif-btn"
                            :aria-label="feed.label"
                            @click.stop="toggleDropdown(feed.key)"
                        >
                            <i class="notif-icon" :class="feed.icon" aria-hidden="true" />
                            <span v-if="feed.unreadCount > 0" class="notif-badge">
                                {{ feed.unreadCount }}
                            </span>
                        </button>

                        <Transition name="dropdown">
                            <div
                                v-if="openFeedKey === feed.key"
                                class="notif-dropdown"
                                role="menu"
                                @click.stop
                            >
                                <div class="notif-header">
                                    <span>{{ feed.label }}</span>
                                    <span class="notif-count">{{ feed.unreadCount }} new</span>
                                </div>
                                <div class="notif-list">
                                    <template v-if="feed.items.length">
                                        <Link
                                            v-for="item in feed.items"
                                            :key="item.id"
                                            :href="feed.route"
                                            class="notif-item"
                                            :class="{ unread: item.unread }"
                                            role="menuitem"
                                            @click="openFeedKey = null"
                                        >
                                            <span
                                                class="notif-dot"
                                                :class="{ unread: item.unread }"
                                                aria-hidden="true"
                                            />
                                            <span class="notif-content">
                                                <span class="notif-title">{{ item.title }}</span>
                                                <span class="notif-msg">{{ item.message }}</span>
                                                <span class="notif-time">{{ item.time }}</span>
                                            </span>
                                        </Link>
                                    </template>
                                    <p v-else class="notif-empty">You're all caught up</p>
                                </div>
                                <Link
                                    :href="feed.route"
                                    class="notif-footer"
                                    @click="openFeedKey = null"
                                >
                                    View all {{ feed.label }}
                                </Link>
                            </div>
                        </Transition>
                    </div>
                </div>

                <!-- User menu -->
                <div ref="userMenuRef" class="user-menu-wrapper">
                    <button
                        type="button"
                        class="user-menu-trigger"
                        :class="{ active: userMenuOpen }"
                        aria-label="User menu"
                        aria-haspopup="menu"
                        :aria-expanded="userMenuOpen"
                        @click.stop="toggleUserMenu"
                    >
                        <img
                            v-if="profilePhotoUrl"
                            :src="profilePhotoUrl"
                            alt="Profile"
                            class="user-menu-photo"
                        />
                        <span v-else class="user-menu-avatar" aria-hidden="true">{{ initials }}</span>
                        <span class="user-menu-info">
                            <span class="user-menu-name">{{ fullName }}</span>
                            <span class="user-menu-role">{{ primaryRole }}</span>
                        </span>
                        <i
                            class="pi pi-chevron-down user-menu-chevron"
                            :class="{ open: userMenuOpen }"
                            aria-hidden="true"
                        />
                    </button>

                    <Transition name="dropdown">
                        <div v-if="userMenuOpen" class="user-dropdown" role="menu" @click.stop>
                            <div class="user-dropdown__identity">
                                <img
                                    v-if="profilePhotoUrl"
                                    :src="profilePhotoUrl"
                                    alt="Profile"
                                    class="user-dropdown__photo"
                                />
                                <span v-else class="user-dropdown__avatar" aria-hidden="true">{{ initials }}</span>
                                <span class="user-dropdown__identity-info">
                                    <span class="user-dropdown__name">{{ fullName }}</span>
                                    <span class="user-dropdown__role">{{ primaryRole }}</span>
                                </span>
                            </div>
                            <div class="user-dropdown__separator" />
                            <Link
                                href="/profile"
                                class="user-dropdown__item"
                                role="menuitem"
                                @click="userMenuOpen = false"
                            >
                                <i class="pi pi-user" aria-hidden="true" />
                                <span>My Profile</span>
                            </Link>
                            <Link
                                href="/profile#security"
                                class="user-dropdown__item"
                                role="menuitem"
                                @click="userMenuOpen = false"
                            >
                                <i class="pi pi-shield" aria-hidden="true" />
                                <span>Security &amp; sessions</span>
                            </Link>
                            <div class="user-dropdown__separator" />
                            <button
                                type="button"
                                class="user-dropdown__item user-dropdown__item--danger"
                                role="menuitem"
                                @click="logout"
                            >
                                <i class="pi pi-sign-out" aria-hidden="true" />
                                <span>Logout</span>
                            </button>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>

        <!-- ══ BAND 2: page context ══ -->
        <div class="header-page">
            <div class="header-page-titles">
                <h1 class="header-title">{{ title }}</h1>
                <p v-if="subtitle" class="header-subtitle">{{ subtitle }}</p>
            </div>

            <AnimatedButton
                v-if="showAction"
                :text="actionLabel"
                variant="primary"
                :icon="actionIcon"
                @click="emit('action')"
            />
        </div>
    </header>
</template>

<style scoped>
@property --border-angle {
    syntax: '<angle>';
    initial-value: 0deg;
    inherits: false;
}

.page-header {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
    margin-bottom: var(--space-8);
    padding: var(--space-5) var(--space-8);
    background: color-mix(in srgb, var(--bg-surface) 60%, transparent);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: var(--radius-2xl);
    position: relative;
    z-index: 10;
}

/* Animated conic-gradient ring drawn on a masked ::before. */
.page-header::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    padding: 1.5px;
    background: conic-gradient(
        from var(--border-angle),
        transparent 0deg,
        transparent 315deg,
        var(--accent-primary) 348deg,
        var(--cyan-light) 360deg
    );
    -webkit-mask:
        linear-gradient(#000 0 0) content-box,
        linear-gradient(#000 0 0);
    mask:
        linear-gradient(#000 0 0) content-box,
        linear-gradient(#000 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    pointer-events: none;
    animation: border-rotate 3s linear infinite;
}

@keyframes border-rotate {
    to {
        --border-angle: 360deg;
    }
}

.header-bar {
    display: flex;
    align-items: center;
    gap: var(--space-4);
}

.header-logo {
    height: 44px;
    width: auto;
    object-fit: contain;
    flex-shrink: 0;
    animation: logo-glow 4s ease-in-out infinite;
    will-change: filter;
}

@keyframes logo-glow {
    0%,
    100% {
        filter: drop-shadow(0 4px 10px color-mix(in srgb, var(--accent-primary) 35%, transparent));
    }
    50% {
        filter: drop-shadow(0 8px 18px color-mix(in srgb, var(--accent-secondary) 50%, transparent));
    }
}

.header-bar-right {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: var(--space-3);
}

/* ── Band 2 ── */
.header-page {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: var(--space-4);
    flex-wrap: wrap;
}

.header-page-titles {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.header-title {
    font-size: var(--text-2xl);
    font-weight: var(--font-bold);
    color: var(--text-primary);
    margin: 0;
    line-height: 1.1;
}

.header-subtitle {
    font-size: var(--text-sm);
    color: var(--text-secondary);
    margin: var(--space-1) 0 0 0;
}

/* ── Notification buttons + dropdowns ── */
.header-notifications {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.notif-wrapper,
.user-menu-wrapper {
    position: relative;
}

.notif-btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: var(--radius-md);
    border: 1px solid var(--border-default);
    background: color-mix(in srgb, var(--text-primary) 5%, transparent);
    color: var(--text-secondary);
    cursor: pointer;
    transition: background var(--transition-slow), border-color var(--transition-slow),
        color var(--transition-slow);
}

.notif-btn:hover {
    background: color-mix(in srgb, var(--text-primary) 10%, transparent);
    border-color: var(--border-strong);
    color: var(--text-primary);
}

.notif-icon {
    font-size: var(--text-lg);
}

.notif-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    border-radius: 9px;
    background: var(--accent-error);
    color: var(--on-accent);
    font-size: 10px;
    font-weight: var(--font-bold);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px color-mix(in srgb, var(--accent-error) 40%, transparent);
}

.notif-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 320px;
    background: color-mix(in srgb, var(--bg-surface) 98%, transparent);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-xl);
    box-shadow:
        0 16px 48px color-mix(in srgb, var(--bg-void) 50%, transparent),
        0 0 0 1px color-mix(in srgb, var(--accent-primary) 8%, transparent);
    overflow: hidden;
    z-index: 1000;
}

.notif-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-4) var(--space-5);
    border-bottom: 1px solid var(--border-default);
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
}

.notif-count {
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
    color: var(--accent-primary);
    background: color-mix(in srgb, var(--accent-primary) 12%, transparent);
    padding: 2px 8px;
    border-radius: var(--radius-md);
}

.notif-list {
    max-height: 280px;
    overflow-y: auto;
}

.notif-item {
    display: flex;
    align-items: flex-start;
    gap: var(--space-3);
    width: 100%;
    padding: var(--space-4) var(--space-5);
    border-bottom: 1px solid var(--border-subtle);
    text-align: left;
    transition: background var(--transition);
}

.notif-item:hover {
    background: color-mix(in srgb, var(--text-primary) 3%, transparent);
}

.notif-item.unread {
    background: color-mix(in srgb, var(--accent-primary) 4%, transparent);
}

.notif-empty {
    margin: 0;
    padding: var(--space-6) var(--space-5);
    text-align: center;
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.notif-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--border-default);
    margin-top: 6px;
    flex-shrink: 0;
}

.notif-dot.unread {
    background: var(--accent-primary);
    box-shadow: 0 0 8px color-mix(in srgb, var(--accent-primary) 50%, transparent);
}

.notif-content {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.notif-title {
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
    margin: 0 0 2px 0;
    line-height: 1.3;
}

.notif-msg {
    font-size: var(--text-xs);
    color: var(--text-secondary);
    margin: 0 0 var(--space-1) 0;
    line-height: 1.4;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.notif-time {
    font-size: 11px;
    color: var(--text-muted);
}

.notif-footer {
    display: block;
    width: 100%;
    padding: var(--space-3) var(--space-5);
    border-top: 1px solid var(--border-default);
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    color: var(--accent-primary);
    cursor: pointer;
    transition: background var(--transition);
    text-align: center;
}

.notif-footer:hover {
    background: color-mix(in srgb, var(--accent-primary) 8%, transparent);
}

/* ── Expandable search ── */
.search-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.search-input-wrap {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    background: color-mix(in srgb, var(--text-primary) 5%, transparent);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    padding: 0 var(--space-3);
    height: 40px;
    width: 260px;
    animation: search-expand 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes search-expand {
    from {
        opacity: 0;
        width: 40px;
    }
    to {
        opacity: 1;
        width: 260px;
    }
}

.search-input-icon {
    color: var(--text-muted);
    font-size: var(--text-sm);
    flex-shrink: 0;
}

.search-input {
    flex: 1;
    background: transparent;
    border: none;
    outline: none;
    color: var(--text-primary);
    font-family: var(--font-sans);
    font-size: var(--text-sm);
    min-width: 0;
    height: 100%;
}

.search-input::placeholder {
    color: var(--text-muted);
}

.search-close {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: var(--radius-sm);
    border: none;
    background: transparent;
    color: var(--text-muted);
    cursor: pointer;
    font-size: var(--text-sm);
    transition: color var(--transition);
    flex-shrink: 0;
}

.search-close:hover {
    color: var(--text-primary);
}

/* ── User menu ── */
.user-menu-trigger {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-1) var(--space-2);
    border-radius: var(--radius-lg);
    border: 1px solid transparent;
    background: transparent;
    cursor: pointer;
    transition: background var(--transition), border-color var(--transition);
}

.user-menu-trigger:hover,
.user-menu-trigger.active {
    background: color-mix(in srgb, var(--text-primary) 5%, transparent);
    border-color: var(--border-default);
}

.user-menu-avatar,
.user-menu-photo {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    flex-shrink: 0;
}

.user-menu-avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--grad-primary);
    color: var(--on-accent);
    font-size: 12px;
    font-weight: var(--font-bold);
}

.user-menu-photo {
    object-fit: cover;
    border: 2px solid var(--border-default);
}

.user-menu-info {
    display: none;
    flex-direction: column;
    align-items: flex-start;
    min-width: 0;
}

.user-menu-name {
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
    line-height: 1.2;
    max-width: 140px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.user-menu-role {
    font-size: var(--text-xs);
    color: var(--text-muted);
    line-height: 1.2;
}

.user-menu-chevron {
    font-size: var(--text-xs);
    color: var(--text-muted);
    transition: transform var(--transition);
}

.user-menu-chevron.open {
    transform: rotate(180deg);
}

.user-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 240px;
    padding: var(--space-2);
    background: color-mix(in srgb, var(--bg-surface) 98%, transparent);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-xl);
    box-shadow:
        0 16px 48px color-mix(in srgb, var(--bg-void) 50%, transparent),
        0 0 0 1px color-mix(in srgb, var(--accent-primary) 8%, transparent);
    z-index: 1000;
}

.user-dropdown__identity {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-3) var(--space-4);
}

.user-dropdown__photo,
.user-dropdown__avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    flex-shrink: 0;
}

.user-dropdown__photo {
    object-fit: cover;
    border: 2px solid var(--border-default);
}

.user-dropdown__avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--grad-primary);
    color: var(--on-accent);
    font-size: var(--text-sm);
    font-weight: var(--font-bold);
}

.user-dropdown__identity-info {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.user-dropdown__name {
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
    line-height: 1.2;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.user-dropdown__role {
    font-size: var(--text-xs);
    color: var(--text-muted);
    line-height: 1.2;
}

.user-dropdown__item {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    width: 100%;
    padding: var(--space-3) var(--space-4);
    border: none;
    background: transparent;
    border-radius: var(--radius-md);
    color: var(--text-secondary);
    font-family: var(--font-sans);
    font-size: var(--text-sm);
    text-align: left;
    cursor: pointer;
    transition: background var(--transition), color var(--transition);
}

.user-dropdown__item:hover {
    background: color-mix(in srgb, var(--text-primary) 6%, transparent);
    color: var(--text-primary);
}

.user-dropdown__item--danger {
    color: var(--accent-error);
}

.user-dropdown__item--danger:hover {
    background: color-mix(in srgb, var(--accent-error) 10%, transparent);
    color: var(--accent-error);
}

.user-dropdown__separator {
    height: 1px;
    margin: var(--space-1) 0;
    background: var(--border-subtle);
}

/* ── Dropdown enter/leave transition ── */
.dropdown-enter-active,
.dropdown-leave-active {
    transition: opacity var(--transition), transform var(--transition);
}

.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-6px) scale(0.98);
}

/* ── Reduced motion ── */
@media (prefers-reduced-motion: reduce) {
    .page-header::before,
    .header-logo,
    .search-input-wrap {
        animation: none;
    }
}

/* ── Responsive ── */
@media (min-width: 640px) {
    .user-menu-info {
        display: flex;
    }
}

@media (max-width: 768px) {
    .page-header {
        gap: var(--space-3);
        padding: var(--space-3) var(--space-4);
        margin-bottom: var(--space-6);
    }

    .header-bar {
        flex-wrap: wrap;
        gap: var(--space-2);
    }

    .header-logo {
        height: 36px;
    }

    .header-title {
        font-size: var(--text-xl);
    }

    .search-wrapper.open {
        order: 5;
        flex: 1 1 100%;
    }

    .search-input-wrap {
        width: 100%;
        animation: none;
    }

    .notif-dropdown {
        position: fixed;
        top: auto;
        right: var(--space-4);
        left: var(--space-4);
        width: auto;
        max-width: calc(100vw - 2 * var(--space-4));
        margin-top: var(--space-2);
    }
}
</style>
