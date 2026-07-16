/**
 * Lazy, single-flight loader for the Google Maps JavaScript API.
 *
 * The bootstrap `<script>` is injected at most once per page load; concurrent
 * callers share the same promise. The injected tag copies the CSP nonce from an
 * existing Vite tag so it satisfies the nonce-based `script-src` emitted by
 * {@see Shared\Infrastructure\Http\Middleware\SecurityHeaders}. `loading=async`
 * enables the modern `google.maps.importLibrary()` dynamic import used by the
 * Places (New) Data API.
 */
let loaderPromise: Promise<typeof google> | null = null;

export function loadGoogleMaps(apiKey: string): Promise<typeof google> {
    if (loaderPromise) {
        return loaderPromise;
    }

    loaderPromise = new Promise<typeof google>((resolve, reject) => {
        // `@types/google.maps` declares `Window.google` as always-present; re-type it as
        // optional here since the script (and thus the global) may not have loaded yet.
        const win = window as unknown as Omit<typeof window, 'google'> & { google?: typeof google };
        if (win.google?.maps?.importLibrary) {
            resolve(win.google);
            return;
        }

        if (!apiKey) {
            reject(new Error('Missing Google Maps API key.'));
            return;
        }

        const callbackName = '__vidulaInitGoogleMaps';
        (window as unknown as Record<string, () => void>)[callbackName] = () => resolve(window.google);

        const params = new URLSearchParams({
            key: apiKey,
            v: 'weekly',
            loading: 'async',
            callback: callbackName,
        });

        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?${params.toString()}`;
        script.async = true;
        script.nonce = document.querySelector<HTMLScriptElement>('script[nonce]')?.nonce ?? '';
        script.onerror = () => {
            loaderPromise = null;
            reject(new Error('Failed to load the Google Maps JavaScript API.'));
        };

        document.head.append(script);
    });

    return loaderPromise;
}
