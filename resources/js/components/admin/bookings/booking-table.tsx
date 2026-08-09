import { Link } from '@inertiajs/react';
import { BookingStatusBadge } from '@/components/admin/bookings/booking-status-badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatHour } from '@/lib/time';
import { show } from '@/routes/admin/bookings';
import type { Booking } from '@/types';

function formatDate(value: string): string {
    return new Date(`${value}T00:00:00`).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

export function BookingTable({ bookings }: { bookings: Booking[] }) {
    return (
        <div className="hidden overflow-x-auto rounded-xl border border-border bg-card lg:block">
            <Table>
                <TableHeader>
                    <TableRow className="bg-muted/40 hover:bg-muted/40">
                        <TableHead>Reference</TableHead>
                        <TableHead>Customer</TableHead>
                        <TableHead>Court</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead>Time</TableHead>
                        <TableHead>Duration</TableHead>
                        <TableHead>Amount</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Payment</TableHead>
                        <TableHead className="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {bookings.map((booking) => (
                        <TableRow key={booking.id}>
                            <TableCell className="font-mono text-xs font-medium">
                                {booking.booking_reference}
                            </TableCell>
                            <TableCell>
                                <div className="grid">
                                    <span className="font-medium">
                                        {booking.customer.name}
                                    </span>
                                    <span className="text-xs text-muted-foreground">
                                        {booking.customer.email}
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell>{booking.court.name}</TableCell>
                            <TableCell>
                                {formatDate(booking.booking_date)}
                            </TableCell>
                            <TableCell className="whitespace-nowrap">
                                {formatHour(booking.start_time)} –{' '}
                                {formatHour(booking.end_time)}
                            </TableCell>
                            <TableCell>{booking.duration_hours}h</TableCell>
                            <TableCell className="font-medium">
                                {booking.total_amount_formatted}
                            </TableCell>
                            <TableCell>
                                <BookingStatusBadge
                                    status={booking.status}
                                    label={booking.status_label}
                                />
                            </TableCell>
                            <TableCell className="text-muted-foreground">
                                {booking.payment_status ?? '—'}
                            </TableCell>
                            <TableCell className="text-right">
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={show(booking.id)}>View</Link>
                                </Button>
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}
