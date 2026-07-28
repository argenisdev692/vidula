<script setup lang="ts">
/**
 * Client detail — read-only view from GET /clients/{uuid} (VIEW_CLIENTS).
 * withTrashed, so suspended clients remain viewable with a Suspended badge.
 * Relation counts (invoices / products) come from withCount on the backend.
 */
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import DetailCard from '@/common/ui/DetailCard.vue';
import StatusBadge from '@/common/ui/StatusBadge.vue';
import { formatDateShort } from '@/modules/clients/helpers/formatDate';
import type { Client } from '@/modules/clients/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    client: Client;
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

const invoicesCount = computed<number>(() => props.client.invoices_count ?? 0);
const productsCount = computed<number>(() => props.client.products_count ?? 0);

function toHttpUrl(url: string | null | undefined): string | null {
    const trimmed = url?.trim();
    if (!trimmed) {
        return null;
    }
    return /^https?:\/\//i.test(trimmed) ? trimmed : `https://${trimmed}`;
}

const websiteHref = computed(() => toHttpUrl(props.client.website));
const facebookHref = computed(() => toHttpUrl(props.client.facebook_link));
const instagramHref = computed(() => toHttpUrl(props.client.instagram_link));
const linkedinHref = computed(() => toHttpUrl(props.client.linkedin_link));
const twitterHref = computed(() => toHttpUrl(props.client.twitter_link));
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
                <dd>
                    <a
                        v-if="client.email"
                        class="ext-link"
                        :href="`mailto:${client.email}`"
                    >{{ client.email }}</a>
                    <template v-else>—</template>
                </dd>
            </div>
            <div class="fact">
                <dt>Phone</dt>
                <dd class="mono">
                    <a
                        v-if="client.phone"
                        class="ext-link"
                        :href="`tel:${client.phone}`"
                    >{{ client.phone }}</a>
                    <template v-else>—</template>
                </dd>
            </div>
            <div class="fact">
                <dt>Owner</dt>
                <dd>{{ ownerName }}</dd>
            </div>
            <div class="fact">
                <dt>Invoices</dt>
                <dd class="mono">{{ invoicesCount }}</dd>
            </div>
            <div class="fact">
                <dt>Products</dt>
                <dd class="mono">{{ productsCount }}</dd>
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
                <dd>
                    <a
                        v-if="websiteHref"
                        class="ext-link"
                        :href="websiteHref"
                        target="_blank"
                        rel="noopener noreferrer"
                    >{{ client.website }}</a>
                    <template v-else>—</template>
                </dd>
            </div>
            <div class="fact">
                <dt>Facebook</dt>
                <dd>
                    <a
                        v-if="facebookHref"
                        class="ext-link"
                        :href="facebookHref"
                        target="_blank"
                        rel="noopener noreferrer"
                    >{{ client.facebook_link }}</a>
                    <template v-else>—</template>
                </dd>
            </div>
            <div class="fact">
                <dt>Instagram</dt>
                <dd>
                    <a
                        v-if="instagramHref"
                        class="ext-link"
                        :href="instagramHref"
                        target="_blank"
                        rel="noopener noreferrer"
                    >{{ client.instagram_link }}</a>
                    <template v-else>—</template>
                </dd>
            </div>
            <div class="fact">
                <dt>LinkedIn</dt>
                <dd>
                    <a
                        v-if="linkedinHref"
                        class="ext-link"
                        :href="linkedinHref"
                        target="_blank"
                        rel="noopener noreferrer"
                    >{{ client.linkedin_link }}</a>
                    <template v-else>—</template>
                </dd>
            </div>
            <div class="fact">
                <dt>Twitter / X</dt>
                <dd>
                    <a
                        v-if="twitterHref"
                        class="ext-link"
                        :href="twitterHref"
                        target="_blank"
                        rel="noopener noreferrer"
                    >{{ client.twitter_link }}</a>
                    <template v-else>—</template>
                </dd>
            </div>
            <div class="fact fact--wide">
                <dt>Notes</dt>
                <dd>{{ client.notes || '—' }}</dd>
            </div>
            <div class="fact">
                <dt>Created</dt>
                <dd>{{ formatDateShort(client.created_at) }}</dd>
            </div>
            <div class="fact">
                <dt>Last updated</dt>
                <dd>{{ formatDateShort(client.updated_at ?? null) }}</dd>
            </div>
        </dl>
    </DetailCard>
</template>

<style scoped>
.ext-link {
    color: var(--accent-primary);
    text-decoration: none;
    word-break: break-all;
}

.ext-link:hover {
    text-decoration: underline;
}

.ext-link:focus-visible {
    outline: 2px solid var(--accent-primary);
    outline-offset: 2px;
    border-radius: var(--radius-sm);
}

.mono {
    font-family: var(--font-mono, monospace);
    font-size: var(--text-sm);
}
</style>
