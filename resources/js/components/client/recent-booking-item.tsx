import { BookingStatusBadge } from '@/components/admin/bookings/booking-status-badge';
import { formatHour } from '@/lib/time';
import type { Booking } from '@/types';

function formatDate(value: string): string {
    return new Date(`${value}T00:00:00`).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

export function RecentBookingItem({ booking }: { booking: Booking }) {
    return (
        <li className="flex items-center justify-between gap-3 py-3">
            <div className="grid gap-0.5">
                <span className="text-sm font-medium">
                    {booking.court.name}
                </span>
                <span className="text-xs text-muted-foreground">
                    {formatDate(booking.booking_date)} ·{' '}
                    {formatHour(booking.start_time)} –{' '}
                    {formatHour(booking.end_time)}
                </span>
            </div>
            <BookingStatusBadge
                status={booking.status}
                label={booking.status_label}
            />
        </li>
    );
}
