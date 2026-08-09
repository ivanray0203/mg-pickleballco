import { Head, Link } from '@inertiajs/react';
import { XCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { book } from '@/routes';
import { checkout } from '@/routes/bookings';
import type { Booking } from '@/types';

type PaymentCancelledProps = {
    booking: { data: Booking };
    can_retry: boolean;
};

export default function PaymentCancelled({
    booking,
    can_retry,
}: PaymentCancelledProps) {
    const data = booking.data;

    return (
        <>
            <Head title="Payment Cancelled" />

            <div className="mx-auto flex w-full max-w-lg flex-col gap-6">
                <Card>
                    <CardContent className="flex flex-col items-center gap-4 py-10 text-center">
                        <span className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                            <XCircle className="size-6" />
                        </span>
                        <div className="grid gap-1">
                            <h1 className="text-xl font-semibold">
                                Payment cancelled
                            </h1>
                            <p className="max-w-sm text-sm text-muted-foreground">
                                {can_retry
                                    ? 'No payment was taken. Your court is still held for a little longer if you want to try again.'
                                    : 'No payment was taken and the reservation hold has ended.'}
                            </p>
                        </div>

                        {can_retry ? (
                            <Button asChild>
                                <Link href={checkout.url({ booking: data.id })}>
                                    Try Again
                                </Link>
                            </Button>
                        ) : (
                            <Button asChild>
                                <Link
                                    href={book.url({
                                        query: { court: data.court.slug },
                                    })}
                                >
                                    Return to Booking
                                </Link>
                            </Button>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
