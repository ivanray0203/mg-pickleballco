import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { OpenPlaySessionForm } from '@/components/admin/open-play/open-play-session-form';
import { Card, CardContent } from '@/components/ui/card';
import { index, update } from '@/routes/admin/open-play';
import type { OpenPlaySession, SelectOption } from '@/types';

export default function EditOpenPlaySession({
    session,
    courts,
    skillLevels,
}: {
    session: { data: OpenPlaySession };
    courts: SelectOption[];
    skillLevels: SelectOption[];
}) {
    const data = session.data;

    return (
        <>
            <Head title="Edit open play session" />

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
                        Edit open play session
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Update the details of this session.
                    </p>
                </div>

                <Card>
                    <CardContent>
                        <OpenPlaySessionForm
                            session={data}
                            courts={courts}
                            skillLevels={skillLevels}
                            submitUrl={update.url({ openPlaySession: data.id })}
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

EditOpenPlaySession.layout = {
    title: 'Edit open play session',
};
