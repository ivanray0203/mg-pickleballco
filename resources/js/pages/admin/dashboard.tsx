import { Head, Link } from '@inertiajs/react';
import {
    CalendarCheck,
    CreditCard,
    LandPlot,
    PhilippinePeso,
} from 'lucide-react';
import { BookingStatusBadge } from '@/components/admin/bookings/booking-status-badge';
import { PaymentStatusBadge } from '@/components/admin/payments/payment-status-badge';
import { StatCard } from '@/components/shared/stat-card';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatHour } from '@/lib/time';
import { cn } from '@/lib/utils';
import { show as bookingShow } from '@/routes/admin/bookings';
import { show as paymentShow } from '@/routes/admin/payments';
import type {
    Booking,
    DailySchedule,
    DailyScheduleStatus,
    DashboardStats,
    Payment,
} from '@/types';

type DashboardProps = {
    stats: DashboardStats;
    schedule: DailySchedule;
    recentBookings: { data: Booking[] };
    recentPayments: { data: Payment[] };
};

const CELL_STYLES: Record<DailyScheduleStatus, string> = {
    available: 'bg-primary/5 text-primary',
    booked: 'bg-muted text-muted-foreground',
    blocked: 'bg-destructive/5 text-destructive',
    closed: 'bg-transparent text-muted-foreground/40',
};

const CELL_LABEL: Record<DailyScheduleStatus, string> = {
    available: 'Available',
    booked: 'Booked',
    blocked: 'Blocked',
    closed: '—',
};

function formatDate(value: string): string {
    return new Date(`${value}T00:00:00`).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });
}

export default function AdminDashboard({
    stats,
    schedule,
    recentBookings,
    recentPayments,
}: DashboardProps) {
    const hasSchedule = schedule.courts.length > 0 && schedule.rows.length > 0;

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-col gap-6">
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        title="Today's Bookings"
                        value={stats.today_bookings}
                        icon={CalendarCheck}
                        description="Active reservations for today"
                    />
                    <StatCard
                        title="Today's Revenue"
                        value={stats.today_revenue_formatted}
                        icon={PhilippinePeso}
                        description="Paid, for today's bookings"
                    />
                    <StatCard
                        title="Active Courts"
                        value={stats.active_courts}
                        icon={LandPlot}
                        description="Currently bookable"
                    />
                    <StatCard
                        title="Pending Payments"
                        value={stats.pending_payments}
                        icon={CreditCard}
                        description="Awaiting settlement"
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Today's schedule</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {hasSchedule ? (
                            <div className="overflow-x-auto rounded-lg border border-border">
                                <table className="w-full border-collapse text-sm">
                                    <thead>
                                        <tr className="bg-muted/40">
                                            <th className="sticky left-0 z-10 bg-muted/40 p-2 text-left font-medium">
                                                Time
                                            </th>
                                            {schedule.courts.map((court) => (
                                                <th
                                                    key={court.id}
                                                    className="p-2 text-left font-medium"
                                                >
                                                    {court.name}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {schedule.rows.map((row) => (
                                            <tr
                                                key={row.hour}
                                                className="border-t border-border"
                                            >
                                                <td className="sticky left-0 z-10 bg-card p-2 font-medium whitespace-nowrap">
                                                    {row.label}
                                                </td>
                                                {row.cells.map((cell) => (
                                                    <td
                                                        key={cell.court_id}
                                                        className="p-1.5"
                                                    >
                                                        <span
                                                            className={cn(
                                                                'block rounded px-2 py-1 text-center text-xs font-medium',
                                                                CELL_STYLES[
                                                                    cell.status
                                                                ],
                                                            )}
                                                        >
                                                            {
                                                                CELL_LABEL[
                                                                    cell.status
                                                                ]
                                                            }
                                                        </span>
                                                    </td>
                                                ))}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <p className="rounded-lg border border-dashed border-border bg-muted/20 p-6 text-center text-sm text-muted-foreground">
                                No active courts are open today.
                            </p>
                        )}
                    </CardContent>
                </Card>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent bookings</CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-2">
                            {recentBookings.data.length === 0 ? (
                                <p className="rounded-lg border border-dashed border-border bg-muted/20 p-6 text-center text-sm text-muted-foreground">
                                    No bookings yet.
                                </p>
                            ) : (
                                recentBookings.data.map((booking) => (
                                    <Link
                                        key={booking.id}
                                        href={bookingShow(booking.id)}
                                        className="flex items-center justify-between gap-3 rounded-lg border border-border p-3 transition-colors hover:bg-accent"
                                    >
                                        <div className="grid">
                                            <span className="text-sm font-medium">
                                                {booking.customer.name}
                                            </span>
                                            <span className="text-xs text-muted-foreground">
                                                {booking.court.name} ·{' '}
                                                {formatDate(
                                                    booking.booking_date,
                                                )}{' '}
                                                {formatHour(booking.start_time)}
                                            </span>
                                        </div>
                                        <BookingStatusBadge
                                            status={booking.status}
                                            label={booking.status_label}
                                        />
                                    </Link>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Recent payments</CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-2">
                            {recentPayments.data.length === 0 ? (
                                <p className="rounded-lg border border-dashed border-border bg-muted/20 p-6 text-center text-sm text-muted-foreground">
                                    No payments yet.
                                </p>
                            ) : (
                                recentPayments.data.map((payment) => (
                                    <Link
                                        key={payment.id}
                                        href={paymentShow(payment.id)}
                                        className="flex items-center justify-between gap-3 rounded-lg border border-border p-3 transition-colors hover:bg-accent"
                                    >
                                        <div className="grid">
                                            <span className="text-sm font-medium">
                                                {payment.amount_formatted}
                                            </span>
                                            <span className="font-mono text-xs text-muted-foreground">
                                                {payment.payment_reference}
                                            </span>
                                        </div>
                                        <PaymentStatusBadge
                                            status={payment.status}
                                            label={payment.status_label}
                                        />
                                    </Link>
                                ))
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

AdminDashboard.layout = {
    title: 'Dashboard',
};
