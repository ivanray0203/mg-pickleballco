import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { BookingStatusBadge } from '@/components/admin/bookings/booking-status-badge';
import { CancelBookingDialog } from '@/components/admin/bookings/cancel-booking-dialog';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatHour } from '@/lib/time';
import { index } from '@/routes/admin/bookings';
import type { Booking } from '@/types';

type BookingShowProps = {
    booking: { data: Booking };
};

function formatDate(value: string): string {
    return new Date(`${value}T00:00:00`).toLocaleDateString(undefined, {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
}

function formatDateTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

function DetailRow({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <div className="flex flex-col gap-0.5 py-2 sm:flex-row sm:justify-between sm:gap-4">
            <dt className="text-sm text-muted-foreground">{label}</dt>
            <dd className="text-sm font-medium sm:text-right">{children}</dd>
        </div>
    );
}

export default function BookingShow({ booking }: BookingShowProps) {
    const data = booking.data;

    return (
        <>
            <Head title={data.booking_reference} />

            <div className="mx-auto flex w-full max-w-4xl flex-col gap-6">
                <div>
                    <Link
                        href={index()}
                        className="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Back to bookings
                    </Link>
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-3">
                            <h1 className="font-mono text-xl font-semibold">
                                {data.booking_reference}
                            </h1>
                            <BookingStatusBadge
                                status={data.status}
                                label={data.status_label}
                            />
                        </div>
                        {data.is_cancellable && (
                            <CancelBookingDialog booking={data} />
                        )}
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Reservation</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="divide-y divide-border">
                                <DetailRow label="Court">
                                    {data.court.name}
                                    <span className="ml-2 text-muted-foreground">
                                        {data.court.type_label}
                                    </span>
                                </DetailRow>
                                <DetailRow label="Date">
                                    {formatDate(data.booking_date)}
                                </DetailRow>
                                <DetailRow label="Time">
                                    {formatHour(data.start_time)} –{' '}
                                    {formatHour(data.end_time)}
                                </DetailRow>
                                <DetailRow label="Duration">
                                    {data.duration_hours} hour
                                    {data.duration_hours === 1 ? '' : 's'}
                                </DetailRow>
                                <DetailRow label="Created">
                                    {formatDateTime(data.created_at)}
                                </DetailRow>
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Customer &amp; payment</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="divide-y divide-border">
                                <DetailRow label="Customer">
                                    {data.customer.name}
                                </DetailRow>
                                <DetailRow label="Email">
                                    {data.customer.email}
                                </DetailRow>
                                <DetailRow label="Hourly rate">
                                    {data.hourly_rate_formatted}
                                </DetailRow>
                                <DetailRow label="Total">
                                    <span className="text-base font-semibold">
                                        {data.total_amount_formatted}
                                    </span>
                                </DetailRow>
                                <DetailRow label="Payment status">
                                    {data.payment_status ?? '—'}
                                </DetailRow>
                            </dl>
                        </CardContent>
                    </Card>
                </div>

                {(data.confirmed_at ||
                    data.cancelled_at ||
                    data.cancellation_reason) && (
                    <Card>
                        <CardHeader>
                            <CardTitle>History</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="divide-y divide-border">
                                {data.confirmed_at && (
                                    <DetailRow label="Confirmed at">
                                        {formatDateTime(data.confirmed_at)}
                                    </DetailRow>
                                )}
                                {data.cancelled_at && (
                                    <DetailRow label="Cancelled at">
                                        {formatDateTime(data.cancelled_at)}
                                    </DetailRow>
                                )}
                                {data.cancellation_reason && (
                                    <DetailRow label="Cancellation reason">
                                        {data.cancellation_reason}
                                    </DetailRow>
                                )}
                            </dl>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

BookingShow.layout = {
    title: 'Booking details',
};
