import type { ProductLifecycleStatus, ProductType } from '../types';

const TYPE_LABELS: Record<ProductType, string> = {
    classroom: 'Classroom',
    video_tutorial: 'Video tutorial',
    video_pill: 'Video pill',
};

export function productTypeLabel(type: ProductType): string {
    return TYPE_LABELS[type] ?? type;
}

export function lifecycleTone(status: ProductLifecycleStatus): 'success' | 'primary' | 'muted' {
    if (status === 'published') {
        return 'success';
    }
    if (status === 'draft') {
        return 'primary';
    }
    return 'muted';
}

/** Renders the stored decimal price with its ISO currency code. */
export function formatPrice(price: string | number, currency: string): string {
    const amount = typeof price === 'number' ? price : Number.parseFloat(price);

    if (Number.isNaN(amount)) {
        return '—';
    }

    return `${amount.toFixed(2)} ${currency.toUpperCase()}`;
}
