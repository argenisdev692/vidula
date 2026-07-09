<script setup lang="ts">
/**
 * Create / edit modal for a role. There is no `GET /create` or `GET /{uuid}/edit`
 * route — the backend store/update return `back()` redirects — so the form lives
 * in a Volt Dialog on the Index page and submits JSON via Inertia `useForm`:
 *
 *   · create → POST /roles
 *   · edit   → PUT  /roles/{uuid}
 *
 * `permissions` is the FULL set of grants to sync (empty ⇒ revoke all). A
 * protected system role may have its permissions adjusted but never be renamed,
 * so the name field is locked for those (mirrors UpdateRoleHandler). Client-side
 * Zod validation mirrors the backend; the server stays authoritative and surfaces
 * errors through `form.errors`.
 */
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import TextField from '@/common/form/TextField.vue';
import AppModal from '@/common/ui/AppModal.vue';
import PermissionPicker from './PermissionPicker.vue';
import { roleFormSchema, type RoleFormValues } from '@/modules/authorization/schemas/roleFormSchema';
import type { Role } from '@/modules/authorization/types';

const visible = defineModel<boolean>('visible', { default: false });

const props = withDefaults(
    defineProps<{
        mode?: 'create' | 'edit';
        role?: Role | null;
        available: string[];
        protectedRoles?: string[];
    }>(),
    { mode: 'create', role: null, protectedRoles: () => [] },
);

const emit = defineEmits<{ saved: [] }>();

const form = useForm<RoleFormValues>({
    name: '',
    permissions: [],
});

const isEdit = computed<boolean>(() => props.mode === 'edit');
const isProtected = computed<boolean>(
    () => isEdit.value && !!props.role && props.protectedRoles.includes(props.role.name),
);
const dialogTitle = computed<string>(() => (isEdit.value ? 'Edit role' : 'New role'));

/** Re-seed the form each time the dialog opens (never carry stale state). */
watch(visible, (open) => {
    if (!open) {
        return;
    }
    form.clearErrors();
    form.name = props.role?.name ?? '';
    form.permissions = props.role?.permissions?.map((p) => p.name) ?? [];
});

function close(): void {
    visible.value = false;
}

function submit(): void {
    const parsed = roleFormSchema.safeParse({ name: form.name, permissions: form.permissions });
    if (!parsed.success) {
        form.clearErrors();
        for (const issue of parsed.error.issues) {
            const key = issue.path[0];
            if (typeof key === 'string') {
                form.setError(key as keyof RoleFormValues, issue.message);
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
        form.put(`/roles/${props.role!.uuid}`, options);
    } else {
        form.post('/roles', options);
    }
}
</script>

<template>
    <AppModal
        v-model:visible="visible"
        :title="dialogTitle"
        subtitle="Name the role and choose the permissions it grants."
        icon="pi pi-shield"
        :confirm-label="isEdit ? 'Save changes' : 'Create role'"
        confirm-icon="pi pi-check"
        :loading="form.processing"
        :dismissable="!form.processing"
        width="44rem"
        @confirm="submit"
        @cancel="close"
    >
        <form class="role-form" @submit.prevent="submit">
            <TextField
                v-model="form.name"
                name="name"
                label="Role name"
                placeholder="e.g. EDITOR"
                required
                :maxlength="125"
                :disabled="isProtected"
                :error="form.errors.name"
                :hint="isProtected ? 'System roles cannot be renamed — you can still adjust their permissions.' : 'Letters, numbers, spaces, hyphens or underscores.'"
            />

            <div class="role-form__perms">
                <span class="role-form__perms-label">Permissions</span>
                <PermissionPicker v-model="form.permissions" :available="available" />
                <p v-if="form.errors.permissions" class="role-form__error" role="alert">
                    {{ form.errors.permissions }}
                </p>
            </div>

            <!-- Hidden submit lets Enter submit the form from the name field. -->
            <button type="submit" class="role-form__enter" tabindex="-1" aria-hidden="true" />
        </form>
    </AppModal>
</template>

<style scoped>
.role-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.role-form__perms {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.role-form__perms-label {
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--text-secondary);
}

.role-form__error {
    margin: 0;
    font-size: var(--text-xs);
    color: var(--accent-error);
}

.role-form__enter {
    display: none;
}
</style>
