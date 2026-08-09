import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export function CourtStatusBadge({ active }: { active: boolean }) {
    return (
        <Badge
            variant="outline"
            className={cn(
                'gap-1.5',
                active
                    ? 'border-primary/30 bg-primary/10 text-primary'
                    : 'border-border bg-muted text-muted-foreground',
            )}
        >
            <span
                className={cn(
                    'size-1.5 rounded-full',
                    active ? 'bg-primary' : 'bg-muted-foreground',
                )}
            />
            {active ? 'Active' : 'Inactive'}
        </Badge>
    );
}
