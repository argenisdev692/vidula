<script setup lang="ts">
/**
 * Dashboard — authenticated home. Mirrors the GUIDE Angular dashboard 1:1:
 * page header (with the "New Product" action), a 4-up stat grid, and a bottom
 * grid pairing the claims chart with the recent-activity feed. Uses AppLayout,
 * which supplies the aurora background, overlay sidebar, floating menu button
 * and app-wide toast.
 */
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import DashboardCard from '@/modules/dashboard/components/DashboardCard.vue';
import ClaimsChart from '@/modules/dashboard/components/ClaimsChart.vue';
import RecentActivity from '@/modules/dashboard/components/RecentActivity.vue';

defineOptions({ layout: AppLayout });

function onNewProduct(): void {
    // TODO(product): route to the product create flow once that module ships.
}

/* Cheap deferred reveal (parity with Angular @defer) so heavy visuals mount
   after the header paints; a skeleton holds the layout in the meantime. */
const ready = ref<boolean>(true);
</script>

<template>
    <Head title="Dashboard" />

    <AppHeader
        title="Dashboard"
        subtitle="Welcome back — here's what's happening today."
        show-action
        action-label="New Product"
        action-icon="pi-plus"
        @action="onNewProduct"
    />

    <section class="stats-grid">
        <DashboardCard
            title="Total Revenue"
            value="$124,500"
            subtitle="+12.5% from last month"
            icon="pi-dollar"
            trend="up"
            trend-value="+12.5%"
            color="purple"
        />
        <DashboardCard
            title="Active Users"
            value="8,432"
            subtitle="+5.2% from last month"
            icon="pi-users"
            trend="up"
            trend-value="+5.2%"
            color="blue"
        />
        <DashboardCard
            title="Projects"
            value="24"
            subtitle="3 in progress"
            icon="pi-briefcase"
            trend="neutral"
            color="green"
        />
        <DashboardCard
            title="Conversion Rate"
            value="3.2%"
            subtitle="-0.8% from last month"
            icon="pi-chart-bar"
            trend="down"
            trend-value="-0.8%"
            color="orange"
        />
    </section>

    <section class="dashboard-bottom-grid">
        <template v-if="ready">
            <ClaimsChart />
            <RecentActivity />
        </template>
        <template v-else>
            <div class="skeleton chart-skeleton" aria-hidden="true" />
            <div class="skeleton chart-skeleton" aria-hidden="true" />
        </template>
    </section>
</template>

<style scoped>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--space-6);
    margin-bottom: var(--space-8);
}

.dashboard-bottom-grid {
    display: grid;
    grid-template-columns: 1.4fr 1fr;
    gap: var(--space-6);
    margin-bottom: var(--space-8);
    align-items: start;
}

.chart-skeleton {
    height: 420px;
    border-radius: var(--radius-2xl);
    background: color-mix(in srgb, var(--bg-surface) 40%, transparent);
    animation: skeleton-pulse 1.4s ease-in-out infinite;
}

@keyframes skeleton-pulse {
    0%,
    100% {
        opacity: 0.6;
    }
    50% {
        opacity: 0.9;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .dashboard-bottom-grid {
        grid-template-columns: 1fr;
    }
}

@media (prefers-reduced-motion: reduce) {
    .chart-skeleton {
        animation: none;
    }
}
</style>
