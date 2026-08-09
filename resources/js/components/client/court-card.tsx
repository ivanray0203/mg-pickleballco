import { Link } from '@inertiajs/react';
import { ImageIcon } from 'lucide-react';
import { CourtTypeBadge } from '@/components/admin/courts/court-type-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { book } from '@/routes';
import { show } from '@/routes/courts';
import type { Court } from '@/types';

export function CourtCard({ court }: { court: Court }) {
    return (
        <Card className="overflow-hidden pt-0">
            <Link
                href={show(court.slug)}
                className="relative block aspect-video w-full overflow-hidden bg-muted"
            >
                {court.image_url ? (
                    <img
                        src={court.image_url}
                        alt={court.name}
                        loading="lazy"
                        className="size-full object-cover transition-transform duration-200 hover:scale-105"
                    />
                ) : (
                    <span className="flex size-full items-center justify-center text-muted-foreground">
                        <ImageIcon className="size-8" />
                    </span>
                )}
                <Badge
                    variant="outline"
                    className="absolute top-3 right-3 gap-1.5 border-transparent bg-background/90 text-primary backdrop-blur"
                >
                    <span className="size-1.5 rounded-full bg-primary" />
                    Available
                </Badge>
            </Link>

            <CardContent className="flex flex-col gap-3">
                <div className="flex items-start justify-between gap-2">
                    <h3 className="font-semibold">{court.name}</h3>
                    <CourtTypeBadge
                        type={court.type}
                        label={court.type_label}
                    />
                </div>

                {court.description && (
                    <p className="line-clamp-2 text-sm text-muted-foreground">
                        {court.description}
                    </p>
                )}

                <p className="text-sm">
                    <span className="text-lg font-semibold text-primary">
                        {court.hourly_rate_formatted}
                    </span>
                    <span className="text-muted-foreground"> / hour</span>
                </p>

                <div className="mt-1 flex gap-2">
                    <Button variant="outline" className="flex-1" asChild>
                        <Link href={show(court.slug)}>View Court</Link>
                    </Button>
                    <Button className="flex-1" asChild>
                        <Link href={book.url({ query: { court: court.slug } })}>
                            Book Now
                        </Link>
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}
