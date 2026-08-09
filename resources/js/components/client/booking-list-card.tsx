import { Link } from '@inertiajs/react';
import { CalendarDays, Clock } from 'lucide-react';
import { BookingStatusBadge } from '@/components/admin/bookings/booking-status-badge';
import { PaymentStatusBadge } from '@/components/admin/payments/payment-status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatHour } from '@/lib/time';
import { show } from '@/routes/bookings';
import type { Booking, PaymentStatus } from '@/types';

function formatDate(value: string): string {
    return new Date(`${value}T00:00:00`).toLocaleDateString(undefined, {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
}

export function BookingListCard({ booking }: { booking: Booking }) {
    return (
        <Card>
            <CardContent className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex flex-col gap-2">
                    <div className="flex flex-wrap items-center gap-2">
                        <h3 className="font-semibold">{booking.court.name}</h3>
                        <BookingStatusBadge
                            status={booking.status}
                            label={booking.status_label}
                        />
                        {booking.payment_status && (
                            <PaymentStatusBadge
                                status={booking.payment_status as PaymentStatus}
                                label={booking.payment_status_label ?? ''}
                            />
                        )}
                    </div>
                    <div className="flex flex-col gap-1 text-sm text-muted-foreground sm:flex-row sm:items-center sm:gap-4">
                        <span className="flex items-center gap-1.5">
                            <CalendarDays className="size-4" />
                            {formatDate(booking.booking_date)}
                        </span>
                        <span className="flex items-center gap-1.5">
                            <Clock className="size-4" />
                            {formatHour(booking.start_time)} –{' '}
                            {formatHour(booking.end_time)} (
                            {booking.duration_hours}
                            {booking.duration_hours === 1 ? ' hr' : ' hrs'})
                        </span>
                    </div>
                    <span className="font-mono text-xs text-muted-foreground">
                        {booking.booking_reference}
                    </span>
                </div>

                <div className="flex items-center justify-between gap-4 sm:flex-col sm:items-end">
                    <span className="text-lg font-semibold text-primary">
                        {booking.total_amount_formatted}
                    </span>
                    <Button variant="outline" size="sm" asChild>
                        <Link href={show.url({ booking: booking.id })}>
                            View Booking
                        </Link>
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}
