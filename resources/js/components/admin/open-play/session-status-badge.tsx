import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { OpenPlaySessionStatus } from '@/types';

const STATUS_STYLES: Record<OpenPlaySessionStatus, string> = {
    scheduled: 'border-border bg-muted text-muted-foreground',
    open: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-600',
    full: 'border-amber-500/30 bg-amber-500/10 text-amber-600',
    in_progress: 'border-primary/30 bg-primary/10 text-primary',
    completed: 'border-border bg-muted text-muted-foreground',
    cancelled: 'border-destructive/30 bg-destructive/10 text-destructive',
};

const DOT_STYLES: Record<OpenPlaySessionStatus, string> = {
    scheduled: 'bg-muted-foreground',
    open: 'bg-emerald-500',
    full: 'bg-amber-500',
    in_progress: 'bg-primary',
    completed: 'bg-muted-foreground',
    cancelled: 'bg-destructive',
};

type SessionStatusBadgeProps = {
    status: OpenPlaySessionStatus;
    label: string;
};

export function SessionStatusBadge({ status, label }: SessionStatusBadgeProps) {
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
