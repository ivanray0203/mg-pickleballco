import type { SelectOption } from '@/types';

/**
 * Format a whole-hour "HH:00" string as a friendly label, e.g. "6:00 AM".
 */
export function formatHour(time: string): string {
    const hour = Number.parseInt(time.slice(0, 2), 10);
    const period = hour < 12 ? 'AM' : 'PM';
    const twelve = hour % 12 === 0 ? 12 : hour % 12;

    return `${twelve}:00 ${period}`;
}

/**
 * Whole-hour options (00:00–23:00) for time selects, enforcing the core
 * booking rule that bookings only happen on the hour.
 */
export function hourOptions(): SelectOption[] {
    return Array.from({ length: 24 }, (_, hour) => {
        const value = `${String(hour).padStart(2, '0')}:00`;

        return { value, label: formatHour(value) };
    });
}
