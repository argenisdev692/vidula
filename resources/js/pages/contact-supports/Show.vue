<script setup lang="ts">
/**
 * Contact request detail — read-only view rendered by GET /contact-supports/{uuid}
 * (VIEW_CONTACT_SUPPORTS). The handler resolves the record `withTrashed`, so a
 * suspended request is viewable here; its status is shown via a badge alongside
 * the read/unread state.
 */
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import BackLink from '@/common/ui/BackLink.vue';
import { formatDateTime } from '@/modules/contact-support/helpers/formatDate';
import type { SharedProps } from '@/types/inertia';
import type { ContactSupportDetail } from '@/modules/contact-support/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    contactSupport: ContactSupportDetail;
}>();

usePage<SharedProps>();

const isSuspended = computed<boolean>(() => props.contactSupport.deleted_at !== null);
const fullName = computed<string>(
    () => [props.contactSupport.first_name, props.contactSupport.last_name].filter(Boolean).join(' ').trim() || 'Unknown sender',
);
</script>

<template>
    <Head :title="fullName" />

    <AppHeader title="Contact Request" subtitle="Contact request detail" />

    <PermissionGuard permission="VIEW_CONTACT_SUPPORTS">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to view this contact request.</p>
            </div>
        </template>

        <div class="detail">
            <BackLink href="/contact-supports" label="Back to inbox" />

            <article class="card">
                <div class="card__head">
                    <h2 class="card__title">{{ fullName }}</h2>
                    <div class="card__badges">
                        <span class="badge" :class="contactSupport.readed ? 'badge--read' : 'badge--unread'">
                            {{ contactSupport.readed ? 'Read' : 'Unread' }}
                        </span>
                        <span class="badge" :class="isSuspended ? 'badge--suspended' : 'badge--active'">
                            {{ isSuspended ? 'Suspended' : 'Active' }}
                        </span>
                    </div>
                </div>

                <dl class="facts">
                    <div class="fact">
                        <dt>Email</dt>
                        <dd>
                            <a class="link" :href="`mailto:${contactSupport.email}`">{{ contactSupport.email }}</a>
                        </dd>
                    </div>
                    <div class="fact">
                        <dt>Phone</dt>
                        <dd>
                            <a class="link" :href="`tel:${contactSupport.phone}`">{{ contactSupport.phone }}</a>
                        </dd>
                    </div>
                    <div class="fact fact--wide">
                        <dt>Subject</dt>
                        <dd>{{ contactSupport.subject || '—' }}</dd>
                    </div>
                    <div class="fact fact--wide">
                        <dt>Message</dt>
                        <dd class="message">{{ contactSupport.message || '—' }}</dd>
                    </div>
                    <div class="fact">
                        <dt>SMS consent</dt>
                        <dd>{{ contactSupport.sms_consent ? 'Yes' : 'No' }}</dd>
                    </div>
                    <div class="fact">
                        <dt>Received</dt>
                        <dd>{{ formatDateTime(contactSupport.created_at) }}</dd>
                    </div>
                    <div class="fact">
                        <dt>Last updated</dt>
                        <dd>{{ formatDateTime(contactSupport.updated_at ?? null) }}</dd>
                    </div>
                </dl>
            </article>
        </div>
    </PermissionGuard>
</template>

<style scoped>
.detail {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    width: 100%;
    max-width: 48rem;
    margin-inline: auto;
}

.card {
    background: color-mix(in srgb, var(--bg-surface) 60%, transparent);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-2xl);
    padding: var(--space-6) var(--space-8);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

.card__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
    margin-bottom: var(--space-5);
    flex-wrap: wrap;
}

.card__title {
    margin: 0;
    font-size: var(--text-xl);
    font-weight: var(--font-bold);
    color: var(--text-primary);
}

.card__badges {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.facts {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-5);
    margin: 0;
}

.fact--wide {
    grid-column: 1 / -1;
}

.fact dt {
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--text-muted);
    margin-bottom: var(--space-1);
}

.fact dd {
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-primary);
    line-height: 1.5;
}

.message {
    white-space: pre-wrap;
    word-break: break-word;
}

.link {
    color: var(--accent-primary);
    text-decoration: none;
}

.link:hover {
    text-decoration: underline;
}

.badge {
    display: inline-flex;
    align-items: center;
    padding: 2px var(--space-3);
    border-radius: var(--radius-sm);
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
}

.badge--active {
    background: color-mix(in srgb, var(--accent-success) 18%, transparent);
    color: var(--accent-success);
}

.badge--suspended {
    background: color-mix(in srgb, var(--accent-error) 18%, transparent);
    color: var(--accent-error);
}

.badge--read {
    background: color-mix(in srgb, var(--text-muted) 16%, transparent);
    color: var(--text-secondary);
}

.badge--unread {
    background: color-mix(in srgb, var(--accent-primary) 18%, transparent);
    color: var(--accent-primary);
    font-weight: var(--font-semibold);
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

@media (max-width: 560px) {
    .facts {
        grid-template-columns: 1fr;
    }

    .card {
        padding: var(--space-5) var(--space-4);
    }
}
</style>
