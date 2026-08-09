import { Head, Link } from '@inertiajs/react';
import { CalendarCheck, Plus } from 'lucide-react';
import { BookingCard } from '@/components/admin/bookings/booking-card';
import { BookingFilters } from '@/components/admin/bookings/booking-filters';
import { BookingTable } from '@/components/admin/bookings/booking-table';
import { Pagination } from '@/components/shared/pagination';
import { Button } from '@/components/ui/button';
import * as walkIn from '@/routes/admin/bookings/walk-in';
import type {
    Booking,
    BookingFilters as BookingFiltersType,
    Paginated,
    SelectOption,
} from '@/types';

type BookingsIndexProps = {
    bookings: Paginated<Booking>;
    filters: BookingFiltersType;
    courts: Array<{ id: number; name: string }>;
    statuses: SelectOption[];
};

export default function BookingsIndex({
    bookings,
    filters,
    courts,
    statuses,
}: BookingsIndexProps) {
    const hasFilters =
        Boolean(filters.search) ||
        Boolean(filters.date) ||
        Boolean(filters.court) ||
        Boolean(filters.status);
    const isEmpty = bookings.data.length === 0;

    return (
        <>
            <Head title="Bookings" />

            <div className="flex flex-col gap-6">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h1 className="text-xl font-semibold">Bookings</h1>
                        <p className="text-sm text-muted-foreground">
                            Review and manage court reservations.
                        </p>
                    </div>
                    <Button asChild size="sm">
                        <Link href={walkIn.create()}>
                            <Plus className="size-4" />
                            Walk-in booking
                        </Link>
                    </Button>
                </div>

                <BookingFilters
                    filters={filters}
                    courts={courts}
                    statuses={statuses}
                />

                {isEmpty ? (
                    <div className="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border bg-muted/20 p-12 text-center">
                        <span className="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <CalendarCheck className="size-6" />
                        </span>
                        <div className="grid gap-1">
                            <p className="font-medium">
                                {hasFilters
                                    ? 'No bookings match your filters'
                                    : 'No bookings yet'}
                            </p>
                            <p className="max-w-sm text-sm text-muted-foreground">
                                {hasFilters
                                    ? 'Try adjusting your search or filters.'
                                    : 'Bookings will appear here once customers reserve courts.'}
                            </p>
                        </div>
                    </div>
                ) : (
                    <>
                        <BookingTable bookings={bookings.data} />

                        <div className="grid gap-4 sm:grid-cols-2 lg:hidden">
                            {bookings.data.map((booking) => (
                                <BookingCard
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

BookingsIndex.layout = {
    title: 'Bookings',
};
