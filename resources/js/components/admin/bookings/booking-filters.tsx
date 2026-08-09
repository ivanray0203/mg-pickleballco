import { router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index } from '@/routes/admin/bookings';
import type {
    BookingFilters as BookingFiltersType,
    SelectOption,
} from '@/types';

type BookingFiltersProps = {
    filters: BookingFiltersType;
    courts: Array<{ id: number; name: string }>;
    statuses: SelectOption[];
};

const ALL = 'all';

export function BookingFilters({
    filters,
    courts,
    statuses,
}: BookingFiltersProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const isFirstRender = useRef(true);

    const applyFilters = (next: Partial<BookingFiltersType>) => {
        const query = {
            search: filters.search ?? '',
            date: filters.date ?? '',
            court: filters.court ?? '',
            status: filters.status ?? '',
            ...next,
        };

        router.get(index.url(), query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    // Debounce the free-text search so we don't navigate on every keystroke.
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            if (search !== (filters.search ?? '')) {
                applyFilters({ search });
            }
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const hasActiveFilters =
        Boolean(filters.search) ||
        Boolean(filters.date) ||
        Boolean(filters.court) ||
        Boolean(filters.status);

    const clearFilters = () => {
        setSearch('');
        router.get(index.url(), {}, { preserveScroll: true, replace: true });
    };

    return (
        <div className="flex flex-col gap-3 lg:flex-row lg:items-center">
            <div className="relative flex-1">
                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    type="search"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="Search by reference or customer…"
                    className="pl-9"
                    aria-label="Search bookings"
                />
            </div>

            <div className="flex flex-wrap gap-3">
                <Input
                    type="date"
                    value={filters.date ?? ''}
                    onChange={(e) =>
                        applyFilters({ date: e.target.value || '' })
                    }
                    className="w-full sm:w-40"
                    aria-label="Filter by date"
                />

                <Select
                    value={filters.court ?? ALL}
                    onValueChange={(value) =>
                        applyFilters({ court: value === ALL ? '' : value })
                    }
                >
                    <SelectTrigger
                        className="w-full sm:w-40"
                        aria-label="Filter by court"
                    >
                        <SelectValue placeholder="Court" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>All courts</SelectItem>
                        {courts.map((court) => (
                            <SelectItem key={court.id} value={String(court.id)}>
                                {court.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                <Select
                    value={filters.status ?? ALL}
                    onValueChange={(value) =>
                        applyFilters({ status: value === ALL ? '' : value })
                    }
                >
                    <SelectTrigger
                        className="w-full sm:w-44"
                        aria-label="Filter by status"
                    >
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>All statuses</SelectItem>
                        {statuses.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                {hasActiveFilters && (
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={clearFilters}
                        aria-label="Clear filters"
                    >
                        <X className="size-4" />
                    </Button>
                )}
            </div>
        </div>
    );
}
