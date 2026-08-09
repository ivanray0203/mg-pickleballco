import { Link } from '@inertiajs/react';
import { CalendarDays, Clock } from 'lucide-react';
import { BookingStatusBadge } from '@/components/admin/bookings/booking-status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatHour } from '@/lib/time';
import { show } from '@/routes/admin/bookings';
import type { Booking } from '@/types';

function formatDate(value: string): string {
    return new Date(`${value}T00:00:00`).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

export function BookingCard({ booking }: { booking: Booking }) {
    return (
        <Card>
            <CardContent className="flex flex-col gap-3">
                <div className="flex items-start justify-between gap-2">
                    <div className="grid">
                        <span className="font-mono text-xs text-muted-foreground">
                            {booking.booking_reference}
                        </span>
                        <span className="font-medium">
                            {booking.customer.name}
                        </span>
                    </div>
                    <BookingStatusBadge
                        status={booking.status}
                        label={booking.status_label}
                    />
                </div>

                <div className="grid gap-1 text-sm text-muted-foreground">
                    <span>{booking.court.name}</span>
                    <span className="flex items-center gap-1.5">
                        <CalendarDays className="size-3.5" />
                        {formatDate(booking.booking_date)}
                    </span>
                    <span className="flex items-center gap-1.5">
                        <Clock className="size-3.5" />
                        {formatHour(booking.start_time)} –{' '}
                        {formatHour(booking.end_time)} ({booking.duration_hours}
                        h)
                    </span>
                </div>

                <div className="flex items-center justify-between">
                    <span className="font-semibold">
                        {booking.total_amount_formatted}
                    </span>
                    <Button variant="outline" size="sm" asChild>
                        <Link href={show(booking.id)}>View</Link>
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}
