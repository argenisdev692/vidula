<script setup lang="ts">
/**
 * The single calendar surface for internal scheduling — renders Meeting's own
 * events plus a read-only overlay of existing Appointment events (tagged via
 * `source`), fed by `GET /meetings/calendar-feed`. Uses FullCalendar's
 * built-in JSON-feed event source (`events: { url }`) — confirmed via
 * context7 that it auto-sends `start`/`end` GET params on every range change,
 * so no manual `datesSet`/fetch wiring is needed (KISS).
 */
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';
import type { CalendarOptions, EventClickArg, EventInput } from '@fullcalendar/core';
import type { CalendarEvent } from '@/modules/meeting/types';

const STATUS_COLOR: Record<string, string> = {
    Scheduled: 'var(--accent-primary)',
    Confirmed: 'var(--accent-success)',
    Rescheduled: 'var(--accent-warning)',
    Cancelled: 'var(--accent-error)',
};

function toEventInput(raw: unknown): EventInput {
    const event = raw as CalendarEvent;
    return {
        id: `${event.source}:${event.uuid}`,
        title: event.source === 'appointment' ? `${event.title} (lead)` : event.title,
        start: event.start,
        end: event.end || undefined,
        url: event.url,
        backgroundColor: STATUS_COLOR[event.status ?? ''] ?? 'var(--accent-primary)',
        borderColor: 'transparent',
    };
}

function onEventClick(info: EventClickArg): void {
    info.jsEvent.preventDefault();
    if (info.event.url) {
        router.visit(info.event.url);
    }
}

const options = computed<CalendarOptions>(() => ({
    plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,listMonth',
    },
    height: 'auto',
    events: {
        url: '/meetings/calendar-feed',
        method: 'GET',
    },
    // The backend wraps the array as `{ data: [...] }` (project convention,
    // mirrors AvailabilityCalendarController) — unwrap it for FullCalendar,
    // which expects the raw event array (confirmed via context7).
    eventSourceSuccess: (content: unknown) => (content as { data: unknown[] }).data as EventInput[],
    eventSourceFailure: (error: Error) => {
        // eslint-disable-next-line no-console
        console.error('Failed to load the meeting calendar feed', error);
    },
    eventDataTransform: toEventInput,
    eventClick: onEventClick,
}));
</script>

<template>
    <div class="meeting-calendar">
        <FullCalendar :options="options" />
    </div>
</template>

<style scoped>
.meeting-calendar {
    padding: var(--space-4);
    background: color-mix(in srgb, var(--bg-surface) 60%, transparent);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-2xl);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

.meeting-calendar :deep(.fc) {
    font-family: var(--font-sans);
    color: var(--text-primary);
}

.meeting-calendar :deep(.fc-theme-standard td),
.meeting-calendar :deep(.fc-theme-standard th) {
    border-color: var(--border-subtle);
}

.meeting-calendar :deep(.fc-toolbar-title) {
    font-size: var(--text-lg);
    color: var(--text-primary);
}

.meeting-calendar :deep(.fc-button-primary) {
    background: var(--accent-primary);
    border-color: var(--accent-primary);
}

.meeting-calendar :deep(.fc-daygrid-day-number),
.meeting-calendar :deep(.fc-col-header-cell-cushion) {
    color: var(--text-secondary);
}
</style>
