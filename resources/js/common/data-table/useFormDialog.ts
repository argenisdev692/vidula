/**
 * Create / edit modal state shared by every index page that hosts its form in a
 * Volt Dialog: `visible` + `mode` + the `entity` under edit, plus the two openers.
 * Pages that instead navigate to dedicated create/edit routes (e.g. Users) don't
 * use this.
 *
 * Layer: common/ — pure Vue reactivity, no Inertia / module imports.
 */
import { ref, type Ref } from 'vue';

export type FormMode = 'create' | 'edit';

export function useFormDialog<T>() {
    const visible = ref<boolean>(false);
    const mode = ref<FormMode>('create');
    const entity = ref<T | null>(null) as Ref<T | null>;

    function openCreate(): void {
        mode.value = 'create';
        entity.value = null;
        visible.value = true;
    }

    function openEdit(target: T): void {
        mode.value = 'edit';
        entity.value = target;
        visible.value = true;
    }

    return { visible, mode, entity, openCreate, openEdit };
}
