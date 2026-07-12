/// <reference types="vite/client" />

import type Echo from 'laravel-echo';

declare module '*.vue' {
    import type { DefineComponent } from 'vue';

    const component: DefineComponent<Record<string, unknown>, Record<string, unknown>, unknown>;
    export default component;
}

declare global {
    interface Window {
        /** Set by `resources/js/echo.js` only when VITE_REVERB_APP_KEY is configured. */
        Echo?: Echo<'reverb'>;
    }
}
