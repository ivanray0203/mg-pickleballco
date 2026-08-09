import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { PaymentStatusBadge } from '@/components/admin/payments/payment-status-badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatHour } from '@/lib/time';
import { show as bookingShow } from '@/routes/admin/bookings';
import { index } from '@/routes/admin/payments';
import type { Payment } from '@/types';

type PaymentShowProps = {
    payment: { data: Payment };
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

export default function PaymentShow({ payment }: PaymentShowProps) {
    const data = payment.data;
    const booking = data.booking;

    return (
        <>
            <Head title={data.payment_reference} />

            <div className="mx-auto flex w-full max-w-4xl flex-col gap-6">
                <div>
                    <Link
                        href={index()}
                        className="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Back to payments
                    </Link>
                    <div className="flex items-center gap-3">
                        <h1 className="font-mono text-xl font-semibold">
                            {data.payment_reference}
                        </h1>
                        <PaymentStatusBadge
                            status={data.status}
                            label={data.status_label}
                        />
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Payment</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="divide-y divide-border">
                                <DetailRow label="Amount">
                                    <span className="text-base font-semibold">
                                        {data.amount_formatted}
                                    </span>
                                </DetailRow>
                                <DetailRow label="Method">
                                    {data.payment_method_label}
                                </DetailRow>
                                <DetailRow label="Provider">
                                    {data.provider_label}
                                </DetailRow>
                                <DetailRow label="Provider reference">
                                    <span className="font-mono text-xs">
                                        {data.provider_reference ?? '—'}
                                    </span>
                                </DetailRow>
                                <DetailRow label="Created">
                                    {formatDateTime(data.created_at)}
                                </DetailRow>
                                <DetailRow label="Paid at">
                                    {formatDateTime(data.paid_at)}
                                </DetailRow>
                                {data.failed_at && (
                                    <DetailRow label="Failed at">
                                        {formatDateTime(data.failed_at)}
                                    </DetailRow>
                                )}
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Booking &amp; customer</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {booking ? (
                                <dl className="divide-y divide-border">
                                    <DetailRow label="Booking">
                                        <Link
                                            href={bookingShow(booking.id)}
                                            className="font-mono text-xs text-primary hover:underline"
                                        >
                                            {booking.reference}
                                        </Link>
                                    </DetailRow>
                                    <DetailRow label="Court">
                                        {booking.court}
                                    </DetailRow>
                                    <DetailRow label="Date">
                                        {formatDate(booking.date)}
                                    </DetailRow>
                                    <DetailRow label="Time">
                                        {formatHour(booking.start_time)} –{' '}
                                        {formatHour(booking.end_time)}
                                    </DetailRow>
                                    <DetailRow label="Customer">
                                        {booking.customer.name}
                                    </DetailRow>
                                    <DetailRow label="Email">
                                        {booking.customer.email}
                                    </DetailRow>
                                </dl>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    Booking details unavailable.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

PaymentShow.layout = {
    title: 'Payment details',
};
