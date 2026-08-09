import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { OpenPlaySessionForm } from '@/components/admin/open-play/open-play-session-form';
import { Card, CardContent } from '@/components/ui/card';
import { index, store } from '@/routes/admin/open-play';
import type { SelectOption } from '@/types';

export default function CreateOpenPlaySession({
    courts,
    skillLevels,
}: {
    courts: SelectOption[];
    skillLevels: SelectOption[];
}) {
    return (
        <>
            <Head title="New open play session" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6">
                <div>
                    <Link
                        href={index()}
                        className="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Back to open play
                    </Link>
                    <h1 className="text-xl font-semibold">
                        New open play session
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Schedule a drop-in session on a court.
                    </p>
                </div>

                <Card>
                    <CardContent>
                        <OpenPlaySessionForm
                            courts={courts}
                            skillLevels={skillLevels}
                            submitUrl={store.url()}
                            method="post"
                            submitLabel="Create session"
                            onCancel={() => router.visit(index.url())}
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CreateOpenPlaySession.layout = {
    title: 'New open play session',
};
