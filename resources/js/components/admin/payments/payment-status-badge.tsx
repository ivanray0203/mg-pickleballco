import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { PaymentStatus } from '@/types';

const STATUS_STYLES: Record<PaymentStatus, string> = {
    pending: 'border-amber-500/30 bg-amber-500/10 text-amber-600',
    paid: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-600',
    failed: 'border-destructive/30 bg-destructive/10 text-destructive',
    expired: 'border-border bg-muted text-muted-foreground',
    refunded: 'border-primary/30 bg-primary/10 text-primary',
};

const DOT_STYLES: Record<PaymentStatus, string> = {
    pending: 'bg-amber-500',
    paid: 'bg-emerald-500',
    failed: 'bg-destructive',
    expired: 'bg-muted-foreground',
    refunded: 'bg-primary',
};

type PaymentStatusBadgeProps = {
    status: PaymentStatus;
    label: string;
};

export function PaymentStatusBadge({ status, label }: PaymentStatusBadgeProps) {
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
