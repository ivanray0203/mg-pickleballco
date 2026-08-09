import { Head, router } from '@inertiajs/react';
import {
    CalendarCheck,
    CircleCheck,
    CircleX,
    PhilippinePeso,
    TrendingUp,
    TriangleAlert,
} from 'lucide-react';
import { useState } from 'react';
import { BarChart } from '@/components/shared/bar-chart';
import { StatCard } from '@/components/shared/stat-card';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index as reportsRoute } from '@/routes/admin/reports';
import type { ReportData, ReportFilters, ReportRange } from '@/types';

type ReportsProps = {
    report: ReportData;
    filters: ReportFilters;
};

const PRESETS: Array<{ value: ReportRange; label: string }> = [
    { value: 'today', label: 'Today' },
    { value: 'week', label: 'This Week' },
    { value: 'month', label: 'This Month' },
    { value: 'custom', label: 'Custom' },
];

function formatDay(value: string): string {
    return new Date(`${value}T00:00:00`).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });
}

export default function Reports({ report, filters }: ReportsProps) {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);

    const visit = (query: Record<string, string>) => {
        router.get(reportsRoute.url(), query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => {
                setLoading(true);
                setError(null);
            },
            onError: () => setError('Something went wrong loading the report.'),
            onFinish: () => setLoading(false),
        });
    };

    const selectPreset = (range: ReportRange) => {
        if (range === 'custom') {
            visit({ range: 'custom', from, to });

            return;
        }

        visit({ range });
    };

    const { summary } = report;

    // Merge the two per-court breakdowns into a single table.
    const revenueByCourt = new Map(
        report.revenue_by_court.map((r) => [r.court, r.amount_formatted]),
    );
    const courts = Array.from(
        new Set([
            ...report.bookings_by_court.map((b) => b.court),
            ...report.revenue_by_court.map((r) => r.court),
        ]),
    );
    const byCourtRows = courts.map((court) => ({
        court,
        bookings:
            report.bookings_by_court.find((b) => b.court === court)?.count ?? 0,
        revenue: revenueByCourt.get(court) ?? '₱0.00',
    }));

    return (
        <>
            <Head title="Reports" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-4">
                    <div>
                        <h1 className="text-xl font-semibold">Reports</h1>
                        <p className="text-sm text-muted-foreground">
                            Revenue and booking performance for the selected
                            period.
                        </p>
                    </div>

                    <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                        <div className="flex flex-wrap gap-2">
                            {PRESETS.map((preset) => (
                                <Button
                                    key={preset.value}
                                    variant={
                                        filters.range === preset.value
                                            ? 'default'
                                            : 'outline'
                                    }
                                    size="sm"
                                    onClick={() => selectPreset(preset.value)}
                                    disabled={loading}
                                >
                                    {preset.label}
                                </Button>
                            ))}
                        </div>

                        {filters.range === 'custom' && (
                            <div className="flex flex-wrap items-end gap-2">
                                <div className="grid gap-1">
                                    <Label htmlFor="from">From</Label>
                                    <Input
                                        id="from"
                                        type="date"
                                        value={from}
                                        className="w-full sm:w-40"
                                        onChange={(e) =>
                                            setFrom(e.target.value)
                                        }
                                    />
                                </div>
                                <div className="grid gap-1">
                                    <Label htmlFor="to">To</Label>
                                    <Input
                                        id="to"
                                        type="date"
                                        value={to}
                                        className="w-full sm:w-40"
                                        onChange={(e) => setTo(e.target.value)}
                                    />
                                </div>
                                <Button
                                    size="sm"
                                    onClick={() =>
                                        visit({ range: 'custom', from, to })
                                    }
                                    disabled={loading}
                                >
                                    Apply
                                </Button>
                            </div>
                        )}
                    </div>
                </div>

                {error && (
                    <div className="flex items-center gap-2 rounded-lg border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive">
                        <TriangleAlert className="size-4" />
                        {error}
                    </div>
                )}

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    <StatCard
                        title="Total Revenue"
                        value={summary.total_revenue_formatted}
                        icon={PhilippinePeso}
                        loading={loading}
                    />
                    <StatCard
                        title="Total Bookings"
                        value={summary.total_bookings}
                        icon={CalendarCheck}
                        loading={loading}
                    />
                    <StatCard
                        title="Confirmed"
                        value={summary.confirmed_bookings}
                        icon={CircleCheck}
                        loading={loading}
                    />
                    <StatCard
                        title="Cancelled"
                        value={summary.cancelled_bookings}
                        icon={CircleX}
                        loading={loading}
                    />
                    <StatCard
                        title="Avg. Booking Value"
                        value={summary.average_booking_value_formatted}
                        icon={TrendingUp}
                        loading={loading}
                    />
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>By court</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {byCourtRows.length === 0 ? (
                                <p className="rounded-lg border border-dashed border-border bg-muted/20 p-6 text-center text-sm text-muted-foreground">
                                    No bookings for this period.
                                </p>
                            ) : (
                                <div className="overflow-x-auto rounded-lg border border-border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow className="bg-muted/40 hover:bg-muted/40">
                                                <TableHead>Court</TableHead>
                                                <TableHead className="text-right">
                                                    Bookings
                                                </TableHead>
                                                <TableHead className="text-right">
                                                    Revenue
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {byCourtRows.map((row) => (
                                                <TableRow key={row.court}>
                                                    <TableCell className="font-medium">
                                                        {row.court}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        {row.bookings}
                                                    </TableCell>
                                                    <TableCell className="text-right font-medium">
                                                        {row.revenue}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Revenue by court</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <BarChart
                                data={report.revenue_by_court.map((r) => ({
                                    label: r.court,
                                    value: r.amount,
                                    display: r.amount_formatted,
                                }))}
                                emptyMessage="No revenue for this period."
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Bookings by day</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <BarChart
                                orientation="vertical"
                                data={report.bookings_by_day.map((d) => ({
                                    label: formatDay(d.date),
                                    value: d.count,
                                }))}
                                emptyMessage="No bookings for this period."
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Peak booking hours</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <BarChart
                                orientation="vertical"
                                data={report.peak_hours.map((h) => ({
                                    label: h.label,
                                    value: h.count,
                                }))}
                                emptyMessage="No bookings for this period."
                            />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

Reports.layout = {
    title: 'Reports',
};
