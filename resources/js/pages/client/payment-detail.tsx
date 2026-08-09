import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { PaymentStatusBadge } from '@/components/admin/payments/payment-status-badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatHour } from '@/lib/time';
import { show as bookingShow } from '@/routes/bookings';
import { index } from '@/routes/payments';
import type { Payment } from '@/types';

type PaymentDetailProps = {
    payment: { data: Payment };
};

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
        <div className="flex justify-between gap-4 py-2 text-sm">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="text-right font-medium">{children}</dd>
        </div>
    );
}

export default function PaymentDetail({ payment }: PaymentDetailProps) {
    const data = payment.data;

    return (
        <>
            <Head title={data.payment_reference} />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-6">
                <div>
                    <Link
                        href={index()}
                        className="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Payments
                    </Link>
                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="font-mono text-xl font-semibold">
                            {data.payment_reference}
                        </h1>
                        <PaymentStatusBadge
                            status={data.status}
                            label={data.status_label}
                        />
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Payment</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className="divide-y divide-border">
                            <DetailRow label="Amount">
                                <span className="text-base font-semibold text-primary">
                                    {data.amount_formatted}
                                </span>
                            </DetailRow>
                            <DetailRow label="Method">
                                {data.payment_method_label}
                            </DetailRow>
                            <DetailRow label="Created">
                                {formatDateTime(data.created_at)}
                            </DetailRow>
                            <DetailRow label="Paid at">
                                {formatDateTime(data.paid_at)}
                            </DetailRow>
                        </dl>
                    </CardContent>
                </Card>

                {data.booking && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Booking</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="divide-y divide-border">
                                <DetailRow label="Reference">
                                    <Link
                                        href={bookingShow.url({
                                            booking: data.booking.id,
                                        })}
                                        className="font-mono text-primary hover:underline"
                                    >
                                        {data.booking.reference}
                                    </Link>
                                </DetailRow>
                                <DetailRow label="Court">
                                    {data.booking.court}
                                </DetailRow>
                                <DetailRow label="Date">
                                    {data.booking.date}
                                </DetailRow>
                                <DetailRow label="Time">
                                    {formatHour(data.booking.start_time)} –{' '}
                                    {formatHour(data.booking.end_time)}
                                </DetailRow>
                            </dl>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}
