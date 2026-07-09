import { ref } from 'vue';

/**
 * Loads the full message body for a single contact request on demand. The list
 * projection is lean and omits `message`, so the edit dialog fetches it lazily
 * from the JSON branch of `GET /contact-supports/{uuid}` (VIEW_CONTACT_SUPPORTS,
 * a session-guarded web route — never `/api/*`). Kept in a composable so no raw
 * `fetch` leaks into the page component.
 */
export function useContactSupportMessage(): {
    loading: import('vue').Ref<boolean>;
    fetchMessage: (uuid: string) => Promise<string>;
} {
    const loading = ref<boolean>(false);

    async function fetchMessage(uuid: string): Promise<string> {
        loading.value = true;
        try {
            const response = await fetch(`/contact-supports/${uuid}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) {
                return '';
            }
            const payload = (await response.json()) as { data?: { message?: string } };
            return payload.data?.message ?? '';
        } catch {
            return '';
        } finally {
            loading.value = false;
        }
    }

    return { loading, fetchMessage };
}
