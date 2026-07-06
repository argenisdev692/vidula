import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h, type DefineComponent } from 'vue';
import type { SharedProps } from '@/types/inertia';
import { createPinia } from 'pinia';
import { PiniaColada } from '@pinia/colada';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import Tooltip from 'primevue/tooltip';
import 'primeicons/primeicons.css';

import './echo';

const appName = 'Vidula';

// App-wide brand for the document-title suffix — the DB company name shared on
// every page (`company.name`), falling back to appName before the first page
// resolves. Captured from the initial page in setup() and refreshed on each
// Inertia visit so a company-name change is reflected without a full reload.
let brandName = appName;

function readBrandName(props: SharedProps | undefined): string {
    return props?.company?.name || appName;
}

void createInertiaApp({
    // Session pages pass a short name ("Dashboard") → "Dashboard — {company}".
    // Guest pages pass a complete title (already contains the " — " separator,
    // e.g. "{company} — {tagline}" or "Reset password — {company}") → verbatim.
    title: (title: string): string =>
        !title ? brandName : title.includes(' — ') ? title : `${title} — ${brandName}`,
    resolve: (name: string) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        // Seed the brand from the initial page, then keep it fresh on each visit.
        brandName = readBrandName(props.initialPage.props as unknown as SharedProps);
        router.on('success', (event): void => {
            brandName = readBrandName(event.detail.page.props as unknown as SharedProps);
        });

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
