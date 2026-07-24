<script setup lang="ts">
/**
 * The single calendar surface for internal scheduling — Meeting events plus a
 * read-only Appointment overlay from `GET /meetings/calendar-feed`. Selecting a
 * slot emits `schedule` (parent opens MeetingFormDialog) instead of navigating
 * to a create page. Colors are source-based (meeting vs lead), not loud status.
 */
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';
import type { CalendarOptions, DateSelectArg, EventClickArg, EventInput } from '@fullcalendar/core';
import { useAuthorization } from '@/modules/auth/composables/useAuthorization';
import { MEETING_DURATION_MINUTES } from '@/modules/meeting/schemas/meetingFormSchema';
import type { CalendarEvent } from '@/modules/meeting/types';

const emit = defineEmits<{
    schedule: [prefill: { starts_at: string }];
    edit: [uuid: string];
}>();

const { hasPermission } = useAuthorization();
const canCreate = computed<boolean>(() => hasPermission('CREATE_MEETINGS'));
const calendarKey = ref(0);

function refresh(): void {
    calendarKey.value += 1;
}

defineExpose({ refresh });

function toEventInput(raw: unknown): EventInput {
    const event = raw as CalendarEvent;
    const isLead = event.source === 'appointment';

    return {
        id: `${event.source}:${event.uuid}`,
        title: isLead ? `${event.title} (lead)` : event.title,
        start: event.start,
        end: event.end || undefined,
        url: event.url,
        classNames: [isLead ? 'fc-event--lead' : 'fc-event--meeting'],
        editable: false,
        extendedProps: {
            source: event.source,
            uuid: event.uuid,
        },
    };
}

function onEventClick(info: EventClickArg): void {
    info.jsEvent.preventDefault();
    const source = info.event.extendedProps.source as string | undefined;
    const uuid = info.event.extendedProps.uuid as string | undefined;

    if (source === 'meeting' && uuid) {
        emit('edit', uuid);
        return;
    }

    if (info.event.url) {
        router.visit(info.event.url);
    }
}

function pad(n: number): string {
    return String(n).padStart(2, '0');
}

