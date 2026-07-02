import {
  DestroyRef,
  Service,
  Signal,
  computed,
  effect,
  inject,
  resource,
} from '@angular/core';
import { AppointmentsFeatureService } from '../../features/appointments/services/appointments-feature.service';
import { ContactSupportFeatureService } from '../../features/contact-support/services/contact-support-feature.service';
import { RealtimeService } from '../../core/realtime/realtime.service';

/**
 * Socket.IO (namespace, event) pairs that re-pull the header feeds. These MUST match
 * what each NestJS gateway emits — and the namespace the gateway is bound to, because
 * Socket.IO namespaces are isolated:
 *   - AppointmentsGateway  (`namespace: '/appointments'`):  `appointments:created`,
 *     `appointment:deleted`, `appointments:read`, …
 *   - ContactSupportGateway (`namespace: '/contact-support'`): `contact-support:created`,
 *     `contact-support:read`.
 * The `*:read` events clear a badge, so they also warrant a refresh.
 */
const REALTIME_EVENTS = [
  { namespace: '/appointments', event: 'appointments:created' },
  { namespace: '/appointments', event: 'appointment:updated' },
  { namespace: '/appointments', event: 'appointment:deleted' },
  { namespace: '/appointments', event: 'appointment:status_changed' },
  { namespace: '/appointments', event: 'appointments:read' },
  { namespace: '/appointments', event: 'appointments:bulk_deleted' },
  { namespace: '/appointments', event: 'appointments:bulk_restored' },
  { namespace: '/contact-support', event: 'contact-support:created' },
  { namespace: '/contact-support', event: 'contact-support:read' },
] as const;

/** A single row rendered inside a header dropdown. */
export interface HeaderNotification {
  id: string;
  title: string;
  message: string;
  time: string;
  unread: boolean;
}

/** A header bell/envelope dropdown backed by a real resource. */
export interface HeaderFeed {
  key: string;
  label: string;
  icon: string;
  route: string;
  items: HeaderNotification[];
  unreadCount: number;
}

/** The mutable part of a feed — what a loader produces from the API. */
interface FeedData {
  items: HeaderNotification[];
  unreadCount: number;
}

/** Declarative description of one feed: its presentation + how to load it. */
interface HeaderFeedSource {
  key: string;
  label: string;
  icon: string;
  route: string;
  load(): Promise<FeedData>;
}

// Coalesce realtime bursts (bulk ops fan out many events) into one feed reload.
const EVENT_DEBOUNCE_MS = 300;
// Only a short preview is shown in the dropdown; the full list lives behind "View all".
const PREVIEW_LIMIT = 5;
// Appointments expose no server-side isRead filter, so scan a window and count unread client-side.
const APPOINTMENT_SCAN_LIMIT = 100;

const EMPTY: FeedData = { items: [], unreadCount: 0 };

const buildFeed = (source: HeaderFeedSource, data: FeedData): HeaderFeed => ({
  key: source.key,
  label: source.label,
  icon: source.icon,
  route: source.route,
  items: data.items,
  unreadCount: data.unreadCount,
});

const fullName = (first: string, last: string): string => `${first} ${last}`.trim();

function relativeTime(iso: string): string {
  const then = new Date(iso).getTime();
  if (Number.isNaN(then)) return '';
  const minutes = Math.floor((Date.now() - then) / 60_000);
  if (minutes < 1) return 'just now';
  if (minutes < 60) return `${minutes} min ago`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
  const days = Math.floor(hours / 24);
  return `${days} day${days > 1 ? 's' : ''} ago`;
}

/** Map any API row that carries a name + timestamp into a dropdown notification. */
const toItem = (
  row: { id: string; firstName: string; lastName: string; createdAt: string },
  message: string,
  unread = true,
): HeaderNotification => ({
  id: row.id,
  title: fullName(row.firstName, row.lastName),
  message,
  time: relativeTime(row.createdAt),
  unread,
});

/**
 * Feeds the page-header notification dropdowns with real data: new appointments
 * and unread contact-support requests. Singleton resource, so the load runs once
 * on first read; `refresh()` re-runs it (the header triggers this when a dropdown
 * opens). Each feed is declared once in `sources` — add an entry to add a feed.
 */
