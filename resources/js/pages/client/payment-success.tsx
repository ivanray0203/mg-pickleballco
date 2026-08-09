import { Head, Link, router } from '@inertiajs/react';
import { CircleCheck, LoaderCircle, TriangleAlert } from 'lucide-react';
import { useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatHour } from '@/lib/time';
import { book } from '@/routes';
import { show as bookingShow } from '@/routes/bookings';
import { index as courtsIndex } from '@/routes/courts';
import type { Booking } from '@/types';

type PaymentSuccessProps = {
    booking: { data: Booking };
    confirmed: boolean;
};

function formatLongDate(value: string): string {
    return new Date(`${value}T00:00:00`).toLocaleDateString(undefined, {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
}

export default function PaymentSuccess({
    booking,
    confirmed,
}: PaymentSuccessProps) {
    const data = booking.data;
    // Still awaiting the authoritative webhook confirmation.
    const awaiting = !confirmed && data.status === 'pending_payment';

    // Poll the server for the real status while we wait for the webhook.
    useEffect(() => {
        if (!awaiting) {
            return;
        }

        const interval = setInterval(() => {
            router.reload({ only: ['confirmed', 'booking'] });
        }, 3000);

        return () => clearInterval(interval);
    }, [awaiting]);

    if (confirmed) {
        return (
            <>
                <Head title="Booking Confirmed" />
                <div className="mx-auto flex w-full max-w-lg flex-col gap-6">
                    <Card className="border-emerald-500/30 bg-emerald-500/5">
                        <CardContent className="flex flex-col items-center gap-4 py-8 text-center">
                            <span className="flex size-14 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600">
                                <CircleCheck className="size-7" />
                            </span>
                            <h1 className="text-2xl font-semibold">
                                Booking Confirmed
                            </h1>

                            <dl className="w-full divide-y divide-border text-sm">
                                <div className="flex justify-between py-2">
                                    <dt className="text-muted-foreground">
                                        Court
                                    </dt>
                                    <dd className="font-medium">
                                        {data.court.name}
                                    </dd>
                                </div>
                                <div className="flex justify-between py-2">
                                    <dt className="text-muted-foreground">
                                        Date
                                    </dt>
                                    <dd className="font-medium">
                                        {formatLongDate(data.booking_date)}
                                    </dd>
                                </div>
                                <div className="flex justify-between py-2">
                                    <dt className="text-muted-foreground">
                                        Time
                                    </dt>
                                    <dd className="font-medium">
                                        {formatHour(data.start_time)} –{' '}
                                        {formatHour(data.end_time)}
                                    </dd>
                                </div>
                                <div className="flex justify-between py-2">
                                    <dt className="text-muted-foreground">
                                        Reference
                                    </dt>
                                    <dd className="font-mono font-medium">
                                        {data.booking_reference}
                                    </dd>
                                </div>
                                <div className="flex justify-between py-2">
                                    <dt className="text-muted-foreground">
                                        Amount
                                    </dt>
                                    <dd className="font-medium">
                                        {data.total_amount_formatted}
                                    </dd>
                                </div>
                                <div className="flex justify-between py-2">
                                    <dt className="text-muted-foreground">
                                        Payment
                                    </dt>
                                    <dd className="font-medium">GCash</dd>
                                </div>
                            </dl>

                            <div className="flex w-full flex-col gap-2">
                                <Button asChild className="w-full">
                                    <Link
                                        href={bookingShow.url({
                                            booking: data.id,
                                        })}
                                    >
                                        View Booking
                                    </Link>
                                </Button>
                                <Button
                                    asChild
                                    variant="outline"
                                    className="w-full"
                                >
                                    <Link href={courtsIndex()}>
                                        Book Another Court
                                    </Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </>
        );
    }

    if (awaiting) {
        return (
            <>
                <Head title="Confirming Payment" />
                <div className="mx-auto flex w-full max-w-lg flex-col gap-6">
                    <Card>
                        <CardContent className="flex flex-col items-center gap-4 py-12 text-center">
                            <LoaderCircle className="size-8 animate-spin text-primary" />
                            <div className="grid gap-1">
                                <h1 className="text-xl font-semibold">
                                    We're confirming your payment
                                </h1>
                                <p className="max-w-sm text-sm text-muted-foreground">
                                    This can take a few moments. This page
                                    updates automatically once your payment is
                                    verified.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </>
        );
    }

    // Not confirmed and no longer pending — the hold lapsed or payment failed.
    return (
        <>
            <Head title="Payment Incomplete" />
            <div className="mx-auto flex w-full max-w-lg flex-col gap-6">
                <Card className="border-destructive/30 bg-destructive/5">
                    <CardContent className="flex flex-col items-center gap-4 py-10 text-center">
                        <span className="flex size-12 items-center justify-center rounded-full bg-destructive/10 text-destructive">
                            <TriangleAlert className="size-6" />
                        </span>
                        <div className="grid gap-1">
                            <h1 className="text-xl font-semibold">
                                Payment not completed
                            </h1>
                            <p className="max-w-sm text-sm text-muted-foreground">
                                Your reservation was not confirmed. Please start
                                a new booking.
                            </p>
                        </div>
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
            </div>
        </>
    );
}
