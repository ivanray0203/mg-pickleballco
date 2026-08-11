import { Link } from '@inertiajs/react';
import { Clock, ImageIcon, Pencil } from 'lucide-react';
import { CourtDeleteDialog } from '@/components/admin/courts/court-delete-dialog';
import { CourtStatusBadge } from '@/components/admin/courts/court-status-badge';
import { CourtStatusToggle } from '@/components/admin/courts/court-status-toggle';
import { CourtTypeBadge } from '@/components/admin/courts/court-type-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { edit } from '@/routes/admin/courts';
import type { Court } from '@/types';

export function CourtCard({ court }: { court: Court }) {
    return (
        <Card className="overflow-hidden py-0">
            <div className="flex aspect-video w-full items-center justify-center bg-muted">
                {court.image_url ? (
                    <img
                        src={court.image_url}
                        alt={court.name}
                        className="size-full object-cover"
                    />
                ) : (
                    <ImageIcon className="size-8 text-muted-foreground" />
                )}
            </div>

            <CardContent className="flex flex-col gap-3 py-4">
                <div className="flex items-start justify-between gap-2">
                    <div className="grid gap-1">
                        <h3 className="font-semibold">{court.name}</h3>
                        <p className="text-sm text-muted-foreground">
                            {court.hourly_rate_formatted} / hr
                        </p>
                    </div>
                    <CourtStatusBadge active={court.is_active} />
                </div>

                <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                    <CourtTypeBadge
                        type={court.type}
                        label={court.type_label}
                    />
                    <span className="flex items-center gap-1">
                        <Clock className="size-3.5" />
                        {court.opening_hours}
                    </span>
                </div>

                <div className="flex flex-col gap-2 border-t border-border pt-3">
                    <div className="flex items-center gap-2">
                        <Button
                            asChild
                            variant="outline"
                            size="sm"
                            className="flex-1"
                        >
                            <Link href={edit({ court: court.id })}>
                                <Pencil className="size-4" />
                                Edit
                            </Link>
                        </Button>
                        <CourtStatusToggle court={court} />
                    </div>
                    <CourtDeleteDialog court={court} variant="block" />
                </div>
            </CardContent>
        </Card>
    );
}
