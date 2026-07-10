/**
 * The confirm-dialog half of a CRUD list screen — the per-action `{ current,
 * visible, loading, ask, run }` state machine both the single-row and the bulk
 * ConfirmDialog copied verbatim on every index page.
 *
 * It is deliberately HTTP-agnostic: `run` hands the current action plus a
 * `finish` callback to a page-supplied `perform`, which fires whatever Inertia
 * request the resource needs (delete / restore / restore-all / resend / …). This
 * keeps the strict URL conventions AND the toast wording in the page where they
 * are reviewable, per the "per-page copy function" decision, while the reactive
 * boilerplate lives here once.
 *
 * Layer: common/ — pure Vue reactivity, no Inertia / module imports.
 */
import { computed, ref, type Ref } from 'vue';

/** Copy for the {@see ConfirmDialog} / AppModal header + footer. */
export interface ConfirmCopy {
    title: string;
    message: string;
    confirmLabel: string;
    confirmIcon: string;
    tone: 'danger' | 'success' | 'primary';
}

/** Rendered while no action is pending (dialog hidden), so bindings stay defined. */
const IDLE_COPY: ConfirmCopy = {
    title: '',
    message: '',
    confirmLabel: 'Confirm',
    confirmIcon: 'pi pi-check',
    tone: 'danger',
};

export function useConfirmAction<A>(copy: (action: A) => ConfirmCopy) {
    const current = ref<A | null>(null) as Ref<A | null>;
    const visible = ref<boolean>(false);
    const loading = ref<boolean>(false);

    /** Live confirm copy for the pending action; a benign default while idle. */
    const confirm = computed<ConfirmCopy>(() => (current.value ? copy(current.value) : IDLE_COPY));

    /** Stage an action and open its confirmation dialog. */
    function ask(action: A): void {
        current.value = action;
        visible.value = true;
    }

    /**
     * Execute the staged action. `perform` receives the action and a `finish`
     * callback that clears the loading state and closes the dialog — wire it to
     * the Inertia request's `onFinish`.
     */
    function run(perform: (action: A, finish: () => void) => void): void {
        const action = current.value;
        if (!action) {
            return;
        }
        loading.value = true;
        perform(action, () => {
            loading.value = false;
            visible.value = false;
        });
    }

    return { current, visible, loading, confirm, ask, run };
}
