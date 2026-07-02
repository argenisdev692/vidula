/**
 * Reads a build-time `NG_APP_*` env var safely across the browser and SSR builds.
 *
 * `@ngx-env/builder` statically replaces the EXACT `import.meta.env.NG_APP_*` member
 * access in the BROWSER bundle (the literal becomes the string value). In the
 * SSR / prerender build that replacement is NOT applied and `import.meta.env` is
 * `undefined`, so a direct access throws. The thunk defers evaluation so esbuild can
 * still inline the literal for the browser, while the try/catch keeps the server safe.
 *
 * ALWAYS pass a direct member access — never an aliased/bare `import.meta.env`:
 *
 *   readBuildEnv(() => import.meta.env.NG_APP_API_BASE_URL)
 */
export function readBuildEnv(read: () => string | undefined): string | undefined {
  try {
    return read();
  } catch {
    return undefined;
  }
}
