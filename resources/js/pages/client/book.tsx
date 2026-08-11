import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, CalendarX2 } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { BookingSummary } from '@/components/client/booking-summary';
import { SlotGrid } from '@/components/client/slot-grid';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatHour, toDateString, toHourString } from '@/lib/time';
import { book } from '@/routes';
import { review } from '@/routes/book';
import { show as courtShow } from '@/routes/courts';
import type { BookingAvailability, BookingConstraints, Court } from '@/types';

type BookProps = {
    court: { data: Court };
    date: string;
    availability: BookingAvailability;
    constraints: BookingConstraints;
};

function addHours(time: string, hours: number): string {
    const hour = Number.parseInt(time.slice(0, 2), 10) + hours;

    return `${String(hour).padStart(2, '0')}:00`;
}

/**
 * The current moment, re-read every half minute. The server already excludes
 * past dates and elapsed hours when the page renders; this keeps the screen
 * honest while it stays open across a midnight or an hour boundary.
 */
function useNow(): Date {
    const [now, setNow] = useState(() => new Date());

    useEffect(() => {
        const timer = window.setInterval(() => setNow(new Date()), 30_000);

        return () => window.clearInterval(timer);
    }, []);

    return now;
}

function formatLongDate(value: string): string {
    return new Date(`${value}T00:00:00`).toLocaleDateString(undefined, {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
}

export default function Book({
    court,
    date,
    availability,
    constraints,
}: BookProps) {
    const courtData = court.data;
    const now = useNow();
    const [selectedStart, setSelectedStart] = useState<string | null>(null);
    const [duration, setDuration] = useState(1);
    const [notice, setNotice] = useState<string | null>(null);

    const today = toDateString(now);

    // Never offer a date before today, even if the page was rendered before
    // midnight. The server bound only ever gets stricter, never looser.
    const minDate = constraints.min_date < today ? today : constraints.min_date;

    // On today, every hour that has already started is closed. `null` on any
    // other date, where no hour has elapsed.
    const elapsedThrough =
        availability.date === today ? toHourString(now) : null;

    // Reset the selection whenever the day (and its availability) changes.
    // Adjusting state during render (tracking the previous date) is the
    // recommended pattern over a setState-in-effect.
    const [trackedDate, setTrackedDate] = useState(availability.date);

    if (trackedDate !== availability.date) {
        setTrackedDate(availability.date);
        setSelectedStart(null);
        setDuration(1);
        setNotice(null);
    }

    const changeDate = (value: string) => {
        // `min`/`max` on the input guide the picker, but a typed-in date can
        // still fall outside the window — ignore those rather than round-trip.
        if (!value || value < minDate || value > constraints.max_date) {
            return;
        }

        router.get(
            book.url(),
            { court: courtData.slug, date: value },
            {
                only: ['availability', 'date'],
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    // Close any hour the clock has passed since the server rendered the page.
    // This can only take availability away, never grant it.
    const slots = useMemo(
        () =>
            elapsedThrough === null
                ? availability.slots
                : availability.slots.map((slot) =>
                      slot.time <= elapsedThrough
                          ? {
                                ...slot,
                                state: 'past' as const,
                                available: false,
                                max_duration: 0,
                            }
                          : slot,
                  ),
        [availability.slots, elapsedThrough],
    );

    // Drop a selection the clock has overtaken while the page sat open.
    if (
        selectedStart !== null &&
        elapsedThrough !== null &&
        selectedStart <= elapsedThrough
    ) {
        setSelectedStart(null);
        setDuration(1);
        setNotice('That start time has passed. Please choose another hour.');
    }

    const startIndex = selectedStart
        ? slots.findIndex((slot) => slot.time === selectedStart)
        : -1;
    const endIndex = startIndex === -1 ? -1 : startIndex + duration - 1;
    const selectedTimes =
        startIndex === -1
            ? []
            : slots
                  .slice(startIndex, startIndex + duration)
                  .map((slot) => slot.time);

    /**
     * Bookings are one contiguous block, so a tap either extends the run by an
     * adjacent hour, trims it, or starts a fresh selection. `max_duration` on
     * the starting slot already accounts for gaps and the configured cap, so it
     * is the only limit an extension has to respect.
     *
     * Tapping an adjacent hour that would breach the cap keeps the current
     * selection and explains why — silently restarting there reads as the grid
     * losing the selection for no reason.
     */
    const toggleSlot = (time: string) => {
        const index = slots.findIndex((slot) => slot.time === time);

        if (index === -1 || !slots[index].available) {
            return;
        }

        setNotice(null);

        if (startIndex === -1) {
            setSelectedStart(time);
            setDuration(1);

            return;
        }

        if (index >= startIndex && index <= endIndex) {
            if (duration === 1) {
                setSelectedStart(null);
            } else if (index === startIndex) {
                setSelectedStart(slots[startIndex + 1].time);
                setDuration(duration - 1);
            } else if (index === endIndex) {
                setDuration(duration - 1);
            } else {
                setDuration(index - startIndex + 1);
            }

            return;
        }

        const isAdjacent = index === endIndex + 1 || index === startIndex - 1;

        if (isAdjacent) {
            const extendingBack = index === startIndex - 1;
            const limit = extendingBack
                ? slots[index].max_duration
                : slots[startIndex].max_duration;

            if (duration + 1 > limit) {
                setNotice(
                    `You can book up to ${constraints.max_duration} ${
                        constraints.max_duration === 1 ? 'hour' : 'hours'
                    } at a time.`,
                );

                return;
            }

            if (extendingBack) {
                setSelectedStart(time);
            }

            setDuration(duration + 1);

            return;
        }

        setSelectedStart(time);
        setDuration(1);
    };

    const canSelectTimes = availability.bookable_date && availability.is_open;
    const hasSelectableSlot = slots.some((slot) => slot.available);

    return (
        <>
            <Head title="Book a Court" />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6">
                <div>
                    <Link
                        href={courtShow(courtData.slug)}
                        className="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Back to {courtData.name}
                    </Link>
                    <h1 className="text-2xl font-semibold">Book a Court</h1>
                    <p className="text-sm text-muted-foreground">
                        Choose a date and time for {courtData.name}.
                    </p>
                </div>

                <div className="grid gap-6 lg:grid-cols-[1fr_340px]">
                    <div className="flex flex-col gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>1. Select a date</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-1 sm:max-w-xs">
                                    <Label htmlFor="date">Date</Label>
                                    <Input
                                        id="date"
                                        type="date"
                                        value={date}
                                        min={minDate}
                                        max={constraints.max_date}
                                        onChange={(e) =>
                                            changeDate(e.target.value)
                                        }
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>2. Select your hours</CardTitle>
                                <CardDescription>
                                    Tap an hour, then tap the hours next to it
                                    to book a longer block — up to{' '}
                                    {constraints.max_duration} hours.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {!canSelectTimes ? (
                                    <div className="flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-border bg-muted/20 p-8 text-center">
                                        <CalendarX2 className="size-6 text-muted-foreground" />
                                        <p className="text-sm font-medium">
                                            {!availability.bookable_date
                                                ? 'Choose a date within the booking window'
                                                : 'The court is closed on this day'}
                                        </p>
                                        <p className="max-w-sm text-sm text-muted-foreground">
                                            Please pick another date to see
                                            available times.
                                        </p>
                                    </div>
                                ) : slots.length === 0 ? (
                                    <p className="rounded-lg border border-dashed border-border bg-muted/20 p-8 text-center text-sm text-muted-foreground">
                                        No time slots for this day.
                                    </p>
                                ) : (
                                    <div className="flex flex-col gap-4">
                                        <SlotGrid
                                            slots={slots}
                                            selected={selectedTimes}
                                            onSelect={toggleSlot}
                                        />
                                        {!hasSelectableSlot && (
                                            <p className="text-sm text-muted-foreground">
                                                {elapsedThrough === null
                                                    ? 'Every hour is taken on this day. Please pick another date.'
                                                    : 'There are no hours left to book today. Please pick a later date.'}
                                            </p>
                                        )}
                                        {notice && (
                                            <p
                                                role="status"
                                                className="text-sm text-muted-foreground"
                                            >
                                                {notice}
                                            </p>
                                        )}
                                        <div className="flex flex-wrap gap-4 text-xs text-muted-foreground">
                                            <span className="flex items-center gap-1.5">
                                                <span className="size-3 rounded border border-border bg-background" />
                                                Available
                                            </span>
                                            <span className="flex items-center gap-1.5">
                                                <span className="size-3 rounded bg-primary" />
                                                Selected
                                            </span>
                                            <span className="flex items-center gap-1.5">
                                                <span className="size-3 rounded border border-amber-500/40 bg-amber-500/10" />
                                                Booked
                                            </span>
                                            <span className="flex items-center gap-1.5">
                                                <span className="size-3 rounded border border-sky-500/40 bg-sky-500/10" />
                                                Open play
                                            </span>
                                            <span className="flex items-center gap-1.5">
                                                <span className="size-3 rounded border border-dashed border-border bg-muted/50" />
                                                Unavailable
                                            </span>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <div className="flex flex-col gap-4">
                        {selectedStart ? (
                            <>
                                <BookingSummary
                                    courtName={courtData.name}
                                    dateLabel={formatLongDate(date)}
                                    startLabel={formatHour(selectedStart)}
                                    endLabel={formatHour(
                                        addHours(selectedStart, duration),
                                    )}
                                    hours={duration}
                                    hourlyRate={courtData.hourly_rate}
                                />
                                <Button size="lg" className="w-full" asChild>
                                    <Link
                                        href={review.url({
                                            query: {
                                                court: courtData.slug,
                                                date,
                                                start_time: selectedStart,
                                                duration: String(duration),
                                            },
                                        })}
                                    >
                                        Continue to Review
                                    </Link>
                                </Button>
                            </>
                        ) : (
                            <Card>
                                <CardContent className="py-10 text-center text-sm text-muted-foreground">
                                    Select a date and your hours to see your
                                    booking summary.
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
