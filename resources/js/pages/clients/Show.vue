<script setup lang="ts">
/**
 * Client detail — read-only view from GET /clients/{uuid} (VIEW_CLIENTS).
 * withTrashed, so suspended clients remain viewable with a Suspended badge.
 */
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import DetailCard from '@/common/ui/DetailCard.vue';
import StatusBadge from '@/common/ui/StatusBadge.vue';
import { formatDate } from '@/modules/clients/helpers/formatDate';
import type { Client } from '@/modules/clients/types';

defineOptions({ layout: AppLayout });

type ClientDetail = Client & { updated_at?: string | null };

const props = defineProps<{
    client: ClientDetail;
}>();

const isSuspended = computed<boolean>(() => props.client.deleted_at !== null);
const name = computed<string>(() => props.client.client_name || 'Untitled client');

const lifecycleTone = computed<'success' | 'primary' | 'muted'>(() => {
    if (props.client.status === 'ACTIVE') {
        return 'success';
    }
    if (props.client.status === 'DRAFT') {
        return 'primary';
    }
    return 'muted';
});

const ownerName = computed<string>(() => {
    const label = [props.client.user?.first_name, props.client.user?.last_name].filter(Boolean).join(' ').trim();
    return label || 'System';
});
</script>

<template>
    <Head :title="name" />

    <DetailCard
        header-title="Client"
        header-subtitle="CRM contact detail"
        permission="VIEW_CLIENTS"
        fallback-text="You don't have permission to view this client."
        back-href="/clients"
        back-label="Back to clients"
        :title="name"
    >
        <template #badges>
            <StatusBadge :tone="lifecycleTone" :label="client.status" />
            <StatusBadge
                :tone="isSuspended ? 'danger' : 'success'"
                :label="isSuspended ? 'Suspended' : 'Active'"
            />
        </template>

        <dl class="facts">
            <div class="fact">
                <dt>Email</dt>
                <dd>{{ client.email || '—' }}</dd>
            </div>
            <div class="fact">
                <dt>Phone</dt>
                <dd class="mono">{{ client.phone }}</dd>
            </div>
            <div class="fact">
                <dt>Owner</dt>
                <dd>{{ ownerName }}</dd>
            </div>
            <div class="fact">
                <dt>Tax ID</dt>
                <dd>{{ client.tax_id || '—' }}</dd>
            </div>
            <div class="fact">
                <dt>NIF</dt>
                <dd>{{ client.nif || '—' }}</dd>
            </div>
            <div class="fact fact--wide">
                <dt>Address</dt>
                <dd>{{ client.address || '—' }}</dd>
            </div>
            <div class="fact">
                <dt>Website</dt>
                <dd>{{ client.website || '—' }}</dd>
            </div>
            <div class="fact">
                <dt>Facebook</dt>
                <dd>{{ client.facebook_link || '—' }}</dd>
            </div>
            <div class="fact">
                <dt>Instagram</dt>
                <dd>{{ client.instagram_link || '—' }}</dd>
            </div>
            <div class="fact">
                <dt>LinkedIn</dt>
                <dd>{{ client.linkedin_link || '—' }}</dd>
            </div>
            <div class="fact">
                <dt>Twitter / X</dt>
                <dd>{{ client.twitter_link || '—' }}</dd>
            </div>
            <div class="fact fact--wide">
                <dt>Notes</dt>
                <dd>{{ client.notes || '—' }}</dd>
            </div>
            <div class="fact">
                <dt>Created</dt>
                <dd>{{ formatDate(client.created_at) }}</dd>
            </div>
            <div class="fact">
                <dt>Last updated</dt>
                <dd>{{ formatDate(client.updated_at ?? null) }}</dd>
            </div>
        </dl>
    </DetailCard>
</template>
