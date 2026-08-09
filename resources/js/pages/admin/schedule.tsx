import { Head, router } from '@inertiajs/react';
import { CalendarX2, LandPlot } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { index as scheduleRoute } from '@/routes/admin/schedule';
import type { DailyBoard, DailyBoardCourt, DailyBoardStatus } from '@/types';

type SchedulePageProps = {
    date: string;
    schedule: DailyBoard;
};

const STATUS_META: Record<
    DailyBoardStatus,
    { label: string; chip: string; dot: string }
> = {
    available: {
        label: 'Available',
        chip: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-600',
        dot: 'bg-emerald-500',
    },
    booked: {
        label: 'Booked',
        chip: 'border-primary/30 bg-primary/10 text-primary',
        dot: 'bg-primary',
    },
    session: {
        label: 'Open play',
        chip: 'border-amber-500/30 bg-amber-500/10 text-amber-600',
        dot: 'bg-amber-500',
    },
    blocked: {
        label: 'Blocked',
        chip: 'border-destructive/30 bg-destructive/10 text-destructive',
        dot: 'bg-destructive',
    },
};

const LEGEND: DailyBoardStatus[] = [
    'available',
    'booked',
    'session',
    'blocked',
];

function SlotRow({
    label,
    status,
    detail,
}: {
    label: string;
    status: DailyBoardStatus;
    detail: string | null;
}) {
    const meta = STATUS_META[status];

    return (
        <div className="flex items-center gap-3 border-t border-border/60 py-2 first:border-t-0">
            <span className="w-16 shrink-0 text-xs font-medium text-muted-foreground tabular-nums">
                {label}
            </span>
            <span
                className={cn(
                    'inline-flex shrink-0 items-center gap-1.5 rounded-md border px-2 py-0.5 text-xs font-medium',
                    meta.chip,
                )}
            >
                <span className={cn('size-1.5 rounded-full', meta.dot)} />
                {meta.label}
            </span>
            {detail && (
                <span
                    className="truncate text-xs text-muted-foreground"
                    title={detail}
                >
                    {detail}
                </span>
            )}
        </div>
    );
}

function CourtCard({ court }: { court: DailyBoardCourt }) {
    return (
        <Card className={cn(!court.is_active && 'opacity-70')}>
            <CardContent className="flex flex-col gap-3">
                <div className="flex items-start justify-between gap-2">
                    <div className="flex items-center gap-2">
                        <span className="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <LandPlot className="size-4" />
                        </span>
                        <div>
                            <p className="leading-tight font-semibold">
                                {court.name}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {court.is_open
                                    ? court.hours_label
                                    : 'Closed today'}
                            </p>
                        </div>
                    </div>
                    {!court.is_active && (
                        <Badge
                            variant="outline"
                            className="text-muted-foreground"
                        >
                            Inactive
                        </Badge>
                    )}
                </div>

                {court.is_open && court.slots.length > 0 ? (
                    <>
                        <div className="flex flex-wrap gap-2 text-xs text-muted-foreground">
                            <span>{court.summary.available} available</span>
                            <span aria-hidden>·</span>
                            <span>{court.summary.booked} booked</span>
                            {court.summary.session > 0 && (
                                <>
                                    <span aria-hidden>·</span>
                                    <span>
                                        {court.summary.session} open play
                                    </span>
                                </>
                            )}
                        </div>
                        <div className="flex flex-col">
                            {court.slots.map((slot) => (
                                <SlotRow
                                    key={slot.hour}
                                    label={slot.label}
                                    status={slot.status}
                                    detail={slot.detail}
                                />
                            ))}
                        </div>
                    </>
                ) : (
                    <div className="flex flex-col items-center gap-1 rounded-lg border border-dashed border-border bg-muted/20 py-6 text-center">
                        <CalendarX2 className="size-5 text-muted-foreground" />
                        <p className="text-sm text-muted-foreground">
                            Closed on this day
                        </p>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

export default function DailySchedulePage({
    date,
    schedule,
}: SchedulePageProps) {
    const changeDate = (value: string) => {
        if (!value) {
            return;
        }

        router.get(
            scheduleRoute.url(),
            { date: value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const hasCourts = schedule.courts.length > 0;

    return (
        <>
            <Head title="Daily schedule" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">
                            Daily schedule
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Availability, bookings and open play across every
                            court on {schedule.day_label}.
                        </p>
                    </div>
                    <div className="grid gap-1">
                        <Label htmlFor="date">Date</Label>
                        <Input
                            id="date"
                            type="date"
                            value={date}
                            className="w-full sm:w-44"
                            onChange={(e) => changeDate(e.target.value)}
                        />
                    </div>
                </div>

                {hasCourts && (
                    <div className="flex flex-wrap items-center gap-x-4 gap-y-2">
                        {LEGEND.map((status) => {
                            const meta = STATUS_META[status];

                            return (
                                <span
                                    key={status}
                                    className="inline-flex items-center gap-1.5 text-xs text-muted-foreground"
                                >
                                    <span
                                        className={cn(
                                            'size-2 rounded-full',
                                            meta.dot,
                                        )}
                                    />
                                    {meta.label}
                                </span>
                            );
                        })}
                    </div>
                )}

                {!hasCourts ? (
                    <div className="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-border bg-muted/20 p-12 text-center">
                        <CalendarX2 className="size-6 text-muted-foreground" />
                        <p className="font-medium">No courts to display</p>
                        <p className="max-w-sm text-sm text-muted-foreground">
                            Add a court to see its daily schedule here.
                        </p>
                    </div>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {schedule.courts.map((court) => (
                            <CourtCard key={court.id} court={court} />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

DailySchedulePage.layout = {
    title: 'Daily schedule',
};
