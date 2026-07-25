/** Formats an ISO date string for table display (YYYY-MM-DD → localized short). */
export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value.includes('T') ? value : `${value}T00:00:00`);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

export function formatMoney(amount: string | number, currency = 'USD'): string {
    const value = typeof amount === 'string' ? Number.parseFloat(amount) : amount;
    if (Number.isNaN(value)) {
        return '—';
    }

    try {
        return new Intl.NumberFormat(undefined, {
            style: 'currency',
            currency,
        }).format(value);
    } catch {
        return `${currency} ${value.toFixed(2)}`;
    }
}
