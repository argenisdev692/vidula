<script setup lang="ts">
/**
 * Activity Log feed — infinite vertical marquee of recent workspace actions.
 * Items are rendered twice so the CSS scroll loops seamlessly; hovering pauses.
 * Data comes from {@see DashboardController} (real activity_log or demo fillers).
 */
import type { DashboardActivity } from '@/modules/dashboard/types';

const props = defineProps<{
    activities: DashboardActivity[];
}>();
</script>

<template>
    <div class="recent-activity-card">
        <div class="activity-header">
            <div class="activity-header-left">
                <i class="pi pi-history activity-icon" aria-hidden="true" />
                <div>
                    <h3 class="activity-title">Activity Log</h3>
                    <p class="activity-subtitle">Recent workspace events</p>
                </div>
            </div>
            <span class="activity-live-dot" aria-hidden="true" />
        </div>

        <div class="marquee-container">
            <div class="marquee-track">
                <div
                    v-for="(item, index) in [...props.activities, ...props.activities]"
                    :key="`${item.id}-${index}`"
                    class="activity-item"
                >
                    <div class="activity-avatar" :style="{ '--avatar-color': item.iconColor }">
                        <span class="activity-initials">{{ item.initials }}</span>
                    </div>
                    <div class="activity-body">
                        <p class="activity-text">
                            <span class="activity-user">{{ item.user }}</span>
                            <span class="activity-action"> {{ item.action }} </span>
                            <span class="activity-target">{{ item.target }}</span>
                        </p>
                        <span class="activity-time">{{ item.time }}</span>
                    </div>
                    <i
                        class="pi activity-item-icon"
                        :class="item.icon"
                        :style="{ color: item.iconColor }"
                        aria-hidden="true"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.recent-activity-card {
    background: color-mix(in srgb, var(--bg-surface) 60%, transparent);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-2xl);
    padding: var(--space-6);
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
    height: 420px;
    max-height: 420px;
    overflow: hidden;
}

.activity-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
}

.activity-header-left {
    display: flex;
    align-items: center;
    gap: var(--space-3);
}

.activity-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--accent-primary) 12%, transparent);
    color: var(--accent-primary);
    font-size: var(--text-base);
}

.activity-title {
    font-size: var(--text-lg);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
    margin: 0;
    line-height: 1.2;
}

.activity-subtitle {
    font-size: var(--text-xs);
    color: var(--text-muted);
    margin: var(--space-1) 0 0 0;
    line-height: 1.3;
}

.activity-live-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--accent-success);
    box-shadow: 0 0 8px color-mix(in srgb, var(--accent-success) 60%, transparent);
    animation: pulse-live 2s ease-in-out infinite;
}

@keyframes pulse-live {
    0%,
    100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.5;
        transform: scale(1.4);
    }
}

.marquee-container {
    flex: 1;
    overflow: hidden;
    position: relative;
    min-height: 0;
    -webkit-mask-image: linear-gradient(to bottom, transparent 0%, black 10%, black 90%, transparent 100%);
    mask-image: linear-gradient(to bottom, transparent 0%, black 10%, black 90%, transparent 100%);
}

.marquee-track {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    animation: marquee-scroll 25s linear infinite;
}

.marquee-track:hover {
    animation-play-state: paused;
}

@keyframes marquee-scroll {
    0% {
        transform: translateY(0);
    }
    100% {
        transform: translateY(-50%);
    }
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: var(--space-3);
    padding: var(--space-3) var(--space-4);
    background: color-mix(in srgb, var(--text-primary) 3%, transparent);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-lg);
    transition: background var(--transition), border-color var(--transition);
    flex-shrink: 0;
}

.activity-item:hover {
    background: color-mix(in srgb, var(--text-primary) 6%, transparent);
    border-color: var(--border-default);
}

.activity-avatar {
    --avatar-color: var(--accent-primary);
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: color-mix(in srgb, var(--avatar-color) 15%, transparent);
    color: var(--avatar-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: var(--font-bold);
    flex-shrink: 0;
    border: 1px solid color-mix(in srgb, var(--avatar-color) 25%, transparent);
}

.activity-body {
    flex: 1;
    min-width: 0;
}

.activity-text {
    font-size: var(--text-sm);
    line-height: 1.4;
    margin: 0;
}

.activity-user {
    color: var(--text-primary);
    font-weight: var(--font-semibold);
}

.activity-action {
    color: var(--text-secondary);
}

.activity-target {
    color: var(--accent-primary);
    font-weight: var(--font-medium);
}

.activity-time {
    display: block;
    font-size: 11px;
    color: var(--text-muted);
    margin-top: 2px;
}

.activity-item-icon {
    font-size: var(--text-sm);
    margin-top: 2px;
    flex-shrink: 0;
}

@media (prefers-reduced-motion: reduce) {
    .marquee-track,
    .activity-live-dot {
        animation: none;
    }
}
</style>
