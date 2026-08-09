import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { index } from '@/routes/courts';
import type { CourtFilters as CourtFiltersType, SelectOption } from '@/types';

type CourtFiltersProps = {
    filters: CourtFiltersType;
    courtTypes: SelectOption[];
};

export function CourtFilters({ filters, courtTypes }: CourtFiltersProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const isFirstRender = useRef(true);

    const apply = (next: Partial<CourtFiltersType>) => {
        router.get(
            index.url(),
            {
                type: filters.type ?? '',
                search: filters.search ?? '',
                ...next,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    // Debounce the free-text search.
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            if (search !== (filters.search ?? '')) {
                apply({ search });
            }
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const tabs: SelectOption[] = [{ value: '', label: 'All' }, ...courtTypes];
    const activeType = filters.type ?? '';

    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex gap-1 rounded-lg bg-muted p-1">
                {tabs.map((tab) => (
                    <button
                        key={tab.value || 'all'}
                        type="button"
                        onClick={() => apply({ type: tab.value })}
                        className={cn(
                            'rounded-md px-4 py-1.5 text-sm font-medium transition-colors',
                            activeType === tab.value
                                ? 'bg-background text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground',
                        )}
                    >
                        {tab.label}
                    </button>
                ))}
            </div>

            <div className="relative sm:w-64">
                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    type="search"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="Search courts…"
                    className="pl-9"
                    aria-label="Search courts"
                />
            </div>
        </div>
    );
}
