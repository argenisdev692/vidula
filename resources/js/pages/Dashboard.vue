<script setup lang="ts">
/**
 * Dashboard — authenticated home. Software-oriented stats (Users, Students,
 * Classrooms, AI Generations), New Products chart, and Activity Log feed.
 * Counts / series / activities arrive from {@see DashboardController}.
 */
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import DashboardCard from '@/modules/dashboard/components/DashboardCard.vue';
import ClaimsChart from '@/modules/dashboard/components/ClaimsChart.vue';
import RecentActivity from '@/modules/dashboard/components/RecentActivity.vue';
import type { DashboardActivity, DashboardStats } from '@/modules/dashboard/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    stats: DashboardStats;
    productSeries: number[];
    activities: DashboardActivity[];
}>();

const usersLabel = computed<string>(() => props.stats.users.toLocaleString('en-US'));
const studentsLabel = computed<string>(() => props.stats.students.toLocaleString('en-US'));
const classroomsLabel = computed<string>(() => props.stats.classrooms.toLocaleString('en-US'));
const aiLabel = computed<string>(() => props.stats.ai_generations.toLocaleString('en-US'));

function onNewProduct(): void {
    router.visit('/products');
}

/* Cheap deferred reveal so heavy visuals mount after the header paints. */
const ready = ref<boolean>(true);
</script>

<template>
    <Head title="Dashboard" />

    <AppHeader
        title="Dashboard"
        subtitle="Welcome back — your software workspace at a glance."
        show-action
        action-label="New Product"
        action-icon="pi-plus"
        @action="onNewProduct"
    />

    <section class="stats-grid">
        <DashboardCard
            title="Users"
            :value="usersLabel"
            subtitle="Active accounts in the workspace"
            icon="pi-users"
            trend="up"
            trend-value="+4"
            color="purple"
        />
        <DashboardCard
            title="Students"
            :value="studentsLabel"
            subtitle="Learners enrolled across products"
            icon="pi-graduation-cap"
            trend="up"
            trend-value="+2"
            color="blue"
        />
        <DashboardCard
            title="Classrooms"
            :value="classroomsLabel"
            subtitle="Live teaching spaces"
            icon="pi-building"
            trend="neutral"
            color="green"
        />
        <DashboardCard
            title="AI Generations"
            :value="aiLabel"
            subtitle="Content pipeline runs"
            icon="pi-sparkles"
            trend="up"
            trend-value="+3"
            color="orange"
        />
    </section>

    <section class="dashboard-bottom-grid">
        <template v-if="ready">
            <ClaimsChart :series="productSeries" />
            <RecentActivity :activities="activities" />
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
