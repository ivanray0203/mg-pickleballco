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
import { index } from '@/routes/admin/payments';
import type {
    PaymentFilters as PaymentFiltersType,
    SelectOption,
} from '@/types';

type PaymentFiltersProps = {
    filters: PaymentFiltersType;
    statuses: SelectOption[];
    methods: SelectOption[];
};

const ALL = 'all';

export function PaymentFilters({
    filters,
    statuses,
    methods,
}: PaymentFiltersProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const isFirstRender = useRef(true);

    const applyFilters = (next: Partial<PaymentFiltersType>) => {
        const query = {
            search: filters.search ?? '',
            status: filters.status ?? '',
            method: filters.method ?? '',
            date: filters.date ?? '',
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
        Boolean(filters.status) ||
        Boolean(filters.method) ||
        Boolean(filters.date);

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
                    aria-label="Search payments"
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
                    value={filters.method ?? ALL}
                    onValueChange={(value) =>
                        applyFilters({ method: value === ALL ? '' : value })
                    }
                >
                    <SelectTrigger
                        className="w-full sm:w-36"
                        aria-label="Filter by method"
                    >
                        <SelectValue placeholder="Method" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>All methods</SelectItem>
                        {methods.map((option) => (
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
