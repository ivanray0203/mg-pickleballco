import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarCheck,
    CalendarClock,
    CircleCheck,
    CircleX,
    Wallet,
} from 'lucide-react';
import { BookingStatusBadge } from '@/components/admin/bookings/booking-status-badge';
import { PaymentStatusBadge } from '@/components/admin/payments/payment-status-badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatHour } from '@/lib/time';
import { show as bookingShow } from '@/routes/admin/bookings';
import { index } from '@/routes/admin/customers';
import { show as paymentShow } from '@/routes/admin/payments';
import type { Booking, Customer, CustomerStats, Payment } from '@/types';

type CustomerShowProps = {
    customer: { data: Customer };
    stats: CustomerStats;
    upcomingBookings: { data: Booking[] };
    bookingHistory: { data: Booking[] };
    paymentHistory: { data: Payment[] };
};

function formatDate(value: string): string {
    return new Date(`${value}T00:00:00`).toLocaleDateString(undefined, {
        month: 'short',
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

function StatTile({
    icon: Icon,
    label,
    value,
}: {
    icon: typeof CalendarCheck;
    label: string;
    value: string | number;
}) {
    return (
        <Card>
            <CardContent className="flex items-center gap-3">
                <span className="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <Icon className="size-5" />
                </span>
                <div className="grid">
                    <span className="text-lg font-semibold">{value}</span>
                    <span className="text-xs text-muted-foreground">
                        {label}
                    </span>
                </div>
            </CardContent>
        </Card>
    );
}

function BookingRows({
    bookings,
    empty,
}: {
    bookings: Booking[];
    empty: string;
}) {
    if (bookings.length === 0) {
        return (
            <p className="rounded-lg border border-dashed border-border bg-muted/20 p-4 text-sm text-muted-foreground">
                {empty}
            </p>
        );
    }

    return (
        <div className="overflow-x-auto rounded-lg border border-border">
            <Table>
                <TableHeader>
                    <TableRow className="bg-muted/40 hover:bg-muted/40">
                        <TableHead>Reference</TableHead>
                        <TableHead>Court</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead>Time</TableHead>
                        <TableHead>Amount</TableHead>
                        <TableHead>Status</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {bookings.map((booking) => (
                        <TableRow key={booking.id}>
                            <TableCell className="font-mono text-xs">
                                <Link
                                    href={bookingShow(booking.id)}
                                    className="text-primary hover:underline"
                                >
                                    {booking.booking_reference}
                                </Link>
                            </TableCell>
                            <TableCell>{booking.court.name}</TableCell>
                            <TableCell className="whitespace-nowrap">
                                {formatDate(booking.booking_date)}
                            </TableCell>
                            <TableCell className="whitespace-nowrap">
                                {formatHour(booking.start_time)} –{' '}
                                {formatHour(booking.end_time)}
                            </TableCell>
                            <TableCell className="font-medium">
                                {booking.total_amount_formatted}
                            </TableCell>
                            <TableCell>
                                <BookingStatusBadge
                                    status={booking.status}
                                    label={booking.status_label}
                                />
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

export default function CustomerShow({
    customer,
    stats,
    upcomingBookings,
    bookingHistory,
    paymentHistory,
}: CustomerShowProps) {
    const data = customer.data;
    const initials = data.name
        .split(' ')
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();

    return (
        <>
            <Head title={data.name} />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6">
                <div>
                    <Link
                        href={index()}
                        className="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Back to customers
                    </Link>
                    <div className="flex items-center gap-4">
                        <span className="flex size-14 items-center justify-center rounded-full bg-primary/10 text-lg font-semibold text-primary">
                            {initials}
                        </span>
                        <div className="grid">
                            <h1 className="text-xl font-semibold">
                                {data.name}
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                {data.email}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                Joined {formatDateTime(data.joined_at)}
                            </p>
                        </div>
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <StatTile
                        icon={CalendarCheck}
                        label="Total bookings"
                        value={stats.total_bookings}
                    />
                    <StatTile
                        icon={CalendarClock}
                        label="Upcoming"
                        value={stats.upcoming_bookings}
                    />
                    <StatTile
                        icon={CircleCheck}
                        label="Completed"
                        value={stats.completed_bookings}
                    />
                    <StatTile
                        icon={CircleX}
                        label="Cancelled"
                        value={stats.cancelled_bookings}
                    />
                    <StatTile
                        icon={Wallet}
                        label="Total paid"
                        value={stats.total_paid_formatted}
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Upcoming reservations</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <BookingRows
                            bookings={upcomingBookings.data}
                            empty="No upcoming reservations."
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Booking history</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <BookingRows
                            bookings={bookingHistory.data}
                            empty="No bookings yet."
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Payment history</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {paymentHistory.data.length === 0 ? (
                            <p className="rounded-lg border border-dashed border-border bg-muted/20 p-4 text-sm text-muted-foreground">
                                No payments yet.
                            </p>
                        ) : (
                            <div className="overflow-x-auto rounded-lg border border-border">
                                <Table>
                                    <TableHeader>
                                        <TableRow className="bg-muted/40 hover:bg-muted/40">
                                            <TableHead>Reference</TableHead>
                                            <TableHead>Method</TableHead>
                                            <TableHead>Amount</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead>Paid at</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {paymentHistory.data.map((payment) => (
                                            <TableRow key={payment.id}>
                                                <TableCell className="font-mono text-xs">
                                                    <Link
                                                        href={paymentShow(
                                                            payment.id,
                                                        )}
                                                        className="text-primary hover:underline"
                                                    >
                                                        {
                                                            payment.payment_reference
                                                        }
                                                    </Link>
                                                </TableCell>
                                                <TableCell>
                                                    {
                                                        payment.payment_method_label
                                                    }
                                                </TableCell>
                                                <TableCell className="font-medium">
                                                    {payment.amount_formatted}
                                                </TableCell>
                                                <TableCell>
                                                    <PaymentStatusBadge
                                                        status={payment.status}
                                                        label={
                                                            payment.status_label
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell className="whitespace-nowrap text-muted-foreground">
                                                    {formatDateTime(
                                                        payment.paid_at,
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CustomerShow.layout = {
    title: 'Customer details',
};
