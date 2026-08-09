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
import { index } from '@/routes/admin/courts';
import type { CourtFilters as CourtFiltersType, SelectOption } from '@/types';

type CourtFiltersProps = {
    filters: CourtFiltersType;
    courtTypes: SelectOption[];
};

const ALL = 'all';

export function CourtFilters({ filters, courtTypes }: CourtFiltersProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const isFirstRender = useRef(true);

    const applyFilters = (next: Partial<CourtFiltersType>) => {
        const query = {
            search: filters.search ?? '',
            type: filters.type ?? '',
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
        Boolean(filters.type) ||
        Boolean(filters.status);

    const clearFilters = () => {
        setSearch('');
        router.get(index.url(), {}, { preserveScroll: true, replace: true });
    };

    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div className="relative flex-1">
                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    type="search"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="Search by court name…"
                    className="pl-9"
                    aria-label="Search courts"
                />
            </div>

            <div className="flex gap-3">
                <Select
                    value={filters.type ?? ALL}
                    onValueChange={(value) =>
                        applyFilters({ type: value === ALL ? '' : value })
                    }
                >
                    <SelectTrigger
                        className="w-full sm:w-40"
                        aria-label="Filter by type"
                    >
                        <SelectValue placeholder="Type" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>All types</SelectItem>
                        {courtTypes.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
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
                        className="w-full sm:w-40"
                        aria-label="Filter by status"
                    >
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>All statuses</SelectItem>
                        <SelectItem value="active">Active</SelectItem>
                        <SelectItem value="inactive">Inactive</SelectItem>
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
