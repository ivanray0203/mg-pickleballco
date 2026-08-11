import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    Calendar,
    CalendarDays,
    CalendarX2,
    ChevronDown,
    Clock,
    LandPlot,
    MoreVertical,
    Pencil,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { edit as editCourt } from '@/routes/admin/courts';
import { index as courtAvailability } from '@/routes/admin/courts/availability';
import { edit as courtSchedule } from '@/routes/admin/courts/schedule';
import { index as scheduleRoute } from '@/routes/admin/schedule';
import type {
    ScheduleCellStatus,
    WeekBoard,
    WeekBoardCell,
    WeekBoardCourt,
} from '@/types';

type SchedulePageProps = {
    date: string;
    schedule: WeekBoard;
};

type StatusMeta = {
    label: string;
    hint: string;
    dot: string;
    cell: string;
};

const STATUS_META: Record<ScheduleCellStatus, StatusMeta> = {
    available: {
        label: 'Available',
        hint: 'Court is available',
        dot: 'bg-emerald-500',
        cell: 'bg-emerald-500/5 text-emerald-600 dark:text-emerald-400',
    },
    booked: {
        label: 'Booked',
        hint: 'Court is booked',
        dot: 'bg-blue-500',
        cell: 'bg-blue-500/5 text-blue-600 dark:text-blue-400',
    },
    session: {
        label: 'Open Play',
        hint: 'Join open play',
        dot: 'bg-amber-500',
        cell: 'bg-amber-500/10 text-amber-600 dark:text-amber-500',
    },
    blocked: {
        label: 'Blocked',
        hint: 'Not available',
        dot: 'bg-red-500',
        cell: 'bg-red-500/5 text-red-600 dark:text-red-400',
    },
    closed: {
        label: 'Closed',
        hint: 'Not available',
        dot: 'bg-red-500',
        cell: 'text-muted-foreground/50',
    },
};

/** Only four states are worth explaining; "blocked" reads as closed to staff. */
const LEGEND: ScheduleCellStatus[] = [
    'available',
    'booked',
    'session',
    'closed',
];

const CLOSED_CELL: WeekBoardCell = {
    status: 'closed',
    detail: null,
    title: null,
};

function SummaryPill({
    count,
    label,
    tone,
}: {
    count: number;
    label: string;
    tone: 'available' | 'booked' | 'session';
}) {
    const active: Record<typeof tone, string> = {
        available:
            'border-emerald-500/30 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
        booked: 'border-blue-500/30 bg-blue-500/10 text-blue-600 dark:text-blue-400',
        session:
            'border-amber-500/30 bg-amber-500/10 text-amber-600 dark:text-amber-500',
    };

    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-medium whitespace-nowrap',
                count > 0
                    ? active[tone]
                    : 'border-border bg-muted/40 text-muted-foreground',
            )}
        >
            {count} {label}
        </span>
    );
}

function ScheduleCell({ cell }: { cell: WeekBoardCell }) {
    const meta = STATUS_META[cell.status];

    if (cell.status === 'closed') {
        return <span className="text-muted-foreground/50">&mdash;</span>;
    }

    return (
        <span className="flex flex-col items-center leading-tight">
            <span className="inline-flex items-center gap-1">
                {cell.status !== 'session' && (
                    <span className={cn('size-1 rounded-full', meta.dot)} />
                )}
                {meta.label}
            </span>
            {cell.detail && (
                <span className="text-[10px] tabular-nums opacity-80">
                    {cell.detail}
                </span>
            )}
        </span>
    );
}

