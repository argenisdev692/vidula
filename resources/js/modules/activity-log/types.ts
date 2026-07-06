/**
 * Activity Log module — snake_case interfaces mirroring the backend
 * {@link \Modules\ActivityLog\Infrastructure\Http\Presenters\ActivityLogPresenter}
 * projections. The trail is READ-ONLY: there is no create/update/delete shape,
 * only list rows, a richer detail row and the applied filter echo.
 */

/** A row in the activity-log DataTable (lean list projection). */
export interface ActivityLog {
    id: number;
    log_name: string | null;
    description: string;
    event: string | null;
    subject_type: string | null;
    subject_id: string | number | null;
    causer_id: string | number | null;
    causer_label: string | null;
    created_at: string | null;
}

/** The detail projection — adds the JSON blobs and the causer type. */
export interface ActivityLogDetail extends ActivityLog {
    causer_type: string | null;
    properties: Record<string, unknown> | null;
    attribute_changes: Record<string, unknown> | null;
    updated_at: string | null;
}

/** Filters echoed back by the server (mirrors ActivityLogFilterData). */
export interface ActivityLogFilters {
    search: string | null;
    event: string | null;
    log_name: string | null;
    causer_id: string | null;
    date_from: string | null;
    date_to: string | null;
}

/** The full reactive query state driving the server-side DataTable. */
export interface ActivityLogQuery extends ActivityLogFilters {
    page: number;
    per_page: number;
    sort_dir: 'asc' | 'desc';
}
