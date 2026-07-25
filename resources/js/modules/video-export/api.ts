import { apiFetch } from '@/lib/http';
import type {
    AudioEnhanceMode,
    EnqueueResponse,
    ExportMode,
    AiProvider,
    JobStatusResponse,
    UploadItem,
} from '@/modules/video-export/types';

interface PresignPayload {
    upload_url: string;
    public_url: string;
    key: string;
    headers: Record<string, string>;
    expires_in_seconds: number;
}

export async function presignUpload(file: File): Promise<PresignPayload> {
    const json = await apiFetch<{ data: PresignPayload }>(
        'POST',
        '/video-export/uploads/presign',
        {
            filename: file.name,
            content_type: file.type || 'video/mp4',
            size_bytes: file.size,
        },
    );

    return json.data;
}

export async function putToR2(
    uploadUrl: string,
    file: File,
    headers: Record<string, string>,
    onProgress?: (pct: number) => void,
): Promise<void> {
    await new Promise<void>((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('PUT', uploadUrl, true);
        Object.entries(headers).forEach(([key, value]) => {
            xhr.setRequestHeader(key, value);
        });
        if (!headers['Content-Type'] && !headers['content-type']) {
            xhr.setRequestHeader('Content-Type', file.type || 'application/octet-stream');
        }
        xhr.upload.onprogress = (event: ProgressEvent): void => {
            if (event.lengthComputable && onProgress) {
                onProgress(Math.round((event.loaded / event.total) * 100));
            }
        };
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

export async function uploadSources(items: UploadItem[]): Promise<string[]> {
    const urls: string[] = [];
    for (const item of items) {
        item.status = 'uploading';
        item.progress = 0;
        try {
            const slot = await presignUpload(item.file);
            await putToR2(slot.upload_url, item.file, slot.headers, (pct) => {
                item.progress = pct;
            });
            item.publicUrl = slot.public_url;
            item.status = 'done';
            item.progress = 100;
            urls.push(slot.public_url);
        } catch (error) {
            item.status = 'error';
            item.error = error instanceof Error ? error.message : 'Upload failed';
            throw error;
        }
    }

    return urls;
}

export async function enqueueExport(payload: {
    job_uuid: string;
    mode: ExportMode;
    video_paths: string[];
    silence_threshold_seconds: number;
    audio_enhancement_enabled: boolean;
    audio_enhance_mode: AudioEnhanceMode;
    ai_provider?: AiProvider;
    script_path?: string;
    script_format?: 'pdf' | 'markdown';
}): Promise<EnqueueResponse> {
    const json = await apiFetch<{ data: EnqueueResponse }>('POST', '/video-export', payload);

    return json.data;
}

export async function fetchJobStatus(jobUuid: string): Promise<JobStatusResponse> {
    const json = await apiFetch<{ data: JobStatusResponse }>(
        'GET',
        `/video-export/jobs/${jobUuid}`,
    );

    return json.data;
}
