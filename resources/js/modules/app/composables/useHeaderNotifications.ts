import { computed, type ComputedRef } from 'vue';

/**
 * A single notification row inside a header feed dropdown.
 */
export interface HeaderNotification {
    id: string;
    title: string;
    message: string;
    time: string;
    unread: boolean;
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

/**
 * Header notification feeds. Mirrors the GUIDE Angular `HeaderNotificationsService`
 * shape so the dropdown UI matches 1:1.
 *
 * TODO(backend): replace this static data with a Pinia Colada `useQuery` against
 * the notifications endpoint once it exists. Kept as a composable so swapping the
 * source is a one-file change and the header never touches raw data.
 */
export function useHeaderNotifications(): { feeds: ComputedRef<HeaderFeed[]> } {
    const feeds = computed<HeaderFeed[]>(() => [
        {
            key: 'appointments',
            label: 'Appointments',
            icon: 'pi pi-calendar',
            route: '/appointments',
            unreadCount: 2,
            items: [
                {
                    id: 'a1',
                    title: 'New appointment request',
                    message: 'Sarah Johnson requested a slot for Fri 10:00',
                    time: '5 min ago',
                    unread: true,
                },
                {
                    id: 'a2',
                    title: 'Appointment rescheduled',
                    message: 'Oak St visit moved to next Monday',
                    time: '1 hr ago',
                    unread: true,
                },
            ],
        },
        {
            key: 'support',
            label: 'Contact & Support',
            icon: 'pi pi-envelope',
            route: '/contact-support',
            unreadCount: 1,
            items: [
                {
                    id: 's1',
                    title: 'New support message',
                    message: 'ABC Corp replied to ticket #1042',
                    time: '20 min ago',
                    unread: true,
                },
                {
                    id: 's2',
                    title: 'Ticket closed',
                    message: 'Billing question resolved',
                    time: '3 hr ago',
                    unread: false,
                },
            ],
        },
    ]);

    return { feeds };
}