function CourtPanel({
    court,
    schedule,
}: {
    court: WeekBoardCourt;
    schedule: WeekBoard;
}) {
    return (
        <div
            className={cn(
                'overflow-hidden rounded-xl border border-border bg-card',
                !court.is_active && 'opacity-60',
            )}
        >
            <div className="flex items-center justify-between gap-3 px-4 py-3">
                <div className="flex min-w-0 items-center gap-2.5">
                    <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <LandPlot className="size-4" />
                    </span>
                    <div className="min-w-0">
                        <p className="truncate text-sm leading-tight font-semibold">
                            {court.name}
                        </p>
                        <p className="truncate text-xs text-muted-foreground">
                            {court.type_label} Court
                            {!court.is_active && ' · Inactive'}
                        </p>
                    </div>
                </div>

                <div className="flex shrink-0 items-center gap-1.5">
                    <SummaryPill
                        count={court.summary.available}
                        label="Available"
                        tone="available"
                    />
                    <SummaryPill
                        count={court.summary.booked}
                        label="Booked"
                        tone="booked"
                    />
                    <SummaryPill
                        count={court.summary.session}
                        label="Open Play"
                        tone="session"
                    />

                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="size-7"
                                aria-label={`${court.name} actions`}
                            >
                                <MoreVertical className="size-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem asChild>
                                <Link href={editCourt({ court: court.id })}>
                                    <Pencil className="size-4" />
                                    Edit court
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <Link href={courtSchedule({ court: court.id })}>
                                    <CalendarDays className="size-4" />
                                    Weekly schedule
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <Link
                                    href={courtAvailability({
                                        court: court.id,
                                    })}
                                >
                                    <Clock className="size-4" />
                                    Availability
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <div className="overflow-x-auto">
                <table className="w-full border-collapse text-[10px]">
                    <thead>
                        <tr>
                            <th className="w-16 border-y border-border bg-muted/20" />
                            {schedule.days.map((day) => (
                                <th
                                    key={day.date}
                                    scope="col"
                                    className="relative border-y border-border bg-muted/20 px-1 py-2 font-medium"
                                >
                                    <span
                                        className={cn(
                                            'block leading-tight',
                                            day.is_today && 'text-primary',
                                        )}
                                    >
                                        {day.weekday_label}
                                    </span>
                                    <span
                                        className={cn(
                                            'block leading-tight font-normal',
                                            day.is_today
                                                ? 'text-primary'
                                                : 'text-muted-foreground',
                                        )}
                                    >
                                        {day.day_label}
                                    </span>
                                    {day.is_today && (
                                        <span className="absolute inset-x-3 bottom-0 h-0.5 rounded-full bg-primary" />
                                    )}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {schedule.hours.map((row) => (
                            <tr key={row.hour}>
                                <th
                                    scope="row"
                                    className="border border-border/60 px-2 py-1.5 text-left font-normal text-muted-foreground tabular-nums"
                                >
                                    {row.label}
                                </th>
                                {schedule.days.map((day) => {
                                    const cell =
                                        court.cells[day.date]?.[row.hour] ??
                                        CLOSED_CELL;

                                    return (
                                        <td
                                            key={day.date}
                                            title={cell.title ?? undefined}
                                            className={cn(
                                                'border border-border/60 px-1 py-1.5 text-center',
                                                STATUS_META[cell.status].cell,
                                            )}
                                        >
                                            <ScheduleCell cell={cell} />
                                        </td>
                                    );
                                })}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

export default function SchedulePage({ date, schedule }: SchedulePageProps) {
    const goToWeek = (value: string) => {
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
            <Head title="Court Booking" />

            <div className="flex flex-col gap-4">
                <div className="grid grid-cols-1 items-center gap-3 sm:grid-cols-[1fr_auto_1fr]">
                    <div>
                        <h1 className="text-base leading-tight font-semibold">
                            Court Booking
                        </h1>
                        <p className="text-xs text-muted-foreground">
                            View availability across every court
                        </p>
                    </div>

                    <div className="flex items-center gap-2 justify-self-start sm:justify-self-center">
                        <Button
                            variant="outline"
                            size="icon"
                            className="size-8"
                            aria-label="Previous week"
                            onClick={() => goToWeek(schedule.prev_date)}
                        >
                            <ArrowLeft className="size-4" />
                        </Button>

                        <label className="relative flex h-8 cursor-pointer items-center gap-2 rounded-lg border border-border bg-card px-3 text-sm">
                            <Calendar className="size-4 text-muted-foreground" />
                            <span className="whitespace-nowrap">
                                {schedule.range_label}
                            </span>
                            <ChevronDown className="size-4 text-muted-foreground" />
                            <input
                                type="date"
                                value={date}
                                aria-label="Jump to week"
                                className="absolute inset-0 cursor-pointer opacity-0"
                                onChange={(event) =>
                                    goToWeek(event.target.value)
                                }
                            />
                        </label>

                        <Button
                            variant="outline"
                            size="icon"
                            className="size-8"
                            aria-label="Next week"
                            onClick={() => goToWeek(schedule.next_date)}
                        >
                            <ArrowRight className="size-4" />
                        </Button>
                    </div>

                    <Button
                        variant="outline"
                        size="sm"
                        className="h-8 justify-self-start sm:justify-self-end"
                        onClick={() => goToWeek(schedule.today)}
                    >
                        Today
                    </Button>
                </div>

                {!hasCourts ? (
                    <div className="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-border bg-muted/20 p-12 text-center">
                        <CalendarX2 className="size-6 text-muted-foreground" />
                        <p className="font-medium">No courts to display</p>
                        <p className="max-w-sm text-sm text-muted-foreground">
                            Add a court to see its weekly schedule here.
                        </p>
                    </div>
                ) : (
                    <>
                        <div className="grid gap-4 xl:grid-cols-2">
                            {schedule.courts.map((court) => (
                                <CourtPanel
                                    key={court.id}
                                    court={court}
                                    schedule={schedule}
                                />
                            ))}
                        </div>

                        <div className="flex w-fit flex-wrap items-start gap-x-6 gap-y-2 rounded-xl border border-border bg-card px-4 py-2.5">
                            {LEGEND.map((status) => {
                                const meta = STATUS_META[status];

                                return (
                                    <span
                                        key={status}
                                        className="flex items-start gap-2"
                                    >
                                        <span
                                            className={cn(
                                                'mt-1 size-2 shrink-0 rounded-full',
                                                meta.dot,
                                            )}
                                        />
                                        <span>
                                            <span className="block text-[11px] font-medium">
                                                {meta.label}
                                            </span>
                                            <span className="block text-[10px] text-muted-foreground">
                                                {meta.hint}
                                            </span>
                                        </span>
                                    </span>
                                );
                            })}
                        </div>
                    </>
                )}
            </div>
        </>
    );
}

SchedulePage.layout = {
    title: 'Court Booking',
};
