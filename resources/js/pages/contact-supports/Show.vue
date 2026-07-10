<script setup lang="ts">
/**
 * Contact request detail — read-only view rendered by GET /contact-supports/{uuid}
 * (VIEW_CONTACT_SUPPORTS). The handler resolves the record `withTrashed`, so a
 * suspended request is viewable here; its status is shown via a badge alongside
 * the read/unread state. Chrome + facts styling live in the shared {@see DetailCard}.
 */
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import DetailCard from '@/common/ui/DetailCard.vue';
import StatusBadge from '@/common/ui/StatusBadge.vue';
import { formatDateTime } from '@/modules/contact-support/helpers/formatDate';
import type { ContactSupportDetail } from '@/modules/contact-support/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    contactSupport: ContactSupportDetail;
}>();

const isSuspended = computed<boolean>(() => props.contactSupport.deleted_at !== null);
const fullName = computed<string>(
    () => [props.contactSupport.first_name, props.contactSupport.last_name].filter(Boolean).join(' ').trim() || 'Unknown sender',
);
</script>

<template>
    <Head :title="fullName" />

    <DetailCard
        header-title="Contact Request"
        header-subtitle="Contact request detail"
        permission="VIEW_CONTACT_SUPPORTS"
        fallback-text="You don't have permission to view this contact request."
        back-href="/contact-supports"
        back-label="Back to inbox"
        :title="fullName"
    >
        <template #badges>
            <StatusBadge
                :tone="contactSupport.readed ? 'muted' : 'primary'"
                :label="contactSupport.readed ? 'Read' : 'Unread'"
                :strong="!contactSupport.readed"
            />
            <StatusBadge
                :tone="isSuspended ? 'danger' : 'success'"
                :label="isSuspended ? 'Suspended' : 'Active'"
            />
        </template>

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
    </DetailCard>
</template>

<style scoped>
.message {
    white-space: pre-wrap;
    word-break: break-word;
}
</style>
