<script setup lang="ts">
/**
 * Edit user — dedicated edit page (GET /users/{uuid}/edit, UPDATE_USERS). Same
 * shell as the Show / Create screens (BackLink + card) driven by the shared
 * UserForm. Roles are NOT edited here (that stays on the user's Access panel);
 * profile photo is out of scope. On success the backend redirects to the list.
 */
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import BackLink from '@/common/ui/BackLink.vue';
import UserForm from './components/UserForm.vue';
import type { UserEditData } from '@/modules/users/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    user: UserEditData;
}>();

const fullName = computed<string>(
    () => [props.user.first_name, props.user.last_name].filter(Boolean).join(' ').trim() || props.user.email,
);
</script>

<template>
    <Head :title="`Edit ${fullName}`" />

    <AppHeader title="Edit user" subtitle="Update this user's profile details" />

    <PermissionGuard permission="UPDATE_USERS">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to edit users.</p>
            </div>
        </template>

        <div class="form-page">
            <BackLink href="/users" label="Back to users" />

            <article class="card">
                <UserForm mode="edit" :user="user" />
            </article>
        </div>
    </PermissionGuard>
</template>

<style scoped>
.form-page {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    max-width: 52rem;
}

.card {
    background: color-mix(in srgb, var(--bg-surface) 60%, transparent);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-2xl);
    padding: var(--space-6) var(--space-8);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

.empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-16) var(--space-6);
    color: var(--text-muted);
}

.empty .pi {
    font-size: var(--text-3xl);
}

@media (max-width: 640px) {
    .card {
        padding: var(--space-5) var(--space-4);
    }
}
</style>
