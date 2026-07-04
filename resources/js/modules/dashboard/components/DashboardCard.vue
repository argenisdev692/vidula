<script setup lang="ts">
/**
 * Single stat tile for the dashboard grid — icon chip, trend pill and value.
 * Ported 1:1 from the GUIDE Angular `app-dashboard-card`. Purely presentational;
 * `color` selects the corner wash + icon tint.
 */
withDefaults(
    defineProps<{
        title: string;
        value: string;
        subtitle?: string;
        icon?: string;
        trend?: 'up' | 'down' | 'neutral';
        trendValue?: string;
        color?: 'purple' | 'blue' | 'green' | 'orange' | 'pink';
    }>(),
    { subtitle: '', icon: '', trend: 'neutral', trendValue: '', color: 'purple' },
);
</script>

<template>
    <div class="dashboard-card" :class="color">
        <div class="card-header">
            <div v-if="icon" class="card-icon">
                <i class="pi" :class="icon" aria-hidden="true" />
            </div>
            <div class="card-trend" :class="trend">
                <template v-if="trend !== 'neutral'">
                    <svg
                        class="trend-icon"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        aria-hidden="true"
                    >
                        <path v-if="trend === 'up'" d="M18 15l-6-6-6 6" />
                        <path v-else d="M6 9l6 6 6-6" />
                    </svg>
                    <span>{{ trendValue }}</span>
                </template>
            </div>
        </div>
        <div class="card-content">
            <h3 class="card-title">{{ title }}</h3>
            <p class="card-value">{{ value }}</p>
            <p v-if="subtitle" class="card-subtitle">{{ subtitle }}</p>
        </div>
    </div>
</template>

<style scoped>
.dashboard-card {
    position: relative;
    overflow: hidden;
    isolation: isolate;
    background: color-mix(in srgb, var(--bg-surface) 55%, transparent);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-2xl);
    padding: var(--space-6);
    transition:
        transform 0.4s cubic-bezier(0.16, 1, 0.3, 1),
        box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1),
        border-color 0.4s ease;
}

.dashboard-card::before {
    content: '';
    position: absolute;
    inset: 0;
    z-index: -1;
    background: radial-gradient(
        120% 120% at 100% 0%,
        color-mix(in srgb, var(--card-color) 18%, transparent),
        transparent 55%
    );
    opacity: 0.5;
    transition: opacity 0.4s ease;
}

.dashboard-card::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(
        90deg,
        transparent,
        color-mix(in srgb, var(--card-color) 75%, transparent),
        transparent
    );
    opacity: 0;
    transition: opacity 0.4s ease;
}

.dashboard-card:hover {
    transform: translateY(-6px);
    border-color: color-mix(in srgb, var(--card-color) 40%, var(--border-strong));
    box-shadow:
        0 18px 40px -12px color-mix(in srgb, var(--bg-void) 60%, transparent),
        0 8px 30px -8px color-mix(in srgb, var(--card-color) 28%, transparent);
}

.dashboard-card:hover::before {
    opacity: 1;
}

.dashboard-card:hover::after {
    opacity: 1;
}

.dashboard-card.blue {
    --card-color: var(--blue-500);
}
.dashboard-card.purple {
    --card-color: #818cf8;
}
.dashboard-card.green {
    --card-color: #2dd4bf;
}
.dashboard-card.orange {
    --card-color: var(--accent-warning);
}
.dashboard-card.pink {
    --card-color: var(--accent-error);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--space-4);
    gap: var(--space-4);
}

.card-icon {
    width: var(--space-12);
    height: var(--space-12);
    border-radius: var(--radius-xl);
    display: flex;
    align-items: center;
    justify-content: center;
    background: color-mix(in srgb, var(--card-color) 15%, transparent);
    border: 1px solid color-mix(in srgb, var(--card-color) 28%, transparent);
    color: var(--card-color);
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.dashboard-card:hover .card-icon {
    transform: scale(1.06) rotate(-4deg);
    background: color-mix(in srgb, var(--card-color) 22%, transparent);
    box-shadow: 0 0 20px color-mix(in srgb, var(--card-color) 35%, transparent);
}

.card-icon i {
    font-size: var(--space-6);
    line-height: 1;
}

.card-trend {
    display: flex;
    align-items: center;
    gap: var(--space-1);
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    padding: var(--space-1) var(--space-3);
    border-radius: var(--space-2);
    background: color-mix(in srgb, var(--text-primary) 5%, transparent);
    min-height: 24px;
}

.card-trend.up {
    color: var(--accent-success);
    background: color-mix(in srgb, var(--accent-success) 10%, transparent);
}

.card-trend.down {
    color: var(--accent-error);
    background: color-mix(in srgb, var(--accent-error) 10%, transparent);
}

.card-trend.neutral {
    background: transparent;
}

.trend-icon {
    width: var(--space-4);
    height: var(--space-4);
}

.card-content {
    position: relative;
    z-index: 1;
}

.card-title {
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--text-secondary);
    margin: 0 0 var(--space-2) 0;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.card-value {
    font-size: var(--text-3xl);
    font-weight: var(--font-bold);
    color: var(--text-primary);
    margin: 0 0 var(--space-1) 0;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}

.card-subtitle {
    font-size: var(--text-xs);
    color: var(--text-muted);
    margin: 0;
}
</style>
