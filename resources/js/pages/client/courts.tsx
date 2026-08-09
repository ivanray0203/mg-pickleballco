import { Head } from '@inertiajs/react';
import { LandPlot } from 'lucide-react';
import { CourtCard } from '@/components/client/court-card';
import { CourtFilters } from '@/components/client/court-filters';
import { Pagination } from '@/components/shared/pagination';
import type {
    Court,
    CourtFilters as CourtFiltersType,
    Paginated,
    SelectOption,
} from '@/types';

type CourtsProps = {
    courts: Paginated<Court>;
    filters: CourtFiltersType;
    courtTypes: SelectOption[];
};

export default function ClientCourts({
    courts,
    filters,
    courtTypes,
}: CourtsProps) {
    const isEmpty = courts.data.length === 0;
    const hasFilters = Boolean(filters.type) || Boolean(filters.search);

    return (
        <>
            <Head title="Book a Court" />

            <div className="flex flex-col gap-6">
                <div>
                    <h1 className="text-2xl font-semibold">Book a Court</h1>
                    <p className="text-sm text-muted-foreground">
                        Find the perfect court for your next game.
                    </p>
                </div>

                <CourtFilters filters={filters} courtTypes={courtTypes} />

                {isEmpty ? (
                    <div className="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border bg-background p-12 text-center">
                        <span className="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <LandPlot className="size-6" />
                        </span>
                        <div className="grid gap-1">
                            <p className="font-medium">
                                {hasFilters
                                    ? 'No courts match your filters'
                                    : 'No courts available yet'}
                            </p>
                            <p className="max-w-sm text-sm text-muted-foreground">
                                {hasFilters
                                    ? 'Try a different type or search term.'
                                    : 'Please check back soon.'}
                            </p>
                        </div>
                    </div>
                ) : (
                    <>
                        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
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
