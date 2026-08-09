import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type BookingSummaryProps = {
    courtName: string;
    dateLabel: string;
    startLabel: string;
    endLabel: string;
    hours: number;
    hourlyRate: number;
};

function peso(value: number): string {
    return `₱${value.toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
}

/**
 * Price preview for the current selection. Presentational only — the backend
 * recomputes and remains authoritative for the final total.
 */
export function BookingSummary({
    courtName,
    dateLabel,
    startLabel,
    endLabel,
    hours,
    hourlyRate,
}: BookingSummaryProps) {
    const total = hours * hourlyRate;

    return (
        <Card className="border-primary/20 bg-primary/5">
            <CardHeader>
                <CardTitle>{courtName}</CardTitle>
            </CardHeader>
            <CardContent className="flex flex-col gap-3 text-sm">
                <div className="flex justify-between">
                    <span className="text-muted-foreground">Date</span>
                    <span className="font-medium">{dateLabel}</span>
                </div>
                <div className="flex justify-between">
                    <span className="text-muted-foreground">Time</span>
                    <span className="font-medium">
                        {startLabel} – {endLabel}
                    </span>
                </div>
                <div className="flex justify-between">
                    <span className="text-muted-foreground">
                        {hours} {hours === 1 ? 'hour' : 'hours'} ×{' '}
                        {peso(hourlyRate)}
                    </span>
                    <span className="font-medium">{peso(total)}</span>
                </div>
                <div className="flex items-baseline justify-between border-t border-border pt-3">
                    <span className="font-medium">Total</span>
                    <span className="text-2xl font-bold text-primary">
                        {peso(total)}
                    </span>
                </div>
                <p className="text-xs text-muted-foreground">
                    Price preview — confirmed at checkout.
                </p>
            </CardContent>
        </Card>
    );
}
