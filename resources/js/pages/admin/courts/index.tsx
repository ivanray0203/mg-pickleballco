import { Head, Link } from '@inertiajs/react';
import { LandPlot, Plus } from 'lucide-react';
import { CourtCard } from '@/components/admin/courts/court-card';
import { CourtFilters } from '@/components/admin/courts/court-filters';
import { CourtTable } from '@/components/admin/courts/court-table';
import { Pagination } from '@/components/shared/pagination';
import { Button } from '@/components/ui/button';
import { create } from '@/routes/admin/courts';
import type {
    Court,
    CourtFilters as CourtFiltersType,
    Paginated,
    SelectOption,
} from '@/types';

type CourtsIndexProps = {
    courts: Paginated<Court>;
    filters: CourtFiltersType;
    courtTypes: SelectOption[];
};

export default function CourtsIndex({
    courts,
    filters,
    courtTypes,
}: CourtsIndexProps) {
    const hasFilters =
        Boolean(filters.search) ||
        Boolean(filters.type) ||
        Boolean(filters.status);
    const isEmpty = courts.data.length === 0;

    return (
        <>
            <Head title="Courts" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Courts</h1>
                        <p className="text-sm text-muted-foreground">
                            Manage your pickleball courts, pricing and
                            availability.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={create()}>
                            <Plus className="size-4" />
                            New court
                        </Link>
                    </Button>
                </div>

                <CourtFilters filters={filters} courtTypes={courtTypes} />

                {isEmpty ? (
                    <div className="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border bg-muted/20 p-12 text-center">
                        <span className="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <LandPlot className="size-6" />
                        </span>
                        <div className="grid gap-1">
                            <p className="font-medium">
                                {hasFilters
                                    ? 'No courts match your filters'
                                    : 'No courts yet'}
                            </p>
                            <p className="max-w-sm text-sm text-muted-foreground">
                                {hasFilters
                                    ? 'Try adjusting your search or filters.'
                                    : 'Get started by adding your first court.'}
                            </p>
                        </div>
                        {!hasFilters && (
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus className="size-4" />
                                    New court
                                </Link>
                            </Button>
                        )}
                    </div>
                ) : (
                    <>
                        <CourtTable courts={courts.data} />

                        <div className="grid gap-4 sm:grid-cols-2 md:hidden">
                            {courts.data.map((court) => (
                                <CourtCard key={court.id} court={court} />
                            ))}
                        </div>

                        <Pagination meta={courts.meta} />
                    </>
                )}
            </div>
        </>
    );
}

CourtsIndex.layout = {
    title: 'Courts',
};
