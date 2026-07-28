<script setup lang="ts">
/**
 * Create / edit meeting modal wrapping MeetingForm (variant=dialog).
 * Store/update return `back()` so the Index stays mounted.
 */
import { computed, ref } from 'vue';
import AppModal from '@/common/ui/AppModal.vue';
import MeetingForm from './MeetingForm.vue';
import type { MeetingEditData, MeetingPrefill } from '@/modules/meeting/types';

const visible = defineModel<boolean>('visible', { default: false });

const props = withDefaults(
    defineProps<{
        mode?: 'create' | 'edit';
        meeting?: MeetingEditData | null;
        prefill?: MeetingPrefill | null;
    }>(),
    { mode: 'create', meeting: null, prefill: null },
);

const emit = defineEmits<{ saved: [] }>();

const formRef = ref<InstanceType<typeof MeetingForm> | null>(null);

const isEdit = computed<boolean>(() => props.mode === 'edit');
const dialogTitle = computed<string>(() => (isEdit.value ? 'Edit meeting' : 'New meeting'));

function close(): void {
    visible.value = false;
}

function submit(): void {
    formRef.value?.submit();
}

function onSaved(): void {
    emit('saved');
    close();
}
</script>

<template>
    <AppModal
        v-model:visible="visible"
        :title="dialogTitle"
        :subtitle="isEdit ? 'Update this meeting. Duration stays fixed.' : 'Schedule a meeting — pick a day and start time.'"
        icon="pi pi-calendar-plus"
        :confirm-label="isEdit ? 'Save changes' : 'Create meeting'"
        :confirm-icon="isEdit ? 'pi pi-check' : 'pi pi-calendar-plus'"
        :loading="Boolean(formRef?.processing)"
        :dismissable="!Boolean(formRef?.processing)"
        width="40rem"
        @confirm="submit"
        @cancel="close"
    >
        <MeetingForm
            v-if="visible"
            ref="formRef"
            :mode="mode"
            :meeting="meeting"
            :prefill="prefill"
            variant="dialog"
            @saved="onSaved"
            @cancel="close"
        />
    </AppModal>
</template>
