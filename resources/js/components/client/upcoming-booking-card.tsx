import { Link } from '@inertiajs/react';
import { CalendarDays, Clock } from 'lucide-react';
import { BookingStatusBadge } from '@/components/admin/bookings/booking-status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatHour } from '@/lib/time';
import { index as bookingsIndex } from '@/routes/bookings';
import type { Booking } from '@/types';

function formatLongDate(value: string): string {
    return new Date(`${value}T00:00:00`).toLocaleDateString(undefined, {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
}

export function UpcomingBookingCard({ booking }: { booking: Booking }) {
    return (
        <Card className="border-primary/20 bg-primary/5">
            <CardContent className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex flex-col gap-2">
                    <div className="flex items-center gap-3">
                        <h3 className="text-lg font-semibold">
                            {booking.court.name}
                        </h3>
                        <BookingStatusBadge
                            status={booking.status}
                            label={booking.status_label}
                        />
                    </div>
                    <div className="flex flex-col gap-1 text-sm text-muted-foreground sm:flex-row sm:items-center sm:gap-4">
                        <span className="flex items-center gap-1.5">
                            <CalendarDays className="size-4" />
                            {formatLongDate(booking.booking_date)}
                        </span>
                        <span className="flex items-center gap-1.5">
                            <Clock className="size-4" />
                            {formatHour(booking.start_time)} –{' '}
                            {formatHour(booking.end_time)}
                        </span>
                    </div>
                </div>

                <Button variant="outline" asChild>
                    <Link href={bookingsIndex()}>View Booking</Link>
                </Button>
            </CardContent>
        </Card>
    );
}
