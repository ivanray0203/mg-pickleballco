import { Link } from '@inertiajs/react';
import { CalendarDays, Clock, ImageIcon, Pencil } from 'lucide-react';
import { CourtDeleteDialog } from '@/components/admin/courts/court-delete-dialog';
import { CourtStatusBadge } from '@/components/admin/courts/court-status-badge';
import { CourtStatusToggle } from '@/components/admin/courts/court-status-toggle';
import { CourtTypeBadge } from '@/components/admin/courts/court-type-badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { edit } from '@/routes/admin/courts';
import { index as availability } from '@/routes/admin/courts/availability';
import { edit as schedule } from '@/routes/admin/courts/schedule';
import type { Court } from '@/types';

export function CourtTable({ courts }: { courts: Court[] }) {
    return (
        <div className="hidden overflow-hidden rounded-xl border border-border bg-card md:block">
            <Table>
                <TableHeader>
                    <TableRow className="bg-muted/40 hover:bg-muted/40">
                        <TableHead>Court</TableHead>
                        <TableHead>Type</TableHead>
                        <TableHead>Hourly rate</TableHead>
                        <TableHead>Opening hours</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead className="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {courts.map((court) => (
                        <TableRow key={court.id}>
                            <TableCell>
                                <div className="flex items-center gap-3">
                                    <div className="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-muted">
                                        {court.image_url ? (
                                            <img
                                                src={court.image_url}
                                                alt={court.name}
                                                className="size-full object-cover"
                                            />
                                        ) : (
                                            <ImageIcon className="size-4 text-muted-foreground" />
                                        )}
                                    </div>
                                    <span className="font-medium">
                                        {court.name}
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <CourtTypeBadge
                                    type={court.type}
                                    label={court.type_label}
                                />
                            </TableCell>
                            <TableCell className="font-medium">
                                {court.hourly_rate_formatted}
                                <span className="text-muted-foreground">
                                    {' '}
                                    /hr
                                </span>
                            </TableCell>
                            <TableCell className="text-muted-foreground">
                                {court.opening_hours}
                            </TableCell>
                            <TableCell>
                                <CourtStatusBadge active={court.is_active} />
                            </TableCell>
                            <TableCell>
                                <div className="flex items-center justify-end gap-2">
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <Button
                                                asChild
                                                variant="ghost"
                                                size="icon"
                                            >
                                                <Link
                                                    href={schedule({
                                                        court: court.id,
                                                    })}
                                                    aria-label="Schedule"
                                                >
                                                    <CalendarDays className="size-4" />
                                                </Link>
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            Schedule
                                        </TooltipContent>
                                    </Tooltip>
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <Button
                                                asChild
                                                variant="ghost"
                                                size="icon"
                                            >
                                                <Link
                                                    href={availability({
                                                        court: court.id,
                                                    })}
                                                    aria-label="Availability"
                                                >
                                                    <Clock className="size-4" />
                                                </Link>
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            Availability
                                        </TooltipContent>
                                    </Tooltip>
                                    <Button asChild variant="outline" size="sm">
                                        <Link href={edit({ court: court.id })}>
                                            <Pencil className="size-4" />
                                            Edit
                                        </Link>
                                    </Button>
                                    <CourtStatusToggle court={court} />
                                    <CourtDeleteDialog court={court} />
                                </div>
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}
