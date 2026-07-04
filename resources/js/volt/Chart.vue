<template>
    <Chart
        unstyled
        :pt="theme"
        :ptOptions="{
            mergeProps: ptViewMerge,
        }"
    >
        <template v-for="(_, slotName) in $slots" #[slotName]="slotProps">
            <slot :name="slotName" v-bind="slotProps ?? {}" />
        </template>
    </Chart>
</template>

<script setup lang="ts">
/**
 * Volt wrapper around PrimeVue's `Chart` (which renders through `chart.js/auto`).
 * Owned in-repo so the canvas host inherits Tailwind sizing without a theme
 * preset. Consumers pass `type`, `data` and `options` like the native component.
 */
import Chart, { type ChartPassThroughOptions, type ChartProps } from 'primevue/chart';
import { ref } from 'vue';
import { ptViewMerge } from './utils';

interface Props extends /* @vue-ignore */ ChartProps {}
defineProps<Props>();

const theme = ref<ChartPassThroughOptions>({
    root: `relative h-full w-full`,
    canvas: `h-full w-full`,
});
</script>
