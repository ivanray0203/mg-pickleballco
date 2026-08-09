import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { CourtForm } from '@/components/admin/courts/court-form';
import { Card, CardContent } from '@/components/ui/card';
import { index, store } from '@/routes/admin/courts';
import type { SelectOption } from '@/types';

export default function CreateCourt({
    courtTypes,
}: {
    courtTypes: SelectOption[];
}) {
    return (
        <>
            <Head title="New court" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6">
                <div>
                    <Link
                        href={index()}
                        className="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Back to courts
                    </Link>
                    <h1 className="text-xl font-semibold">New court</h1>
                    <p className="text-sm text-muted-foreground">
                        Add a new court to your facility.
                    </p>
                </div>

                <Card>
                    <CardContent>
                        <CourtForm
                            courtTypes={courtTypes}
                            submitUrl={store.url()}
                            method="post"
                            submitLabel="Create court"
                            onCancel={() => router.visit(index.url())}
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CreateCourt.layout = {
    title: 'New court',
};
