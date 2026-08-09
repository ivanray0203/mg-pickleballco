import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, LoaderCircle, TriangleAlert } from 'lucide-react';
import { useMemo, useState } from 'react';
import { CourtTypeBadge } from '@/components/admin/courts/court-type-badge';
import { EquipmentPicker } from '@/components/client/equipment-picker';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatHour } from '@/lib/time';
import { book } from '@/routes';
import { store } from '@/routes/bookings';
import type { Court, EquipmentOption } from '@/types';

type BookingReviewSummary = {
    date: string;
    start_time: string;
    end_time: string;
    duration_hours: number;
    hourly_rate: number;
    hourly_rate_formatted: string;
    total_amount: number;
    total_amount_formatted: string;
};

type BookingReviewProps = {
    court: { data: Court };
    summary: BookingReviewSummary;
    equipment: EquipmentOption[];
};

function formatLongDate(value: string): string {
    return new Date(`${value}T00:00:00`).toLocaleDateString(undefined, {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
}

function formatPeso(value: number): string {
    return `₱${value.toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
}

export default function BookingReview({
    court,
    summary,
    equipment,
}: BookingReviewProps) {
    const courtData = court.data;

    const [selection, setSelection] = useState<Record<number, number>>({});

    const { setData, post, processing, errors } = useForm<{
        court: string;
        date: string;
        start_time: string;
        duration: number;
        equipment: Array<{ id: number; quantity: number }>;
    }>({
        court: courtData.slug,
        date: summary.date,
        start_time: summary.start_time,
        duration: summary.duration_hours,
        equipment: [],
    });

    const updateSelection = (next: Record<number, number>) => {
        setSelection(next);
        setData(
            'equipment',
            Object.entries(next).map(([id, quantity]) => ({
                id: Number(id),
                quantity,
            })),
        );
    };

    // Live preview lines; the server recomputes the authoritative total.
    const equipmentLines = useMemo(
        () =>
            equipment
                .filter((item) => (selection[item.id] ?? 0) > 0)
                .map((item) => {
                    const quantity = selection[item.id];

                    return {
                        id: item.id,
                        name: item.name,
                        quantity,
                        total:
                            item.hourly_rate *
                            quantity *
                            summary.duration_hours,
                    };
                }),
        [equipment, selection, summary.duration_hours],
    );

    const equipmentTotal = equipmentLines.reduce(
        (sum, line) => sum + line.total,
        0,
    );
    const grandTotal = summary.total_amount + equipmentTotal;

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        post(store.url());
    };

    // Slot taken between review and submit surfaces on start_time; stock
    // shortfalls surface on equipment.
    const errorMessage = errors.start_time ?? errors.equipment;

    return (
        <>
            <Head title="Review Booking" />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-6">
                <div>
                    <Link
                        href={book.url({
                            query: {
                                court: courtData.slug,
                                date: summary.date,
                            },
                        })}
                        className="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Change selection
                    </Link>
                    <h1 className="text-2xl font-semibold">Review booking</h1>
                    <p className="text-sm text-muted-foreground">
                        Confirm the details before continuing to payment.
                    </p>
                </div>

                {errorMessage && (
                    <div className="flex items-center gap-2 rounded-lg border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive">
                        <TriangleAlert className="size-4 shrink-0" />
                        {errorMessage}
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-3">
                            <CardTitle>{courtData.name}</CardTitle>
                            <CourtTypeBadge
                                type={courtData.type}
                                label={courtData.type_label}
                            />
                        </div>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-3 text-sm">
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Date</span>
                            <span className="font-medium">
                                {formatLongDate(summary.date)}
                            </span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Time</span>
                            <span className="font-medium">
                                {formatHour(summary.start_time)} –{' '}
                                {formatHour(summary.end_time)}
                            </span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">
                                Duration
                            </span>
                            <span className="font-medium">
                                {summary.duration_hours}{' '}
                                {summary.duration_hours === 1
                                    ? 'Hour'
                                    : 'Hours'}
                            </span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">
                                Court rate
                            </span>
                            <span className="font-medium">
                                {summary.hourly_rate_formatted} / hour
                            </span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">
                                Court subtotal
                            </span>
                            <span className="font-medium">
                                {summary.total_amount_formatted}
                            </span>
                        </div>

                        {equipmentLines.map((line) => (
                            <div key={line.id} className="flex justify-between">
                                <span className="text-muted-foreground">
                                    {line.name} × {line.quantity}
                                </span>
                                <span className="font-medium">
                                    {formatPeso(line.total)}
                                </span>
                            </div>
                        ))}

                        <div className="flex items-baseline justify-between border-t border-border pt-3">
                            <span className="font-medium">Total</span>
                            <span className="text-2xl font-bold text-primary">
                                {formatPeso(grandTotal)}
                            </span>
                        </div>
                    </CardContent>
                </Card>

                {equipment.length > 0 && (
                    <EquipmentPicker
                        equipment={equipment}
                        durationHours={summary.duration_hours}
                        selection={selection}
                        onChange={updateSelection}
                    />
                )}

                <form onSubmit={submit}>
                    <Button
                        type="submit"
                        size="lg"
                        className="w-full"
                        disabled={processing}
                    >
                        {processing && (
                            <LoaderCircle className="size-4 animate-spin" />
                        )}
                        Continue to Payment
                    </Button>
                </form>
                <p className="-mt-3 text-center text-xs text-muted-foreground">
                    Your court will be held for a limited time while you pay.
                </p>
            </div>
        </>
    );
}
