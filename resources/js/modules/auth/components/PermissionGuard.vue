<script setup lang="ts">
/**
 * Conditionally renders its slot based on the current user's permissions.
 * Pass no `permission` to render for any authenticated user. Optionally provide
 * a `#fallback` slot for the unauthorized branch.
 */
import { computed } from 'vue';
import { useAuthorization } from '@/modules/auth/composables/useAuthorization';
import type { PermissionInput } from '@/modules/auth/types';

const props = withDefaults(
    defineProps<{
        /** One permission or a set. Omit to allow any authenticated user. */
        permission?: PermissionInput;
        /** When multiple permissions are passed, require every one (default: any). */
        requireAll?: boolean;
    }>(),
    { requireAll: false },
);

const { hasPermission } = useAuthorization();

const allowed = computed<boolean>(() => hasPermission(props.permission, props.requireAll));
</script>

<template>
    <slot v-if="allowed" />
    <slot v-else name="fallback" />
</template>
