import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { EquipmentForm } from '@/components/admin/equipment/equipment-form';
import { Card, CardContent } from '@/components/ui/card';
import { index, store } from '@/routes/admin/equipment';
import type { SelectOption } from '@/types';

export default function CreateEquipment({
    equipmentTypes,
}: {
    equipmentTypes: SelectOption[];
}) {
    return (
        <>
            <Head title="New equipment" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6">
                <div>
                    <Link
                        href={index()}
                        className="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Back to equipment
                    </Link>
                    <h1 className="text-xl font-semibold">New equipment</h1>
                    <p className="text-sm text-muted-foreground">
                        Add a rentable item to your catalogue.
                    </p>
                </div>

                <Card>
                    <CardContent>
                        <EquipmentForm
                            equipmentTypes={equipmentTypes}
                            submitUrl={store.url()}
                            method="post"
                            submitLabel="Create equipment"
                            onCancel={() => router.visit(index.url())}
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CreateEquipment.layout = {
    title: 'New equipment',
};
