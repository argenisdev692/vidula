<script setup lang="ts">
/**
 * "New Claims" monthly bar chart, ported from the GUIDE Angular `app-claims-chart`.
 * Renders through the Volt `Chart` (chart.js). Bar fills and axis colours are read
 * from the live CSS design tokens and rebuilt whenever the theme flips, so the
 * chart always matches light/dark. Data is static placeholder for now.
 *
 * TODO(backend): swap `chartData` for a Pinia Colada query once the metrics
 * endpoint exists.
 */
import { computed, onMounted, ref, watch } from 'vue';
import Chart from '@/volt/Chart.vue';
import { useThemeStore } from '@/modules/app/stores/useThemeStore';

const theme = useThemeStore();

const currentYear = new Date().getFullYear();

const series = [42, 38, 55, 48, 62, 71, 58, 49, 67, 74, 61, 53];
const totalClaims = computed<number>(() => series.reduce((a, b) => a + b, 0));

const chartData = {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    datasets: [
        {
            label: 'New Claims',
            data: series,
            borderRadius: 8,
            borderSkipped: false,
            barThickness: 18,
            maxBarThickness: 24,
        },
    ],
};

const chartOptions = ref<Record<string, unknown>>({});

function token(name: string, fallback: string): string {
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    return value || fallback;
}

function buildOptions(): void {
    const textPrimary = token('--text-primary', '#e8e8ed');
    const textSecondary = token('--text-secondary', '#b0b0c0');
    const textMuted = token('--text-muted', '#7a7a90');
    const borderDefault = token('--border-default', 'rgba(255,255,255,0.10)');
    const accentPrimary = token('--accent-primary', '#6366f1');
    const accentSecondary = token('--accent-secondary', '#a78bfa');
    const accentLight = token('--purple-light', '#a78bfa');
    const fontFamily = token('--font-sans', "'JetBrains Mono', ui-monospace, monospace");

    chartOptions.value = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(6, 13, 24, 0.92)',
                titleColor: textPrimary,
                bodyColor: textSecondary,
                borderColor: 'rgba(99, 102, 241, 0.25)',
                borderWidth: 1,
                cornerRadius: 8,
                padding: 12,
                displayColors: false,
                callbacks: {
                    label: (ctx: { raw: number }) => `${ctx.raw} claims`,
                },
            },
        },
        scales: {
            x: {
                border: { display: false },
                grid: { display: false },
                ticks: {
                    color: textMuted,
                    font: { family: fontFamily, size: 11, weight: '500' },
                },
            },
            y: {
                border: { display: false },
                grid: { color: borderDefault, drawBorder: false },
                ticks: {
                    color: textMuted,
                    font: { family: fontFamily, size: 11, weight: '500' },
                    padding: 8,
                    stepSize: 20,
                },
                beginAtZero: true,
            },
        },
        animation: { duration: 1200, easing: 'easeOutQuart' },
        datasets: {
            bar: {
                backgroundColor: (ctx: { chart: { ctx: CanvasRenderingContext2D } }) => {
                    const gradient = ctx.chart.ctx.createLinearGradient(0, 0, 0, 280);
                    gradient.addColorStop(0, accentPrimary);
                    gradient.addColorStop(1, accentSecondary);
                    return gradient;
                },
                hoverBackgroundColor: (ctx: { chart: { ctx: CanvasRenderingContext2D } }) => {
                    const gradient = ctx.chart.ctx.createLinearGradient(0, 0, 0, 280);
                    gradient.addColorStop(0, accentLight);
                    gradient.addColorStop(1, accentPrimary);
                    return gradient;
                },
            },
        },
    };
}

onMounted(buildOptions);
watch(() => theme.isDark, buildOptions);
</script>

<template>
    <div class="claims-chart-card">
        <div class="chart-header">
            <div class="chart-header-left">
                <i class="pi pi-chart-bar chart-icon" aria-hidden="true" />
                <div>
                    <h3 class="chart-title">New Claims</h3>
                    <p class="chart-subtitle">Monthly overview — {{ currentYear }}</p>
                </div>
            </div>
            <span class="chart-badge">{{ totalClaims }} total</span>
        </div>
        <div class="chart-body">
            <Chart type="bar" :data="chartData" :options="chartOptions" class="claims-chart-canvas" />
        </div>
    </div>
</template>

<style scoped>
.claims-chart-card {
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
}

.chart-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
}

.chart-header-left {
    display: flex;
    align-items: center;
    gap: var(--space-3);
}

.chart-icon {
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

.chart-title {
    font-size: var(--text-lg);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
    margin: 0;
    line-height: 1.2;
}

.chart-subtitle {
    font-size: var(--text-xs);
    color: var(--text-muted);
    margin: var(--space-1) 0 0 0;
    line-height: 1.3;
}

.chart-badge {
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    color: var(--accent-primary);
    background: color-mix(in srgb, var(--accent-primary) 12%, transparent);
    padding: var(--space-1) var(--space-3);
    border-radius: var(--radius-xl);
}

.chart-body {
    flex: 1;
    position: relative;
    min-height: 0;
}

.claims-chart-canvas {
    display: block;
    height: 100%;
    width: 100%;
}
</style>
