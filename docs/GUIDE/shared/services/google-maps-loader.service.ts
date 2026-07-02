import { Injectable, signal } from '@angular/core';
import { readBuildEnv } from '../utils/build-env';

// Direct `import.meta.env.NG_APP_*` access so @ngx-env inlines the value into the
// browser bundle (an aliased `import.meta.env` is NOT replaced → '' → maps disabled).
// readBuildEnv keeps the SSR build safe, where import.meta.env is undefined. See build-env.ts.
const GOOGLE_MAPS_API_KEY = readBuildEnv(() => import.meta.env.NG_APP_GOOGLE_MAPS_API_KEY) ?? '';

/**
 * Lazily injects the Google Maps JS SDK (`places` library) exactly once and
 * shares the load across every caller. Returns `false` when no API key is
 * configured or the script fails to load, so callers can degrade gracefully.
 */
@Injectable({ providedIn: 'root' })
export class GoogleMapsLoaderService {
  private loaded = signal(false);
  private loading = signal(false);

  async load(): Promise<boolean> {
    if (this.loaded()) return true;
    if (this.loading()) {
      // Wait for the in-flight load to settle.
      while (this.loading()) {
        await new Promise((r) => setTimeout(r, 50));
      }
      return this.loaded();
    }

    if (!GOOGLE_MAPS_API_KEY) {
      console.warn('[GoogleMapsLoader] No NG_APP_GOOGLE_MAPS_API_KEY env var set');
      return false;
    }

    this.loading.set(true);

    return new Promise((resolve) => {
      const existing = document.querySelector('script[data-google-maps]') as HTMLScriptElement | null;
      if (existing) {
        existing.addEventListener('load', () => {
          this.loaded.set(true);
          this.loading.set(false);
          resolve(true);
        });
        existing.addEventListener('error', () => {
          this.loading.set(false);
          resolve(false);
        });
        return;
      }

      const script = document.createElement('script');
      script.src = `https://maps.googleapis.com/maps/api/js?key=${GOOGLE_MAPS_API_KEY}&libraries=places`;
      script.async = true;
      script.defer = true;
      script.dataset['googleMaps'] = 'true';

      script.addEventListener('load', () => {
        this.loaded.set(true);
        this.loading.set(false);
        resolve(true);
      });
      script.addEventListener('error', () => {
        this.loading.set(false);
        resolve(false);
      });

      document.head.appendChild(script);
    });
  }

  isLoaded(): boolean {
    return this.loaded();
  }
}
