/**
 * Date helpers shared across pages and form components.
 *
 * `toLocalIsoDate` formats a `Date` to a `YYYY-MM-DD` string using its LOCAL
 * calendar fields — never `toISOString()`, which serialises in UTC and shifts
 * the day for any user east of UTC (a date picked as "Jul 10" becomes "Jul 09"
 * in the query string). This mirrors the internal formatter DateField already
 * uses, kept in one place so the two can never drift.
 */
export function toLocalIsoDate(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}
