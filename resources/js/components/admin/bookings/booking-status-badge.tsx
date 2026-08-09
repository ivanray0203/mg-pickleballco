import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { BookingStatus } from '@/types';

const STATUS_STYLES: Record<BookingStatus, string> = {
    pending_payment: 'border-amber-500/30 bg-amber-500/10 text-amber-600',
    confirmed: 'border-primary/30 bg-primary/10 text-primary',
    completed: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-600',
    cancelled: 'border-destructive/30 bg-destructive/10 text-destructive',
    expired: 'border-border bg-muted text-muted-foreground',
};

const DOT_STYLES: Record<BookingStatus, string> = {
    pending_payment: 'bg-amber-500',
    confirmed: 'bg-primary',
    completed: 'bg-emerald-500',
    cancelled: 'bg-destructive',
    expired: 'bg-muted-foreground',
};

type BookingStatusBadgeProps = {
    status: BookingStatus;
    label: string;
};

export function BookingStatusBadge({ status, label }: BookingStatusBadgeProps) {
    return (
        <Badge
            variant="outline"
            className={cn('gap-1.5', STATUS_STYLES[status])}
        >
            <span className={cn('size-1.5 rounded-full', DOT_STYLES[status])} />
            {label}
        </Badge>
    );
}
