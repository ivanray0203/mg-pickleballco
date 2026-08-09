import { cn } from '@/lib/utils';

export type BarChartDatum = {
    label: string;
    value: number;
    /** Optional pre-formatted value shown instead of the raw number. */
    display?: string;
};

type BarChartProps = {
    data: BarChartDatum[];
    /** Layout: horizontal bars (default) or vertical columns. */
    orientation?: 'horizontal' | 'vertical';
    emptyMessage?: string;
    className?: string;
};

/**
 * Lightweight, dependency-free bar chart built from CSS/flex primitives. Works
 * in both light and dark themes and is fully responsive. Two orientations:
 * horizontal (good for ranked categories) and vertical (good for time series).
 */
export function BarChart({
    data,
    orientation = 'horizontal',
    emptyMessage = 'No data for this period.',
    className,
}: BarChartProps) {
    if (data.length === 0) {
        return (
            <p className="rounded-lg border border-dashed border-border bg-muted/20 p-6 text-center text-sm text-muted-foreground">
                {emptyMessage}
            </p>
        );
    }

    const max = Math.max(...data.map((d) => d.value), 1);

    if (orientation === 'vertical') {
        return (
            <div
                className={cn(
                    'flex items-end gap-2 overflow-x-auto',
                    className,
                )}
                role="img"
                aria-label="Bar chart"
            >
                {data.map((datum) => (
                    <div
                        key={datum.label}
                        className="flex min-w-8 flex-1 flex-col items-center gap-1"
                    >
                        <span className="text-xs font-medium text-muted-foreground">
                            {datum.display ?? datum.value}
                        </span>
                        <div className="flex h-32 w-full items-end">
                            <div
                                className="w-full rounded-t-md bg-primary/80 transition-all"
                                style={{
                                    height: `${Math.max((datum.value / max) * 100, 2)}%`,
                                }}
                            />
                        </div>
                        <span className="text-xs text-muted-foreground">
                            {datum.label}
                        </span>
                    </div>
                ))}
            </div>
        );
    }

    return (
        <div
            className={cn('flex flex-col gap-3', className)}
            role="img"
            aria-label="Bar chart"
        >
            {data.map((datum) => (
                <div key={datum.label} className="grid gap-1">
                    <div className="flex items-center justify-between text-sm">
                        <span className="font-medium">{datum.label}</span>
                        <span className="text-muted-foreground">
                            {datum.display ?? datum.value}
                        </span>
                    </div>
                    <div className="h-2.5 w-full overflow-hidden rounded-full bg-muted">
                        <div
                            className="h-full rounded-full bg-primary transition-all"
                            style={{
                                width: `${Math.max((datum.value / max) * 100, 2)}%`,
                            }}
                        />
                    </div>
                </div>
            ))}
        </div>
    );
}
