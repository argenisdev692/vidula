import { computed, onUnmounted, type ComputedRef } from 'vue';
import { useQuery, useQueryCache } from '@pinia/colada';
import { apiFetch } from '@/lib/http';
import { useAuthorization } from '@/modules/auth/composables/useAuthorization';

/**
 * A single notification row inside a header feed dropdown.
 */
export interface HeaderNotification {
    id: string;
    title: string;
    message: string;
    time: string;
    unread: boolean;
    /** The record's own detail page — distinct from the feed's "view all" `route`. */
    href: string;
}

/**
 * One bell in the header top-bar: a labelled feed with an icon, an unread badge
 * count and its dropdown items. `route` is the "view all" destination.
 */
export interface HeaderFeed {
    key: string;
    label: string;
    icon: string;
    route: string;
    unreadCount: number;
    items: HeaderNotification[];
}

interface NotificationsResponse {
    unread_count: number;
    items: {
        uuid: string;
        title: string;
        message: string;
        time: string;
        unread: boolean;
        href: string;
    }[];
}

interface FeedSource {
    key: string;
    label: string;
    icon: string;
    route: string;
    endpoint: string;
    channel: string;
    eventName: string;
    permission: string;
    markReadUrl: (uuid: string) => string;
    markAllUrl: string;
}

const FEED_SOURCES: FeedSource[] = [
    {
        key: 'appointments',
        label: 'Appointments',
        icon: 'pi pi-calendar',
        route: '/appointments',
        endpoint: '/appointments/notifications',
        channel: 'notifications.appointments',
        eventName: '.appointment.created',
        permission: 'VIEW_ANY_APPOINTMENTS',
        markReadUrl: (uuid) => `/appointments/${uuid}/read`,
        markAllUrl: '/appointments/mark-all-read',
    },
    {
        key: 'support',
        label: 'Contact & Support',
        icon: 'pi pi-envelope',
        route: '/contact-supports',
        endpoint: '/contact-supports/notifications',
        channel: 'notifications.contact-supports',
        eventName: '.contact-support.created',
        permission: 'VIEW_ANY_CONTACT_SUPPORTS',
        markReadUrl: (uuid) => `/contact-supports/${uuid}/read`,
        markAllUrl: '/contact-supports/mark-all-read',
    },
];

function toItems(response: NotificationsResponse | undefined): HeaderNotification[] {
    return (response?.items ?? []).map((item) => ({
        id: item.uuid,
        title: item.title,
        message: item.message,
        time: item.time,
        unread: item.unread,
        href: item.href,
    }));
}

/**
 * Header notification feeds — Appointments and Contact & Support, kept as two
 * separate bells by product decision. Backed by real DB reads plus Reverb
 * push for live updates (new public submissions arrive without a manual
 * refresh).
 *
 * Uses Pinia Colada `useQuery` rather than an Inertia partial reload: unlike
 * the page-scoped tables (see `activity-logs/Index.vue`'s documented
 * Inertia-first convention), this widget lives in the persistent app shell
 * and must stay fresh across page navigations — exactly the case that
 * convention doesn't cover.
 */
export function useHeaderNotifications(): {
    feeds: ComputedRef<HeaderFeed[]>;
    markItemRead: (feedKey: string, uuid: string) => void;
    markAllRead: (feedKey: string) => void;
} {
    const { hasPermission } = useAuthorization();
    const queryCache = useQueryCache();

    const queries = FEED_SOURCES.map((source) => ({
        source,
        query: useQuery<NotificationsResponse>({
            key: ['header-notifications', source.key],
            query: () => apiFetch<NotificationsResponse>('GET', source.endpoint),
            enabled: computed(() => hasPermission(source.permission)),
        }),
    }));

    const feeds = computed<HeaderFeed[]>(() =>
        queries.map(({ source, query }) => ({
            key: source.key,
            label: source.label,
            icon: source.icon,
            route: source.route,
            unreadCount: query.data.value?.unread_count ?? 0,
            items: toItems(query.data.value),
        })),
    );

    // Live push: a no-op when Reverb isn't configured (`window.Echo` unset) or
    // the user lacks the feed's permission — mirrors useAiGenerationProgress.
    const subscribedChannels: string[] = [];

    for (const source of FEED_SOURCES) {
        if (!window.Echo || !hasPermission(source.permission)) {
            continue;
        }
        window.Echo.private(source.channel).listen(source.eventName, () => {
            void queryCache.invalidateQueries({ key: ['header-notifications', source.key] });
        });
        subscribedChannels.push(source.channel);
    }

    onUnmounted(() => {
        for (const channel of subscribedChannels) {
            window.Echo?.leave(channel);
        }
    });

    function findSource(feedKey: string): FeedSource {
        const source = FEED_SOURCES.find((candidate) => candidate.key === feedKey);
        if (!source) {
            throw new Error(`Unknown notification feed: ${feedKey}`);
        }
        return source;
    }

    function markItemRead(feedKey: string, uuid: string): void {
        const source = findSource(feedKey);
        void apiFetch('PATCH', source.markReadUrl(uuid)).then(() =>
            queryCache.invalidateQueries({ key: ['header-notifications', feedKey] }),
        );
    }

    function markAllRead(feedKey: string): void {
        const source = findSource(feedKey);
        void apiFetch('POST', source.markAllUrl).then(() =>
            queryCache.invalidateQueries({ key: ['header-notifications', feedKey] }),
        );
    }

    return { feeds, markItemRead, markAllRead };
}
