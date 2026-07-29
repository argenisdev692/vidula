/**
 * Minimal JSON HTTP helper for the handful of Fortify endpoints that speak JSON
 * rather than Inertia (2FA QR/secret/recovery codes, enable/confirm/disable,
 * password confirmation). Inertia v3 removed Axios, so we use the native fetch
 * client and forward Laravel's XSRF-TOKEN cookie as the X-XSRF-TOKEN header.
 */

export class HttpError extends Error {
  constructor(
    public readonly status: number,
    public readonly body: unknown,
  ) {
    super(`HTTP ${status}`);
    this.name = 'HttpError';
  }
}

function readCookie(name: string): string | null {
  const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));
  return match ? decodeURIComponent(match[1]) : null;
}

export async function apiFetch<T = unknown>(
  method: string,
  url: string,
  body?: unknown,
): Promise<T> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  };

  const xsrf = readCookie('XSRF-TOKEN');
  if (xsrf !== null) {
    headers['X-XSRF-TOKEN'] = xsrf;
  }
  if (body !== undefined) {
    headers['Content-Type'] = 'application/json';
  }

  const response = await fetch(url, {
    method,
    headers,
    credentials: 'same-origin',
    // Never follow HTML redirects (e.g. password.confirm → /user/confirm-password
    // or intended → external host). Callers must handle 302/323/401/423 as JSON errors.
    redirect: 'manual',
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });

  if (response.type === 'opaqueredirect' || (response.status >= 300 && response.status < 400)) {
    throw new HttpError(response.status || 302, {
      message: 'Unexpected redirect from the server. Please reload and try again.',
    });
  }

  const text = await response.text();
  let parsed: unknown;

  if (text) {
    try {
      parsed = JSON.parse(text) as unknown;
    } catch {
      const snippet = text.trimStart().slice(0, 48);
      const hint =
        response.status === 429
          ? 'Too many requests — wait a minute and try again.'
          : `Server returned non-JSON (${response.status}).`;
      throw new HttpError(response.status, {
        message: `${hint} Got: ${snippet}${text.length > 48 ? '…' : ''}`,
      });
    }
  }

  if (!response.ok) {
    throw new HttpError(response.status, parsed);
  }

  return parsed as T;
}
