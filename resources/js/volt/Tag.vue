<script lang="ts">
/** Severity tones a status chip can express (superset of PrimeVue's own set). */
export type TagSeverity = 'primary' | 'secondary' | 'success' | 'info' | 'warn' | 'danger' | 'contrast';
</script>

<script setup lang="ts">
/**
 * Volt Tag — status chip. Local edit vs the stock `npx volt-vue add Tag` output:
 * instead of the default Tailwind severity palette (`bg-green-100` …), the root
 * `pt` maps `severity` to the project's token-driven `.status-tag*` classes
 * defined once in globals.css, so light/dark and brand accents remain the single
 * source of truth (FRONTEND/SKILL.md §0/§10). No `severity` ⇒ the primary accent.
 */
import Tag, { type TagPassThroughOptions, type TagProps } from 'primevue/tag';
import { ref } from 'vue';
import { ptViewMerge } from './utils';

interface Props extends /* @vue-ignore */ TagProps {}
defineProps<Props>();

const severityClass: Record<string, string> = {
    primary: 'status-tag--primary',
    secondary: 'status-tag--secondary',
    success: 'status-tag--success',
    info: 'status-tag--info',
    warn: 'status-tag--warn',
    danger: 'status-tag--danger',
    contrast: 'status-tag--contrast',
};

const theme = ref<TagPassThroughOptions>({
    root: ({ props }) => ({
        class: ['status-tag', severityClass[props.severity as string] ?? 'status-tag--primary'],
    }),
});
</script>

<template>
    <Tag unstyled :pt="theme" :pt-options="{ mergeProps: ptViewMerge }">
        <template v-for="(_, slotName) in $slots" #[slotName]="slotProps">
            <slot :name="slotName" v-bind="slotProps ?? {}" />
        </template>
    </Tag>
</template>
