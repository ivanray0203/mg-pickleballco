import type { LucideIcon } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';

export type StatCardProps = {
    title: string;
    value: string | number;
    icon: LucideIcon;
    description?: string;
    /** Show a loading skeleton in place of the value. */
    loading?: boolean;
    className?: string;
};

/**
 * Reusable statistic card for dashboards. Renders a title, an icon, a primary
 * value, and optional supporting text, with a built-in loading state.
 */
export function StatCard({
    title,
    value,
    icon: Icon,
    description,
    loading = false,
    className,
}: StatCardProps) {
    return (
        <Card className={cn('gap-2', className)}>
            <CardHeader className="flex flex-row items-center justify-between gap-2 space-y-0">
                <CardTitle className="text-sm font-medium text-muted-foreground">
                    {title}
                </CardTitle>
                <span className="flex size-9 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <Icon className="size-4" />
                </span>
            </CardHeader>
            <CardContent>
                {loading ? (
                    <Skeleton className="h-8 w-24" />
                ) : (
                    <div className="text-2xl font-bold tracking-tight">
                        {value}
                    </div>
                )}
                {description && (
                    <p className="mt-1 text-xs text-muted-foreground">
                        {description}
                    </p>
                )}
            </CardContent>
        </Card>
    );
}
