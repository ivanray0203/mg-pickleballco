import { Head, Link } from '@inertiajs/react';
import { Dumbbell, Plus } from 'lucide-react';
import { EquipmentCard } from '@/components/admin/equipment/equipment-card';
import { EquipmentFilters } from '@/components/admin/equipment/equipment-filters';
import { EquipmentTable } from '@/components/admin/equipment/equipment-table';
import { Pagination } from '@/components/shared/pagination';
import { Button } from '@/components/ui/button';
import { create } from '@/routes/admin/equipment';
import type {
    Equipment,
    EquipmentFilters as EquipmentFiltersType,
    Paginated,
    SelectOption,
} from '@/types';

type EquipmentIndexProps = {
    equipment: Paginated<Equipment>;
    filters: EquipmentFiltersType;
    equipmentTypes: SelectOption[];
};

export default function EquipmentIndex({
    equipment,
    filters,
    equipmentTypes,
}: EquipmentIndexProps) {
    const hasFilters =
        Boolean(filters.search) ||
        Boolean(filters.type) ||
        Boolean(filters.status);
    const isEmpty = equipment.data.length === 0;

    return (
        <>
            <Head title="Equipment" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Equipment</h1>
                        <p className="text-sm text-muted-foreground">
                            Manage rentable paddles, balls and other gear.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={create()}>
                            <Plus className="size-4" />
                            New equipment
                        </Link>
                    </Button>
                </div>

                <EquipmentFilters
                    filters={filters}
                    equipmentTypes={equipmentTypes}
                />

                {isEmpty ? (
                    <div className="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border bg-muted/20 p-12 text-center">
                        <span className="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <Dumbbell className="size-6" />
                        </span>
                        <div className="grid gap-1">
                            <p className="font-medium">
                                {hasFilters
                                    ? 'No equipment matches your filters'
                                    : 'No equipment yet'}
                            </p>
                            <p className="max-w-sm text-sm text-muted-foreground">
                                {hasFilters
                                    ? 'Try adjusting your search or filters.'
                                    : 'Add rentable gear so customers can add it to a booking.'}
                            </p>
                        </div>
                        {!hasFilters && (
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus className="size-4" />
                                    New equipment
                                </Link>
                            </Button>
                        )}
                    </div>
                ) : (
                    <>
                        <EquipmentTable equipment={equipment.data} />

                        <div className="grid gap-4 sm:grid-cols-2 md:hidden">
                            {equipment.data.map((item) => (
                                <EquipmentCard key={item.id} equipment={item} />
                            ))}
                        </div>

                        <Pagination meta={equipment.meta} />
                    </>
                )}
            </div>
        </>
    );
}

EquipmentIndex.layout = {
    title: 'Equipment',
};
