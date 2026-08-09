import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarDays,
    Clock,
    LoaderCircle,
    Smartphone,
} from 'lucide-react';
import { useState } from 'react';
import { HoldCountdown } from '@/components/client/hold-countdown';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatHour } from '@/lib/time';
import { pay } from '@/routes/bookings';
import { show as bookingShow } from '@/routes/bookings';
import type { Booking } from '@/types';

type CheckoutProps = {
    booking: { data: Booking };
};

function formatLongDate(value: string): string {
    return new Date(`${value}T00:00:00`).toLocaleDateString(undefined, {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
}

export default function Checkout({ booking }: CheckoutProps) {
    const data = booking.data;
    const [expired, setExpired] = useState(false);
    const { post, processing } = useForm({});

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        post(pay.url({ booking: data.id }));
    };

    return (
        <>
            <Head title="Checkout" />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-6">
                <div>
                    <Link
                        href={bookingShow.url({ booking: data.id })}
                        className="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Back to booking
                    </Link>
                    <h1 className="text-2xl font-semibold">Checkout</h1>
                </div>

                {data.expires_at && (
                    <Card className="border-primary/20 bg-primary/5">
                        <CardContent className="flex items-center justify-between gap-3 py-4">
                            <span className="text-sm text-muted-foreground">
                                Complete payment within
                            </span>
                            <HoldCountdown
                                expiresAt={data.expires_at}
                                onExpire={() => setExpired(true)}
                            />
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center justify-between gap-3">
                            <span>{data.court.name}</span>
                            <span className="font-mono text-sm font-normal text-muted-foreground">
                                {data.booking_reference}
                            </span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-3 text-sm">
                        <div className="flex items-center gap-2">
                            <CalendarDays className="size-4 text-muted-foreground" />
                            {formatLongDate(data.booking_date)}
                        </div>
                        <div className="flex items-center gap-2">
                            <Clock className="size-4 text-muted-foreground" />
                            {formatHour(data.start_time)} –{' '}
                            {formatHour(data.end_time)} ({data.duration_hours}{' '}
                            {data.duration_hours === 1 ? 'hour' : 'hours'})
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Payment method</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center gap-3 rounded-lg border border-primary bg-primary/5 p-4">
                            <span className="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <Smartphone className="size-5" />
                            </span>
                            <div className="grid">
                                <span className="font-medium">GCash</span>
                                <span className="text-xs text-muted-foreground">
                                    You'll be redirected to GCash to complete
                                    payment securely.
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="flex items-center justify-between rounded-lg border border-border bg-background p-4">
                    <span className="font-medium">Total</span>
                    <span className="text-2xl font-bold text-primary">
                        {data.total_amount_formatted}
                    </span>
                </div>

                <form onSubmit={submit}>
                    <Button
                        type="submit"
                        size="lg"
                        className="w-full"
                        disabled={processing || expired}
                    >
                        {processing && (
                            <LoaderCircle className="size-4 animate-spin" />
                        )}
                        {expired ? 'Hold expired' : 'Pay with GCash'}
                    </Button>
                </form>
                {expired && (
                    <p className="-mt-3 text-center text-xs text-destructive">
                        This reservation hold has expired. Please start a new
                        booking.
                    </p>
                )}
            </div>
        </>
    );
}
