/**
 * Extracts a human-readable message from an HttpClient error.
 * Trust-boundary data: the backend error body is `unknown`, so we narrow it
 * safely (no `any`) before reading the `message` field.
 */
export function extractApiErrorMessage(
  err: unknown,
  fallback = 'Something went wrong. Please try again.'
): string {
  if (typeof err !== 'object' || err === null) return fallback;

  // HttpErrorResponse shape: { error: <body>, message, status }
  const httpErr = err as { error?: unknown; message?: unknown };
  const body = httpErr.error;

  if (typeof body === 'string' && body.trim()) return body;

  if (typeof body === 'object' && body !== null) {
    const message = (body as { message?: unknown }).message;
    if (typeof message === 'string' && message.trim()) return message;
    // NestJS validation errors: message can be a string[]
    if (Array.isArray(message) && message.length > 0) {
      return message.filter((m): m is string => typeof m === 'string').join(', ');
    }
  }

  if (typeof httpErr.message === 'string' && httpErr.message.trim()) {
    return httpErr.message;
  }

  return fallback;
}
