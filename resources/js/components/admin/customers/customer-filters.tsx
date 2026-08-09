import { router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index } from '@/routes/admin/customers';
import type { CustomerFilters as CustomerFiltersType } from '@/types';

export function CustomerFilters({ filters }: { filters: CustomerFiltersType }) {
    const [search, setSearch] = useState(filters.search ?? '');
    const isFirstRender = useRef(true);

    // Debounce the free-text search so we don't navigate on every keystroke.
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            if (search !== (filters.search ?? '')) {
                router.get(
                    index.url(),
                    { search },
                    {
                        preserveState: true,
                        preserveScroll: true,
                        replace: true,
                    },
                );
            }
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    return (
        <div className="flex items-center gap-3">
            <div className="relative flex-1">
                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    type="search"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="Search by name or email…"
                    className="pl-9"
                    aria-label="Search customers"
                />
            </div>

            {Boolean(filters.search) && (
                <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => {
                        setSearch('');
                        router.get(
                            index.url(),
                            {},
                            { preserveScroll: true, replace: true },
                        );
                    }}
                    aria-label="Clear search"
                >
                    <X className="size-4" />
                </Button>
            )}
        </div>
    );
}
