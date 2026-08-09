import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { CourtForm } from '@/components/admin/courts/court-form';
import { CourtManageTabs } from '@/components/admin/courts/court-manage-tabs';
import { CourtStatusBadge } from '@/components/admin/courts/court-status-badge';
import { Card, CardContent } from '@/components/ui/card';
import { index, update } from '@/routes/admin/courts';
import type { Court, SelectOption } from '@/types';

type EditCourtProps = {
    court: { data: Court };
    courtTypes: SelectOption[];
};

export default function EditCourt({ court, courtTypes }: EditCourtProps) {
    const data = court.data;

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
                        Back to courts
                    </Link>
                    <div className="flex items-center gap-3">
                        <h1 className="text-xl font-semibold">{data.name}</h1>
                        <CourtStatusBadge active={data.is_active} />
                    </div>
                    <p className="text-sm text-muted-foreground">
                        Update this court's details.
                    </p>
                </div>

                <CourtManageTabs courtId={data.id} />

                <Card>
                    <CardContent>
                        <CourtForm
                            court={data}
                            courtTypes={courtTypes}
                            submitUrl={update.url({ court: data.id })}
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

EditCourt.layout = {
    title: 'Edit court',
};
