<script setup lang="ts">
/**
 * Create / edit lead modal wrapping AppointmentForm (variant=dialog).
 * Store/update return `back()` so the Index stays mounted.
 */
import { computed, ref } from 'vue';
import AppModal from '@/common/ui/AppModal.vue';
import AppointmentForm from './AppointmentForm.vue';
import type { AppointmentEditData } from '@/modules/appointments/types';

const visible = defineModel<boolean>('visible', { default: false });

const props = withDefaults(
    defineProps<{
        mode?: 'create' | 'edit';
        appointment?: AppointmentEditData | null;
    }>(),
    { mode: 'create', appointment: null },
);

const emit = defineEmits<{ saved: [] }>();

const formRef = ref<InstanceType<typeof AppointmentForm> | null>(null);

const isEdit = computed<boolean>(() => props.mode === 'edit');
const dialogTitle = computed<string>(() => (isEdit.value ? 'Edit lead' : 'New lead'));

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
        :subtitle="isEdit ? 'Update this lead’s profile.' : 'Capture a lead with contact details.'"
        icon="pi pi-user-plus"
        :confirm-label="isEdit ? 'Save changes' : 'Create lead'"
        :confirm-icon="isEdit ? 'pi pi-check' : 'pi pi-user-plus'"
        :loading="Boolean(formRef?.processing)"
        :dismissable="!Boolean(formRef?.processing)"
        width="44rem"
        @confirm="submit"
        @cancel="close"
    >
        <AppointmentForm
            v-if="visible"
            ref="formRef"
            :mode="mode"
            :appointment="appointment"
            variant="dialog"
            @saved="onSaved"
            @cancel="close"
        />
    </AppModal>
</template>
