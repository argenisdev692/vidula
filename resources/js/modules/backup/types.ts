/**
 * Backup module — snake_case interfaces mirroring the backend
 * {@link \Modules\Backup\Infrastructure\Http\Presenters\BackupPresenter} output.
 * Backups are ZIP files on a storage disk (no DB rows); `id` is the file
 * basename and is what every action route uses to re-resolve the archive.
 */

export interface BackupItem {
    id: string;
    path: string;
    disk: string;
    date: string;
    size_bytes: number;
    size_human: string;
}

export interface BackupStatus {
    reachable: boolean;
    healthy: boolean;
    backup_count: number;
    newest_at: string | null;
    used_storage_bytes: number;
    used_storage_human: string;
    disk: string;
    backup_name: string;
}
