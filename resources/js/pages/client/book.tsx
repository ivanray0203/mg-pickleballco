import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, CalendarX2 } from 'lucide-react';
import { useState } from 'react';
import { BookingSummary } from '@/components/client/booking-summary';
import { DurationPicker } from '@/components/client/duration-picker';
import { SlotGrid } from '@/components/client/slot-grid';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatHour } from '@/lib/time';
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
    const [selectedStart, setSelectedStart] = useState<string | null>(null);
    const [duration, setDuration] = useState(1);

    // Reset the selection whenever the day (and its availability) changes.
    // Adjusting state during render (tracking the previous date) is the
    // recommended pattern over a setState-in-effect.
    const [trackedDate, setTrackedDate] = useState(availability.date);

    if (trackedDate !== availability.date) {
        setTrackedDate(availability.date);
        setSelectedStart(null);
        setDuration(1);
    }

    const changeDate = (value: string) => {
        if (!value) {
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

    const selectedSlot = availability.slots.find(
        (slot) => slot.time === selectedStart,
    );
    const maxDuration = selectedSlot?.max_duration ?? 0;

    const selectStart = (time: string) => {
        setSelectedStart(time);
        setDuration(1);
    };

    const canSelectTimes = availability.bookable_date && availability.is_open;

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
                                        min={constraints.min_date}
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
                                <CardTitle>2. Select a start time</CardTitle>
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
                                ) : availability.slots.length === 0 ? (
                                    <p className="rounded-lg border border-dashed border-border bg-muted/20 p-8 text-center text-sm text-muted-foreground">
                                        No time slots for this day.
                                    </p>
                                ) : (
                                    <div className="flex flex-col gap-4">
                                        <SlotGrid
                                            slots={availability.slots}
                                            selected={selectedStart}
                                            onSelect={selectStart}
                                        />
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
                                                <span className="size-3 rounded border border-dashed border-border bg-muted/50" />
                                                Unavailable
                                            </span>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {selectedStart && maxDuration > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>3. Select duration</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <DurationPicker
                                        max={maxDuration}
                                        selected={duration}
                                        onSelect={setDuration}
                                    />
                                </CardContent>
                            </Card>
                        )}
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
                                    Select a date and start time to see your
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
