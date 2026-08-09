import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Clock, ImageIcon, Info } from 'lucide-react';
import { CourtTypeBadge } from '@/components/admin/courts/court-type-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { book } from '@/routes';
import { index } from '@/routes/courts';
import type { Court } from '@/types';

type CourtDetailProps = {
    court: { data: Court };
};

export default function CourtDetail({ court }: CourtDetailProps) {
    const data = court.data;

    return (
        <>
            <Head title={data.name} />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6">
                <Link
                    href={index()}
                    className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="size-4" />
                    Back to courts
                </Link>

                <div className="grid gap-6 lg:grid-cols-[1.3fr_1fr]">
                    <div className="relative aspect-video w-full overflow-hidden rounded-xl bg-muted">
                        {data.image_url ? (
                            <img
                                src={data.image_url}
                                alt={data.name}
                                className="size-full object-cover"
                            />
                        ) : (
                            <span className="flex size-full items-center justify-center text-muted-foreground">
                                <ImageIcon className="size-10" />
                            </span>
                        )}
                    </div>

                    <div className="flex flex-col gap-5">
                        <div className="flex flex-col gap-2">
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-semibold">
                                    {data.name}
                                </h1>
                                <CourtTypeBadge
                                    type={data.type}
                                    label={data.type_label}
                                />
                            </div>
                            {data.description && (
                                <p className="text-sm text-muted-foreground">
                                    {data.description}
                                </p>
                            )}
                        </div>

                        <div className="flex items-baseline gap-1">
                            <span className="text-3xl font-bold text-primary">
                                {data.hourly_rate_formatted}
                            </span>
                            <span className="text-muted-foreground">
                                / hour
                            </span>
                        </div>

                        <Card>
                            <CardContent className="flex flex-col gap-3">
                                <div className="flex items-center gap-2 text-sm">
                                    <Clock className="size-4 text-muted-foreground" />
                                    <span className="font-medium">
                                        Operating hours
                                    </span>
                                    <span className="ml-auto text-muted-foreground">
                                        {data.opening_hours}
                                    </span>
                                </div>
                                <div className="flex items-start gap-2 text-sm text-muted-foreground">
                                    <Info className="mt-0.5 size-4 shrink-0" />
                                    <span>
                                        Bookings are made in whole-hour slots
                                        within the court's operating hours.
                                    </span>
                                </div>
                            </CardContent>
                        </Card>

                        <Button size="lg" className="w-full" asChild>
                            <Link
                                href={book.url({ query: { court: data.slug } })}
                            >
                                Book This Court
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>
        </>
    );
}