function toLocalIso(date: Date): string {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}:00`;
}

function onDateSelect(info: DateSelectArg): void {
    if (!canCreate.value) {
        return;
    }

    const start = info.start;
    // Month select is all-day (midnight → next midnight). Seed a sensible time.
    if (info.allDay) {
        start.setHours(9, 0, 0, 0);
    }

    emit('schedule', { starts_at: toLocalIso(start) });
    info.view.calendar.unselect();
}

const options = computed<CalendarOptions>(() => ({
    plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
    initialView: 'timeGridWeek',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,listMonth',
    },
    height: 'auto',
    selectable: canCreate.value,
    selectMirror: true,
    unselectAuto: true,
    slotDuration: `00:${String(MEETING_DURATION_MINUTES).padStart(2, '0')}:00`,
    snapDuration: `00:${String(MEETING_DURATION_MINUTES).padStart(2, '0')}:00`,
    events: {
        url: '/meetings/calendar-feed',
        method: 'GET',
    },
    eventSourceSuccess: (content: unknown) => (content as { data: unknown[] }).data as EventInput[],
    eventSourceFailure: (error: Error) => {
        // eslint-disable-next-line no-console
        console.error('Failed to load the meeting calendar feed', error);
    },
    eventDataTransform: toEventInput,
    eventClick: onEventClick,
    select: onDateSelect,
}));
</script>

<template>
    <div class="meeting-calendar">
        <div class="meeting-calendar__legend">
            <span class="meeting-calendar__legend-item">
                <span class="meeting-calendar__swatch meeting-calendar__swatch--meeting" aria-hidden="true" />
                Meetings
            </span>
            <span class="meeting-calendar__legend-item">
                <span class="meeting-calendar__swatch meeting-calendar__swatch--lead" aria-hidden="true" />
                Leads (read-only)
            </span>
            <span v-if="canCreate" class="meeting-calendar__hint">Click or drag to schedule ({{ MEETING_DURATION_MINUTES }} min).</span>
        </div>
        <FullCalendar :key="calendarKey" :options="options" />
    </div>
</template>

<style scoped>
.meeting-calendar {
    padding: var(--space-5);
    background: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-2xl);
}

.meeting-calendar__legend {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-3) var(--space-5);
    margin-bottom: var(--space-5);
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.meeting-calendar__legend-item {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
}

.meeting-calendar__swatch {
    width: 8px;
    height: 8px;
    border-radius: var(--radius-full, 99px);
}

.meeting-calendar__swatch--meeting {
    background: var(--accent-primary);
}

.meeting-calendar__swatch--lead {
    background: var(--accent-info);
}

.meeting-calendar__hint {
    margin-left: auto;
    color: var(--text-muted);
}

/* ── Base ─────────────────────────────────────────────────────────────── */
.meeting-calendar :deep(.fc) {
    font-family: var(--font-sans);
    color: var(--text-primary);
    --fc-border-color: var(--border-subtle);
    --fc-page-bg-color: transparent;
    --fc-neutral-bg-color: transparent;
    --fc-neutral-text-color: var(--text-muted);
    --fc-today-bg-color: color-mix(in srgb, var(--accent-primary) 6%, transparent);
    --fc-now-indicator-color: var(--accent-primary);
    --fc-highlight-color: color-mix(in srgb, var(--accent-primary) 10%, transparent);
    --fc-non-business-color: transparent;
    --fc-list-event-hover-bg-color: var(--bg-hover);
}

.meeting-calendar :deep(.fc-theme-standard .fc-scrollgrid),
.meeting-calendar :deep(.fc-theme-standard td),
.meeting-calendar :deep(.fc-theme-standard th) {
    border-color: var(--border-subtle);
}

.meeting-calendar :deep(.fc-scrollgrid) {
    border: none;
}

.meeting-calendar :deep(.fc-scrollgrid-section > td),
.meeting-calendar :deep(.fc-scrollgrid-section > th) {
    border: none;
}

.meeting-calendar :deep(.fc-scrollgrid-sync-table) {
    border-collapse: separate;
    border-spacing: 0;
}

/* Soft vertical dividers only — no heavy grid cage */
.meeting-calendar :deep(.fc-timegrid-col),
.meeting-calendar :deep(.fc-daygrid-day),
.meeting-calendar :deep(.fc-col-header-cell) {
    border-inline-color: var(--border-subtle) !important;
    border-block-color: transparent !important;
}

.meeting-calendar :deep(.fc-timegrid-slot) {
    border-bottom: 1px solid var(--border-subtle);
    height: 2.5rem;
}

.meeting-calendar :deep(.fc-timegrid-slot-minor) {
    border-bottom-style: dotted;
    border-bottom-color: color-mix(in srgb, var(--border-subtle) 60%, transparent);
}

.meeting-calendar :deep(.fc-timegrid-axis),
.meeting-calendar :deep(.fc-timegrid-divider) {
    border-color: transparent;
}

/* ── Column headers (Sun 7/19 …) ──────────────────────────────────────── */
.meeting-calendar :deep(.fc-col-header) {
    background: transparent;
}

.meeting-calendar :deep(.fc-col-header-cell) {
    background: transparent !important;
    border-bottom: 1px solid var(--border-subtle) !important;
    border-top: none !important;
    padding: var(--space-2) 0 var(--space-3);
}

.meeting-calendar :deep(.fc-col-header-cell-cushion) {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    padding: var(--space-1) var(--space-2);
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
    letter-spacing: 0.02em;
    text-transform: uppercase;
    color: var(--text-muted);
    text-decoration: none;
}

.meeting-calendar :deep(.fc-col-header-cell.fc-day-today .fc-col-header-cell-cushion) {
    color: var(--accent-primary);
}

/* ── Today column / cell ──────────────────────────────────────────────── */
.meeting-calendar :deep(.fc-day-today) {
    background: color-mix(in srgb, var(--accent-primary) 5%, transparent) !important;
}

.meeting-calendar :deep(.fc-timegrid-col.fc-day-today),
.meeting-calendar :deep(.fc-daygrid-day.fc-day-today) {
    background: color-mix(in srgb, var(--accent-primary) 5%, transparent) !important;
}

.meeting-calendar :deep(.fc-daygrid-day-number) {
    color: var(--text-secondary);
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    padding: var(--space-2);
    text-decoration: none;
}

.meeting-calendar :deep(.fc-day-today .fc-daygrid-day-number) {
    color: var(--accent-primary);
    background: color-mix(in srgb, var(--accent-primary) 12%, transparent);
    border-radius: var(--radius-full, 99px);
    width: 1.75rem;
    height: 1.75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}

.meeting-calendar :deep(.fc-day-other .fc-daygrid-day-number) {
    color: var(--text-disabled);
}

/* ── Toolbar ──────────────────────────────────────────────────────────── */
.meeting-calendar :deep(.fc-toolbar) {
    margin-bottom: var(--space-5);
    gap: var(--space-3);
}

.meeting-calendar :deep(.fc-toolbar-title) {
    font-size: var(--text-lg);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
    letter-spacing: -0.01em;
}

.meeting-calendar :deep(.fc-button-primary) {
    background: transparent;
    border: 1px solid var(--border-subtle);
    color: var(--text-secondary);
    box-shadow: none;
    font-weight: var(--font-medium);
    font-size: var(--text-sm);
    border-radius: var(--radius-md);
    padding: var(--space-2) var(--space-3);
    text-transform: capitalize;
    transition: background var(--transition), color var(--transition), border-color var(--transition);
}

.meeting-calendar :deep(.fc-button-primary:hover) {
    background: var(--bg-hover);
    border-color: var(--border-default);
    color: var(--text-primary);
}

.meeting-calendar :deep(.fc-button-primary:not(:disabled).fc-button-active),
.meeting-calendar :deep(.fc-button-primary:not(:disabled):active) {
    background: color-mix(in srgb, var(--accent-primary) 10%, transparent);
    border-color: color-mix(in srgb, var(--accent-primary) 35%, transparent);
    color: var(--accent-primary);
}

.meeting-calendar :deep(.fc-button-primary:focus) {
    box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent-primary) 20%, transparent);
}

.meeting-calendar :deep(.fc-button-group > .fc-button) {
    border-radius: 0;
}

.meeting-calendar :deep(.fc-button-group > .fc-button:first-child) {
    border-radius: var(--radius-md) 0 0 var(--radius-md);
}

.meeting-calendar :deep(.fc-button-group > .fc-button:last-child) {
    border-radius: 0 var(--radius-md) var(--radius-md) 0;
}

/* ── Selection / now ──────────────────────────────────────────────────── */
.meeting-calendar :deep(.fc-highlight) {
    background: color-mix(in srgb, var(--accent-primary) 10%, transparent);
}

.meeting-calendar :deep(.fc-timegrid-now-indicator-line) {
    border-color: var(--accent-primary);
}

.meeting-calendar :deep(.fc-timegrid-now-indicator-arrow) {
    border-top-color: var(--accent-primary);
    border-bottom-color: var(--accent-primary);
}

.meeting-calendar :deep(.fc-timegrid-axis-cushion),
.meeting-calendar :deep(.fc-timegrid-slot-label-cushion) {
    color: var(--text-muted);
    font-size: var(--text-xs);
}

/* ── Events ───────────────────────────────────────────────────────────── */
.meeting-calendar :deep(.fc-event) {
    border: none !important;
    border-radius: var(--radius-sm);
    box-shadow: none;
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
}

.meeting-calendar :deep(.fc-event--meeting) {
    background: color-mix(in srgb, var(--accent-primary) 88%, transparent) !important;
    color: var(--text-on-accent) !important;
}

.meeting-calendar :deep(.fc-event--lead) {
    background: color-mix(in srgb, var(--accent-info) 80%, transparent) !important;
    color: var(--text-on-accent) !important;
}

.meeting-calendar :deep(.fc-list) {
    border: none;
}

.meeting-calendar :deep(.fc-list-day-cushion) {
    background: transparent !important;
    color: var(--text-secondary);
}

.meeting-calendar :deep(.fc-list-event:hover td) {
    background: var(--bg-hover);
}
</style>
