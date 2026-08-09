import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { EquipmentForm } from '@/components/admin/equipment/equipment-form';
import { EquipmentStatusBadge } from '@/components/admin/equipment/equipment-status-badge';
import { Card, CardContent } from '@/components/ui/card';
import { index, update } from '@/routes/admin/equipment';
import type { Equipment, SelectOption } from '@/types';

type EditEquipmentProps = {
    equipment: { data: Equipment };
    equipmentTypes: SelectOption[];
};

export default function EditEquipment({
    equipment,
    equipmentTypes,
}: EditEquipmentProps) {
    const data = equipment.data;

    return (
        <>
            <Head title={`Edit ${data.name}`} />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6">
                <div>
                    <Link
                        href={index()}
                        className="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Back to equipment
                    </Link>
                    <div className="flex items-center gap-3">
                        <h1 className="text-xl font-semibold">{data.name}</h1>
                        <EquipmentStatusBadge active={data.is_active} />
                    </div>
                    <p className="text-sm text-muted-foreground">
                        Update this equipment's details.
                    </p>
                </div>

                <Card>
                    <CardContent>
                        <EquipmentForm
                            equipment={data}
                            equipmentTypes={equipmentTypes}
                            submitUrl={update.url({ equipment: data.id })}
                            method="patch"
                            submitLabel="Save changes"
                            onCancel={() => router.visit(index.url())}
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

EditEquipment.layout = {
    title: 'Edit equipment',
};
