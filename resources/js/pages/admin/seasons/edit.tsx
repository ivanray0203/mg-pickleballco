import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { SeasonForm } from '@/components/admin/seasons/season-form';
import { Card, CardContent } from '@/components/ui/card';
import { index, update } from '@/routes/admin/seasons';
import type { Season } from '@/types';

export default function EditSeason({ season }: { season: { data: Season } }) {
    const data = season.data;

    return (
        <>
            <Head title={`Edit ${data.name}`} />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-6">
                <div>
                    <Link
                        href={index()}
                        className="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Back to seasons
                    </Link>
                    <h1 className="text-xl font-semibold">Edit season</h1>
                    <p className="text-sm text-muted-foreground">
                        Update the window or toggle its visibility.
                    </p>
                </div>

                <Card>
                    <CardContent>
                        <SeasonForm
                            season={data}
                            submitUrl={update.url({ season: data.id })}
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

EditSeason.layout = {
    title: 'Edit season',
};
