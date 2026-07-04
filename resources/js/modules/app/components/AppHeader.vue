<script setup lang="ts">
/**
 * Per-page app-shell header (ported from the GUIDE `app-page-header`, simplified).
 * Band 1: menu toggle · logo · theme toggle · user avatar → profile.
 * Band 2: page title/subtitle · optional primary action.
 * Notifications feeds + global search are intentionally deferred until their
 * backend endpoints exist. Each page renders this at the top of its content.
 */
import { Link } from '@inertiajs/vue3';
import Button from '@/volt/Button.vue';
import { useAppShellStore } from '@/modules/app/stores/useAppShellStore';
import { useThemeStore } from '@/modules/app/stores/useThemeStore';
import { useCurrentUser } from '@/modules/auth/composables/useCurrentUser';

withDefaults(
    defineProps<{
        title: string;
        subtitle?: string;
        showAction?: boolean;
        actionLabel?: string;
        actionIcon?: string;
    }>(),
    { subtitle: '', showAction: false, actionLabel: 'New', actionIcon: 'pi pi-plus' },
);

const emit = defineEmits<{ action: [] }>();

const shell = useAppShellStore();
const theme = useThemeStore();
const { fullName, initials, profilePhotoUrl } = useCurrentUser();
</script>

<template>
    <header class="page-header">
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
                :src="theme.isDark ? '/img/Logo-white.png' : '/img/Logo.png'"
                alt="Logo"
                class="header-logo"
            />

            <div class="header-bar-right">
                <Button
                    type="button"
                    :icon="theme.isDark ? 'pi pi-moon' : 'pi pi-sun'"
                    text
                    rounded
                    severity="secondary"
                    :aria-label="theme.isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                    @click="theme.toggle()"
                />

                <Link href="/profile" class="header-user" aria-label="Go to profile" :title="fullName">
                    <img
                        v-if="profilePhotoUrl"
                        :src="profilePhotoUrl"
                        alt="Profile photo"
                        class="header-avatar-photo"
                    />
                    <span v-else class="header-avatar" aria-hidden="true">{{ initials }}</span>
                </Link>
            </div>
        </div>

        <div class="header-page">
            <div class="header-page-titles">
                <h1 class="header-title">{{ title }}</h1>
                <p v-if="subtitle" class="header-subtitle">{{ subtitle }}</p>
            </div>

            <Button
                v-if="showAction"
                type="button"
                :icon="actionIcon"
                :label="actionLabel"
                @click="emit('action')"
            />
        </div>
    </header>
</template>

<style scoped>
.page-header {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    margin-bottom: 2rem;
    padding: 1.25rem 2rem;
    background: color-mix(in srgb, var(--bg-surface) 60%, transparent);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-lg);
    position: relative;
    z-index: 10;
}

.header-bar {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.header-logo {
    height: 44px;
    width: auto;
    object-fit: contain;
    flex-shrink: 0;
}

.header-bar-right {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.header-user {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.header-avatar,
.header-avatar-photo {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 50%;
    flex-shrink: 0;
}

.header-avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--grad-primary);
    color: var(--on-accent);
    font-size: 0.8125rem;
    font-weight: 600;
}

.header-avatar-photo {
    object-fit: cover;
    border: 2px solid var(--border-default);
}

.header-page {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}

.header-page-titles {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.header-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.1;
    color: var(--text-primary);
}

.header-subtitle {
    margin: 0.25rem 0 0 0;
    font-size: 0.875rem;
    color: var(--text-secondary);
}

@media (max-width: 768px) {
    .page-header {
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        margin-bottom: 1.5rem;
    }

    .header-logo {
        height: 36px;
    }

    .header-title {
        font-size: 1.25rem;
    }
}
</style>
