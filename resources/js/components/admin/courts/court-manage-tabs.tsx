import { Link } from '@inertiajs/react';
import { CalendarDays, Clock, Pencil } from 'lucide-react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import { edit } from '@/routes/admin/courts';
import { index as availability } from '@/routes/admin/courts/availability';
import { edit as schedule } from '@/routes/admin/courts/schedule';

type CourtManageTabsProps = {
    courtId: number;
};

export function CourtManageTabs({ courtId }: CourtManageTabsProps) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    const tabs = [
        { label: 'Details', href: edit({ court: courtId }), icon: Pencil },
        {
            label: 'Schedule',
            href: schedule({ court: courtId }),
            icon: CalendarDays,
        },
        {
            label: 'Availability',
            href: availability({ court: courtId }),
            icon: Clock,
        },
    ];

    return (
        <div className="flex flex-wrap gap-1 rounded-lg border border-border bg-muted/40 p-1">
            {tabs.map((tab) => {
                const active = isCurrentOrParentUrl(tab.href);

                return (
                    <Link
                        key={tab.label}
                        href={tab.href}
                        className={cn(
                            'inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                            active
                                ? 'bg-background text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground',
                        )}
                    >
                        <tab.icon className="size-4" />
                        {tab.label}
                    </Link>
                );
            })}
        </div>
    );
}
