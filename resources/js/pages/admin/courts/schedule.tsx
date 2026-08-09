import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, LoaderCircle } from 'lucide-react';
import { CourtManageTabs } from '@/components/admin/courts/court-manage-tabs';
import { HourSelect } from '@/components/admin/courts/hour-select';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/admin/courts';
import { update } from '@/routes/admin/courts/schedule';
import type { Court, CourtScheduleDay } from '@/types';

type ScheduleDayForm = {
    day_of_week: number;
    day_label: string;
    is_open: boolean;
    opening_time: string | null;
    closing_time: string | null;
};

type SchedulePageProps = {
    court: { data: Court };
    schedules: { data: CourtScheduleDay[] };
};

export default function CourtSchedule({ court, schedules }: SchedulePageProps) {
    const courtData = court.data;

    const { data, setData, put, processing, errors } = useForm<{
        days: ScheduleDayForm[];
    }>({
        days: schedules.data.map((day) => ({
            day_of_week: day.day_of_week,
            day_label: day.day_label,
            is_open: day.is_open,
            opening_time: day.opening_time ?? '06:00',
            closing_time: day.closing_time ?? '22:00',
        })),
    });

    const dayErrors = errors as Record<string, string>;

    const updateDay = (index: number, patch: Partial<ScheduleDayForm>) => {
        setData(
            'days',
            data.days.map((day, i) =>
                i === index ? { ...day, ...patch } : day,
            ),
        );
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        put(update.url({ court: courtData.id }), { preserveScroll: true });
    };

    return (
        <>
            <Head title={`${courtData.name} · Schedule`} />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6">
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
                        Set the weekly operating hours. Times are whole hours
                        only.
                    </p>
                </div>

                <CourtManageTabs courtId={courtData.id} />

                <form onSubmit={submit}>
                    <Card>
                        <CardContent className="flex flex-col divide-y divide-border">
                            {data.days.map((day, index) => (
                                <div
                                    key={day.day_of_week}
                                    className="flex flex-col gap-3 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:gap-4"
                                >
                                    <div className="flex min-w-40 items-center gap-2">
                                        <Checkbox
                                            id={`open-${day.day_of_week}`}
                                            checked={day.is_open}
                                            onCheckedChange={(checked) =>
                                                updateDay(index, {
                                                    is_open: checked === true,
                                                })
                                            }
                                        />
                                        <Label
                                            htmlFor={`open-${day.day_of_week}`}
                                            className="cursor-pointer font-medium"
                                        >
                                            {day.day_label}
                                        </Label>
                                    </div>

                                    {day.is_open ? (
                                        <div className="flex flex-1 flex-col gap-3 sm:flex-row sm:items-start">
                                            <div className="grid flex-1 gap-1">
                                                <HourSelect
                                                    aria-label={`${day.day_label} opening time`}
                                                    value={day.opening_time}
                                                    onChange={(value) =>
                                                        updateDay(index, {
                                                            opening_time: value,
                                                        })
                                                    }
                                                />
                                                <InputError
                                                    message={
                                                        dayErrors[
                                                            `days.${index}.opening_time`
                                                        ]
                                                    }
                                                />
                                            </div>
                                            <span className="hidden pt-2 text-muted-foreground sm:block">
                                                –
                                            </span>
                                            <div className="grid flex-1 gap-1">
                                                <HourSelect
                                                    aria-label={`${day.day_label} closing time`}
                                                    value={day.closing_time}
                                                    onChange={(value) =>
                                                        updateDay(index, {
                                                            closing_time: value,
                                                        })
                                                    }
                                                />
                                                <InputError
                                                    message={
                                                        dayErrors[
                                                            `days.${index}.closing_time`
                                                        ]
                                                    }
                                                />
                                            </div>
                                        </div>
                                    ) : (
                                        <p className="flex-1 text-sm text-muted-foreground">
                                            Closed
                                        </p>
                                    )}
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <div className="mt-4 flex justify-end">
                        <Button type="submit" disabled={processing}>
                            {processing && (
                                <LoaderCircle className="size-4 animate-spin" />
                            )}
                            Save schedule
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

CourtSchedule.layout = {
    title: 'Court schedule',
};
