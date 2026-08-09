import { Head, router } from '@inertiajs/react';
import { CalendarCheck } from 'lucide-react';
import { BookingListCard } from '@/components/client/booking-list-card';
import { Pagination } from '@/components/shared/pagination';
import { cn } from '@/lib/utils';
import { index } from '@/routes/bookings';
import type { Booking, Paginated } from '@/types';

type BookingFilter = 'upcoming' | 'completed' | 'cancelled' | 'expired';

type BookingsProps = {
    bookings: Paginated<Booking>;
    filter: BookingFilter;
};

const TABS: Array<{ value: BookingFilter; label: string }> = [
    { value: 'upcoming', label: 'Upcoming' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' },
    { value: 'expired', label: 'Expired' },
];

export default function Bookings({ bookings, filter }: BookingsProps) {
    const isEmpty = bookings.data.length === 0;

    const selectFilter = (value: BookingFilter) => {
        router.get(
            index.url(),
            { filter: value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    return (
        <>
            <Head title="My Bookings" />

            <div className="flex flex-col gap-6">
                <div>
                    <h1 className="text-2xl font-semibold">My Bookings</h1>
                    <p className="text-sm text-muted-foreground">
                        Your reservations across every court.
                    </p>
                </div>

                <div className="flex gap-1 overflow-x-auto rounded-lg bg-muted p-1">
                    {TABS.map((tab) => (
                        <button
                            key={tab.value}
                            type="button"
                            onClick={() => selectFilter(tab.value)}
                            className={cn(
                                'flex-1 rounded-md px-4 py-1.5 text-sm font-medium whitespace-nowrap transition-colors',
                                filter === tab.value
                                    ? 'bg-background text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground',
                            )}
                        >
                            {tab.label}
                        </button>
                    ))}
                </div>

                {isEmpty ? (
                    <div className="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border bg-background p-12 text-center">
                        <span className="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <CalendarCheck className="size-6" />
                        </span>
                        <div className="grid gap-1">
                            <p className="font-medium">No {filter} bookings</p>
                            <p className="max-w-sm text-sm text-muted-foreground">
                                Bookings in this category will appear here.
                            </p>
                        </div>
                    </div>
                ) : (
                    <>
                        <div className="flex flex-col gap-4">
                            {bookings.data.map((booking) => (
                                <BookingListCard
                                    key={booking.id}
                                    booking={booking}
                                />
                            ))}
                        </div>

                        <Pagination meta={bookings.meta} />
                    </>
                )}
            </div>
        </>
    );
}
