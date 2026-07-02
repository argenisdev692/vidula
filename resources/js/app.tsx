import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { ComponentType } from 'react';
import { createRoot } from 'react-dom/client';

import './echo';

void createInertiaApp({
    title: (title: string): string => (title ? `${title} — Vidula` : 'Vidula'),
    resolve: async (name: string) => {
        const page = await resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob<{ default: ComponentType }>('./pages/**/*.tsx'),
        );

        return page.default;
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: 'var(--primary)',
    },
});
