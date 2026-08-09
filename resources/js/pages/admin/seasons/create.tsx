import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { SeasonForm } from '@/components/admin/seasons/season-form';
import { Card, CardContent } from '@/components/ui/card';
import { index, store } from '@/routes/admin/seasons';

export default function CreateSeason() {
    return (
        <>
            <Head title="New season" />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-6">
                <div>
                    <Link
                        href={index()}
                        className="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Back to seasons
                    </Link>
                    <h1 className="text-xl font-semibold">New season</h1>
                    <p className="text-sm text-muted-foreground">
                        Define the date window this leaderboard aggregates.
                    </p>
                </div>

                <Card>
                    <CardContent>
                        <SeasonForm
                            submitUrl={store.url()}
                            method="post"
                            submitLabel="Create season"
                            onCancel={() => router.visit(index.url())}
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CreateSeason.layout = {
    title: 'New season',
};
