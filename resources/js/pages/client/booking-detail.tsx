import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarDays,
    Clock,
    ImageIcon,
    TimerOff,
} from 'lucide-react';
import { useState } from 'react';
import { BookingStatusBadge } from '@/components/admin/bookings/booking-status-badge';
import { PaymentStatusBadge } from '@/components/admin/payments/payment-status-badge';
import { HoldCountdown } from '@/components/client/hold-countdown';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatHour } from '@/lib/time';
import { book } from '@/routes';
import { checkout } from '@/routes/bookings';
import { index } from '@/routes/bookings';
import type { Booking, PaymentStatus } from '@/types';

type BookingDetailProps = {
    booking: { data: Booking };
    expired: boolean;
};

function formatLongDate(value: string): string {
    return new Date(`${value}T00:00:00`).toLocaleDateString(undefined, {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
}

function formatTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleTimeString(undefined, {
        hour: 'numeric',
        minute: '2-digit',
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
        <div className="flex justify-between gap-4 py-2 text-sm">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="text-right font-medium">{children}</dd>
        </div>
    );
}

export default function BookingDetail({
    booking,
    expired: initiallyExpired,
}: BookingDetailProps) {
    const data = booking.data;
    const [expired, setExpired] = useState(initiallyExpired);

    const isPending = data.status === 'pending_payment';

    return (
        <>
            <Head title={data.booking_reference} />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6">
                <div>
                    <Link
                        href={index()}
                        className="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        My bookings
                    </Link>
                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="font-mono text-xl font-semibold">
                            {data.booking_reference}
                        </h1>
                        <BookingStatusBadge
                            status={data.status}
                            label={data.status_label}
                        />
                    </div>
                </div>

                {/* Pending-payment hold banner. */}
                {isPending &&
                    (expired ? (
                        <Card className="border-destructive/30 bg-destructive/5">
                            <CardContent className="flex flex-col items-center gap-3 py-6 text-center">
                                <span className="flex size-11 items-center justify-center rounded-full bg-destructive/10 text-destructive">
                                    <TimerOff className="size-5" />
                                </span>
                                <p className="font-medium">
                                    This reservation hold has expired
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    The slot has been released and payment is no
                                    longer available.
                                </p>
                                <Button asChild>
                                    <Link
                                        href={book.url({
                                            query: { court: data.court.slug },
                                        })}
                                    >
                                        Return to Booking
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>
                    ) : (
                        <Card className="border-primary/20 bg-primary/5">
                            <CardContent className="flex flex-col items-center gap-2 py-6 text-center">
                                <p className="font-medium">Payment pending</p>
                                {data.expires_at && (
                                    <>
                                        <p className="text-sm text-muted-foreground">
                                            Complete payment before{' '}
                                            {formatTime(data.expires_at)}
                                        </p>
                                        <HoldCountdown
                                            expiresAt={data.expires_at}
                                            onExpire={() => setExpired(true)}
                                        />
                                    </>
                                )}
                                <Button asChild className="mt-1">
                                    <Link
                                        href={checkout.url({
                                            booking: data.id,
                                        })}
                                    >
                                        Continue Payment
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>
                    ))}

                <div className="grid gap-6 sm:grid-cols-[1.1fr_1fr]">
                    <div className="relative aspect-video w-full overflow-hidden rounded-xl bg-muted">
                        {data.court.image_url ? (
                            <img
                                src={data.court.image_url}
                                alt={data.court.name}
                                className="size-full object-cover"
                            />
                        ) : (
                            <span className="flex size-full items-center justify-center text-muted-foreground">
                                <ImageIcon className="size-10" />
                            </span>
                        )}
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>{data.court.name}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="divide-y divide-border">
                                <DetailRow label="Date">
                                    <span className="flex items-center gap-1.5">
                                        <CalendarDays className="size-4 text-muted-foreground" />
                                        {formatLongDate(data.booking_date)}
                                    </span>
                                </DetailRow>
                                <DetailRow label="Time">
                                    <span className="flex items-center gap-1.5">
                                        <Clock className="size-4 text-muted-foreground" />
                                        {formatHour(data.start_time)} –{' '}
                                        {formatHour(data.end_time)}
                                    </span>
                                </DetailRow>
                                <DetailRow label="Duration">
                                    {data.duration_hours}{' '}
                                    {data.duration_hours === 1
                                        ? 'hour'
                                        : 'hours'}
                                </DetailRow>
                                <DetailRow label="Hourly rate">
                                    {data.hourly_rate_formatted}
                                </DetailRow>
                                <DetailRow label="Total">
                                    <span className="text-base font-semibold text-primary">
                                        {data.total_amount_formatted}
                                    </span>
                                </DetailRow>
                                <DetailRow label="Booking status">
                                    <BookingStatusBadge
                                        status={data.status}
                                        label={data.status_label}
                                    />
                                </DetailRow>
                                <DetailRow label="Payment status">
                                    {data.payment_status ? (
                                        <PaymentStatusBadge
                                            status={
                                                data.payment_status as PaymentStatus
                                            }
                                            label={
                                                data.payment_status_label ?? ''
                                            }
                                        />
                                    ) : (
                                        <span className="text-muted-foreground">
                                            No payment yet
                                        </span>
                                    )}
                                </DetailRow>
                            </dl>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Timeline</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className="divide-y divide-border">
                            <DetailRow label="Booked">
                                {formatTime(data.created_at)}
                            </DetailRow>
                            {data.confirmed_at && (
                                <DetailRow label="Confirmed">
                                    {formatTime(data.confirmed_at)}
                                </DetailRow>
                            )}
                            {data.cancelled_at && (
                                <DetailRow label="Cancelled">
                                    {formatTime(data.cancelled_at)}
                                </DetailRow>
                            )}
                        </dl>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