@Service()
export class HeaderNotificationsService {
  private appointmentsApi = inject(AppointmentsFeatureService);
  private contactSupportApi = inject(ContactSupportFeatureService);
  private realtime = inject(RealtimeService);
  private destroyRef = inject(DestroyRef);

  // Live updates: a Socket.IO push on any tracked event re-pulls the feeds so the
  // bell/envelope counts update in real time without waiting for a dropdown to open.
  private readonly liveEvents = REALTIME_EVENTS.map(({ namespace, event }) =>
    this.realtime.on(namespace, event),
  );

  // Pending coalesced reload (null when idle). A burst of events (e.g. the
  // bulk_deleted / bulk_restored fan-out) collapses into a single refresh.
  private refreshTimer: ReturnType<typeof setTimeout> | null = null;

  constructor() {
    effect(() => {
      // Read EVERY event signal (`.map`, never `.some` — `.some` short-circuits and
      // would drop the un-read signals from the effect's dependency set, so only the
      // first namespace to fire would keep refreshing). The initial run is all-null
      // and skipped: `feedsResource` already does the first load on its own.
      const fired = this.liveEvents.map((sig) => sig() !== null).includes(true);
      if (fired) {
        this.scheduleRefresh();
      }
    });
    // Cancel a pending reload if the service is torn down mid-debounce.
    this.destroyRef.onDestroy(() => this.clearRefreshTimer());
  }

  /** Debounce: reset the window on each event so a burst yields one reload. */
  private scheduleRefresh(): void {
    this.clearRefreshTimer();
    this.refreshTimer = setTimeout(() => {
      this.refreshTimer = null;
      this.refresh();
    }, EVENT_DEBOUNCE_MS);
  }

  private clearRefreshTimer(): void {
    if (this.refreshTimer !== null) {
      clearTimeout(this.refreshTimer);
      this.refreshTimer = null;
    }
  }

  private readonly sources: HeaderFeedSource[] = [
    {
      key: 'appointments',
      label: 'Appointments',
      icon: 'pi pi-bell',
      route: '/appointments',
      load: () => this.loadAppointments(),
    },
    {
      key: 'contact-support',
      label: 'Contact Support',
      icon: 'pi pi-envelope',
      route: '/contact-support',
      load: () => this.loadContactSupport(),
    },
  ];

  private readonly feedsResource = resource({ loader: () => this.loadAll() });

  readonly feeds = computed<HeaderFeed[]>(
    () => this.feedsResource.value() ?? this.sources.map((s) => buildFeed(s, EMPTY)),
  );
  readonly isLoading = this.feedsResource.isLoading;
  readonly error: Signal<unknown> = this.feedsResource.error;

  refresh(): void {
    this.feedsResource.reload();
  }

  private loadAll(): Promise<HeaderFeed[]> {
    return Promise.all(
      this.sources.map(async (source) => {
        try {
          return buildFeed(source, await source.load());
        } catch {
          // Trust-boundary failure: render an empty feed rather than a header error.
          return buildFeed(source, EMPTY);
        }
      }),
    );
  }

  private async loadAppointments(): Promise<FeedData> {
    const res = await this.appointmentsApi.getAll({ page: 1, limit: APPOINTMENT_SCAN_LIMIT });
    const unread = res.data.filter((a) => !a.isRead);
    return {
      unreadCount: unread.length,
      items: unread.slice(0, PREVIEW_LIMIT).map((a) =>
        // 'null' is the API's sentinel for "no project type" (a real string in the union).
        toItem(a, a.projectType !== 'null' ? a.projectType : (a.message ?? 'New appointment')),
      ),
    };
  }

  private async loadContactSupport(): Promise<FeedData> {
    const res = await this.contactSupportApi.getAll({ isRead: 'false', page: 1, limit: PREVIEW_LIMIT });
    return {
      unreadCount: res.total,
      items: res.data.map((c) => toItem(c, c.subject, !c.isRead)),
    };
  }
}
