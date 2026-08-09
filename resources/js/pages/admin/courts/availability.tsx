import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, CalendarX2, Circle } from 'lucide-react';
import { CourtBlockManager } from '@/components/admin/courts/court-block-manager';
import { CourtManageTabs } from '@/components/admin/courts/court-manage-tabs';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { index } from '@/routes/admin/courts';
import { index as availabilityRoute } from '@/routes/admin/courts/availability';
import type {
    AvailabilityStatus,
    Court,
    CourtBlock,
    DayAvailability,
    SelectOption,
} from '@/types';

type AvailabilityPageProps = {
    court: { data: Court };
    date: string;
    availability: DayAvailability;
    blocks: { data: CourtBlock[] };
    blockReasons: SelectOption[];
};

const STATUS_STYLES: Record<AvailabilityStatus, string> = {
    available:
        'border-primary/30 bg-primary/5 text-foreground [&_svg]:text-primary',
    blocked:
        'border-destructive/30 bg-destructive/5 text-muted-foreground [&_svg]:text-destructive',
    booked: 'border-border bg-muted text-muted-foreground [&_svg]:text-muted-foreground',
};

const STATUS_LABEL: Record<AvailabilityStatus, string> = {
    available: 'Available',
    blocked: 'Blocked',
    booked: 'Booked',
};

export default function CourtAvailability({
    court,
    date,
    availability,
    blocks,
    blockReasons,
}: AvailabilityPageProps) {
    const courtData = court.data;

    const changeDate = (value: string) => {
        if (!value) {
            return;
        }

        router.get(
            availabilityRoute.url({ court: courtData.id }),
            { date: value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    return (
        <>
            <Head title={`${courtData.name} · Availability`} />

            <div className="mx-auto flex w-full max-w-4xl flex-col gap-6">
                <div>
                    <Link
                        href={index()}
                        className="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Back to courts
                    </Link>
                    <h1 className="text-xl font-semibold">{courtData.name}</h1>
                    <p className="text-sm text-muted-foreground">
                        Review hourly availability and manage blocks.
                    </p>
                </div>

                <CourtManageTabs courtId={courtData.id} />

                <div className="grid gap-6 lg:grid-cols-[1fr_360px]">
                    <Card>
                        <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                            <div className="grid gap-2">
                                <CardTitle>Hourly availability</CardTitle>
                                <p className="text-sm text-muted-foreground">
                                    {availability.day_label}
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
                        </CardHeader>
                        <CardContent>
                            {!availability.is_open ? (
                                <div className="flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-border bg-muted/20 p-10 text-center">
                                    <CalendarX2 className="size-6 text-muted-foreground" />
                                    <p className="font-medium">
                                        {courtData.is_active
                                            ? 'Closed on this day'
                                            : 'Court is inactive'}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {courtData.is_active
                                            ? 'Update the weekly schedule to open this day.'
                                            : 'Activate the court to manage availability.'}
                                    </p>
                                </div>
                            ) : (
                                <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                    {availability.slots.map((slot) => (
                                        <div
                                            key={slot.start_time}
                                            className={cn(
                                                'flex flex-col gap-1 rounded-lg border p-3',
                                                STATUS_STYLES[slot.status],
                                            )}
                                        >
                                            <span className="text-sm font-medium">
                                                {slot.label}
                                            </span>
                                            <span className="flex items-center gap-1.5 text-xs">
                                                <Circle className="size-2 fill-current" />
                                                {slot.reason ??
                                                    STATUS_LABEL[slot.status]}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent>
                            <CourtBlockManager
                                courtId={courtData.id}
                                date={date}
                                blocks={blocks.data}
                                reasons={blockReasons}
                            />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

CourtAvailability.layout = {
    title: 'Court availability',
};
