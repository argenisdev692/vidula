/**
 * Portfolio media: presign → direct browser PUT to Cloudflare R2, then submit
 * object keys on create/update (never multipart through Laravel).
 */
import { apiFetch } from '@/lib/http';

export type PortfolioMediaKind = 'cover' | 'video';

interface PresignPayload {
    upload_url: string;
    key: string;
    headers: Record<string, string>;
    expires_in_seconds: number;
}

export async function presignPortfolioMedia(
    kind: PortfolioMediaKind,
    file: File,
): Promise<PresignPayload> {
    const json = await apiFetch<{ data: PresignPayload }>(
        'POST',
        '/portfolios/uploads/presign',
        {
            kind,
            filename: file.name,
            content_type: file.type || (kind === 'cover' ? 'image/jpeg' : 'video/mp4'),
            size_bytes: file.size,
        },
    );

    return json.data;
}

export async function putPortfolioMediaToR2(
    uploadUrl: string,
    file: File,
    headers: Record<string, string>,
): Promise<void> {
    await new Promise<void>((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('PUT', uploadUrl, true);

        const safeHeaders = Object.fromEntries(
            Object.entries(headers).filter(([key]) => {
                const lower = key.toLowerCase();
                return lower === 'content-type' || lower.startsWith('x-amz-');
            }),
        );

        Object.entries(safeHeaders).forEach(([key, value]) => {
            xhr.setRequestHeader(key, value);
        });
        if (!safeHeaders['Content-Type'] && !safeHeaders['content-type']) {
            xhr.setRequestHeader('Content-Type', file.type || 'application/octet-stream');
        }

        xhr.onload = (): void => {
            if (xhr.status >= 200 && xhr.status < 300) {
                resolve();
            } else {
                reject(new Error(`Upload failed (${xhr.status})`));
            }
        };
        xhr.onerror = (): void => reject(new Error('Upload network error'));
        xhr.send(file);
    });
}

/** Presign + PUT; returns the R2 object key to persist on the portfolio row. */
export async function uploadPortfolioMedia(kind: PortfolioMediaKind, file: File): Promise<string> {
    const slot = await presignPortfolioMedia(kind, file);
    await putPortfolioMediaToR2(slot.upload_url, file, slot.headers);

    return slot.key;
}
