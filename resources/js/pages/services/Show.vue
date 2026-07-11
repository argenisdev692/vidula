<script setup lang="ts">
/**
 * Service detail — read-only view rendered by GET /services/{uuid}
 * (VIEW_SERVICES). The handler resolves the record `withTrashed`, so a
 * suspended service is viewable here; its soft-delete status is shown via a
 * badge alongside the separate catalog-visibility (`is_active`) badge. The
 * detail chrome (back link, glass card, facts styling) lives in the shared
 * {@see DetailCard}.
 */
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import DetailCard from '@/common/ui/DetailCard.vue';
import StatusBadge from '@/common/ui/StatusBadge.vue';
import { formatDate } from '@/modules/services/helpers/formatDate';
import type { Service } from '@/modules/services/types';

defineOptions({ layout: AppLayout });

/** The detail render returns the full model (adds `updated_at` over list rows). */
type ServiceDetail = Service & { updated_at?: string | null };

const props = defineProps<{
    service: ServiceDetail;
}>();

const isSuspended = computed<boolean>(() => props.service.deleted_at !== null);
const name = computed<string>(() => props.service.name || 'Untitled service');
</script>

<template>
    <Head :title="name" />

    <DetailCard
        header-title="Service"
        header-subtitle="Service detail"
        permission="VIEW_SERVICES"
        fallback-text="You don't have permission to view this service."
        back-href="/services"
        back-label="Back to services"
        :title="name"
    >
        <template #badges>
            <StatusBadge
                :tone="service.is_active ? 'success' : 'muted'"
                :label="service.is_active ? 'In catalog' : 'Hidden'"
            />
            <StatusBadge
                :tone="isSuspended ? 'danger' : 'success'"
                :label="isSuspended ? 'Suspended' : 'Active'"
            />
        </template>

        <dl class="facts">
            <div class="fact">
                <dt>Slug</dt>
                <dd class="mono">{{ service.slug }}</dd>
            </div>
            <div class="fact">
                <dt>Sort order</dt>
                <dd>{{ service.sort_order }}</dd>
            </div>
            <div class="fact fact--wide">
                <dt>Description</dt>
                <dd>{{ service.description || '—' }}</dd>
            </div>
            <div class="fact">
                <dt>Created</dt>
                <dd>{{ formatDate(service.created_at) }}</dd>
            </div>
            <div class="fact">
                <dt>Last updated</dt>
                <dd>{{ formatDate(service.updated_at ?? null) }}</dd>
            </div>
        </dl>
    </DetailCard>
</template>
