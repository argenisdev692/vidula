<script setup lang="ts">
/**
 * Create / edit modal for a permission. There is no `GET /create` or
 * `GET /{uuid}/edit` route — the backend store/update return `back()` redirects —
 * so the form lives in a Volt Dialog on the Index page and submits JSON via
 * Inertia `useForm`:
 *
 *   · create → POST /permissions
 *   · edit   → PUT  /permissions/{uuid}
 *
 * The name follows the project convention `{ACTION}_{MODULE}` (UPPER_SNAKE_CASE);
 * client-side Zod mirrors the backend regex, and the server stays authoritative
 * (uniqueness + shape) surfacing errors through `form.errors`.
 */
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import TextField from '@/common/form/TextField.vue';
import AppModal from '@/common/ui/AppModal.vue';
import { permissionFormSchema, type PermissionFormValues } from '@/modules/authorization/schemas/permissionFormSchema';
import type { Permission } from '@/modules/authorization/types';

const visible = defineModel<boolean>('visible', { default: false });

const props = withDefaults(
    defineProps<{
        mode?: 'create' | 'edit';
        permission?: Permission | null;
    }>(),
    { mode: 'create', permission: null },
);

const emit = defineEmits<{ saved: [] }>();

const form = useForm<PermissionFormValues>({
    name: '',
});

const isEdit = computed<boolean>(() => props.mode === 'edit');
const dialogTitle = computed<string>(() => (isEdit.value ? 'Edit permission' : 'New permission'));

/** Re-seed the form each time the dialog opens (never carry stale state). */
watch(visible, (open) => {
    if (!open) {
        return;
    }
    form.clearErrors();
    form.name = props.permission?.name ?? '';
});

function close(): void {
    visible.value = false;
}

function submit(): void {
    const parsed = permissionFormSchema.safeParse({ name: form.name });
    if (!parsed.success) {
        form.clearErrors();
        for (const issue of parsed.error.issues) {
            const key = issue.path[0];
            if (typeof key === 'string') {
                form.setError(key as keyof PermissionFormValues, issue.message);
            }
        }
        return;
    }

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            close();
        },
    };

    if (isEdit.value) {
        form.put(`/permissions/${props.permission!.uuid}`, options);
    } else {
        form.post('/permissions', options);
    }
}
</script>

<template>
    <AppModal
        v-model:visible="visible"
        :title="dialogTitle"
        subtitle="Follow the {ACTION}_{MODULE} naming convention."
        icon="pi pi-key"
        :confirm-label="isEdit ? 'Save changes' : 'Create permission'"
        confirm-icon="pi pi-check"
        :loading="form.processing"
        :dismissable="!form.processing"
        width="32rem"
        @confirm="submit"
        @cancel="close"
    >
        <form class="perm-form" @submit.prevent="submit">
            <TextField
                v-model="form.name"
                name="name"
                label="Permission name"
                placeholder="e.g. VIEW_ANY_USERS"
                required
                :maxlength="125"
                :error="form.errors.name"
                hint="UPPER_SNAKE_CASE, following {ACTION}_{MODULE}."
            />

            <!-- Hidden submit lets Enter submit the form from any field. -->
            <button type="submit" class="perm-form__enter" tabindex="-1" aria-hidden="true" />
        </form>
    </AppModal>
</template>

<style scoped>
.perm-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.perm-form__enter {
    display: none;
}
</style>
