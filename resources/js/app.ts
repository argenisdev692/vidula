import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h, type DefineComponent } from 'vue';
import { createPinia } from 'pinia';
import { PiniaColada } from '@pinia/colada';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import Tooltip from 'primevue/tooltip';
import 'primeicons/primeicons.css';

import './echo';

const appName = 'Vidula';

void createInertiaApp({
    title: (title: string): string => (title ? `${title} — ${appName}` : appName),
    resolve: (name: string) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(createPinia())
            .use(PiniaColada, {
                queryOptions: {
                    // 2 minutes — server state is fresh enough for admin CRUD.
                    staleTime: 1000 * 60 * 2,
                },
            })
            // PrimeVue in UNSTYLED mode — no theme preset, no design-token CSS layer.
            // Volt primitives carry all styling via Tailwind pass-through (§1).
            .use(PrimeVue, { unstyled: true })
            .use(ToastService) // powers <Toast/> + useToast() (§12)
            .use(ConfirmationService) // powers destructive confirmations (§10)
            .directive('tooltip', Tooltip)
            .mount(el);
    },
    progress: {
        color: 'var(--accent-primary)',
    },
});
