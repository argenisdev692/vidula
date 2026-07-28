<script setup lang="ts">
/**
 * Edit user — dedicated edit page (GET /users/{uuid}/edit, UPDATE_USERS). Same
 * shell as Create (BackLink + Volt Card) driven by the shared UserForm. Roles are
 * NOT edited here (that stays on the user's Access screen); profile photo is out
 * of scope. On success the backend redirects to the list.
 */
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import BackLink from '@/common/ui/BackLink.vue';
import Card from '@/volt/Card.vue';
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

            <Card class="form-card">
                <template #content>
                    <UserForm mode="edit" :user="user" />
                </template>
            </Card>
        </div>
    </PermissionGuard>
</template>

<style scoped>
.form-page {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    width: 100%;
    max-width: 52rem;
    margin-inline: auto;
}

.form-card {
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
</style>
