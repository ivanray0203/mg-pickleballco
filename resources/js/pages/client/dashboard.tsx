import { Head, Link, usePage } from '@inertiajs/react';
import { CalendarCheck, CalendarClock, CircleCheck, Plus } from 'lucide-react';
import { RecentBookingItem } from '@/components/client/recent-booking-item';
import { UpcomingBookingCard } from '@/components/client/upcoming-booking-card';
import { StatCard } from '@/components/shared/stat-card';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { index as courtsIndex } from '@/routes/courts';
import type { Booking, CustomerStats } from '@/types';

type DashboardProps = {
    stats: CustomerStats;
    upcomingBooking: { data: Booking } | null;
    recentBookings: { data: Booking[] };
};

export default function ClientDashboard({
    stats,
    upcomingBooking,
    recentBookings,
}: DashboardProps) {
    const { auth } = usePage().props;
    const firstName = auth.user.name.split(' ')[0];

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Welcome back, {firstName}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Here's an overview of your court bookings.
                        </p>
                    </div>
                    <Button asChild size="lg">
                        <Link href={courtsIndex()}>
                            <Plus className="size-4" />
                            Book a Court
                        </Link>
                    </Button>
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    <StatCard
                        title="Upcoming"
                        value={stats.upcoming_bookings}
                        icon={CalendarClock}
                    />
                    <StatCard
                        title="Completed"
                        value={stats.completed_bookings}
                        icon={CircleCheck}
                    />
                    <StatCard
                        title="Total Bookings"
                        value={stats.total_bookings}
                        icon={CalendarCheck}
                    />
                </div>

                <section className="flex flex-col gap-3">
                    <h2 className="text-lg font-semibold">Upcoming booking</h2>
                    {upcomingBooking ? (
                        <UpcomingBookingCard booking={upcomingBooking.data} />
                    ) : (
                        <Card>
                            <CardContent className="flex flex-col items-center justify-center gap-3 py-10 text-center">
                                <span className="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                                    <CalendarClock className="size-6" />
                                </span>
                                <div className="grid gap-1">
                                    <p className="font-medium">
                                        No upcoming bookings
                                    </p>
                                    <p className="max-w-sm text-sm text-muted-foreground">
                                        Reserve a court to see your next game
                                        here.
                                    </p>
                                </div>
                                <Button asChild>
                                    <Link href={courtsIndex()}>
                                        <Plus className="size-4" />
                                        Book a Court
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>
                    )}
                </section>

                <Card>
                    <CardHeader>
                        <CardTitle>Recent bookings</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {recentBookings.data.length === 0 ? (
                            <p className="rounded-lg border border-dashed border-border bg-muted/20 p-6 text-center text-sm text-muted-foreground">
                                You haven't made any bookings yet.
                            </p>
                        ) : (
                            <ul className="flex flex-col divide-y divide-border">
                                {recentBookings.data.map((booking) => (
                                    <RecentBookingItem
                                        key={booking.id}
                                        booking={booking}
                                    />
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
