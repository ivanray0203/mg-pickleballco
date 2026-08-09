import { Head, Link, router } from '@inertiajs/react';
import { CalendarRange, ListChecks, Pencil, Plus, Ban } from 'lucide-react';
import { useState } from 'react';
import { SessionStatusBadge } from '@/components/admin/open-play/session-status-badge';
import { Pagination } from '@/components/shared/pagination';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cancel, create, edit, index, manage } from '@/routes/admin/open-play';
import type {
    OpenPlaySession,
    OpenPlaySessionFilters,
    Paginated,
    SelectOption,
} from '@/types';

type OpenPlayIndexProps = {
    sessions: Paginated<OpenPlaySession>;
    filters: OpenPlaySessionFilters;
    statuses: SelectOption[];
    courts: SelectOption[];
};

const ANY = 'any';

function applyFilter(
    next: Partial<OpenPlaySessionFilters>,
    current: OpenPlaySessionFilters,
) {
    const merged = { ...current, ...next };
    const query: Record<string, string> = {};

    if (merged.search) {
        query.search = merged.search;
    }

    if (merged.status) {
        query.status = merged.status;
    }

    if (merged.court) {
        query.court = merged.court;
    }

    router.get(index.url(), query, { preserveState: true, replace: true });
}

function CancelSessionButton({ session }: { session: OpenPlaySession }) {
    const [open, setOpen] = useState(false);

    return (
        <>
            <Button
                variant="ghost"
                size="sm"
                className="text-destructive hover:text-destructive"
                onClick={() => setOpen(true)}
            >
                <Ban className="size-4" />
                Cancel
            </Button>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Cancel this session?</DialogTitle>
                        <DialogDescription>
                            “{session.title}” on {session.session_date_label}{' '}
                            will be cancelled and its court time freed.
                            Registered players should be notified.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setOpen(false)}
                        >
                            Keep session
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() =>
                                router.patch(
                                    cancel.url({ openPlaySession: session.id }),
                                    {},
                                    {
                                        preserveScroll: true,
                                        onSuccess: () => setOpen(false),
                                    },
                                )
                            }
                        >
                            Cancel session
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

function SessionActions({ session }: { session: OpenPlaySession }) {
    const canCancel =
        session.status !== 'cancelled' && session.status !== 'completed';
    const canManage = session.status !== 'cancelled';

    return (
        <div className="flex items-center justify-end gap-1">
            {canManage && (
                <Button variant="outline" size="sm" asChild>
                    <Link href={manage.url({ openPlaySession: session.id })}>
                        <ListChecks className="size-4" />
                        Manage
                    </Link>
                </Button>
            )}
            <Button variant="outline" size="sm" asChild>
                <Link href={edit.url({ openPlaySession: session.id })}>
                    <Pencil className="size-4" />
                    Edit
                </Link>
            </Button>
            {canCancel && <CancelSessionButton session={session} />}
        </div>
    );
}

export default function OpenPlayIndex({
    sessions,
    filters,
    statuses,
    courts,
}: OpenPlayIndexProps) {
    const hasFilters = Boolean(
        filters.search || filters.status || filters.court,
    );
    const isEmpty = sessions.data.length === 0;

    return (
        <>
            <Head title="Open play" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Open play</h1>
                        <p className="text-sm text-muted-foreground">
                            Schedule and manage drop-in open play sessions.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={create()}>
                            <Plus className="size-4" />
                            New session
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-col gap-3 sm:flex-row">
                    <Input
                        defaultValue={filters.search ?? ''}
                        placeholder="Search by title…"
                        className="sm:max-w-xs"
                        onKeyDown={(e) => {
                            if (e.key === 'Enter') {
                                applyFilter(
                                    { search: e.currentTarget.value || null },
                                    filters,
                                );
                            }
                        }}
                    />
                    <Select
                        value={filters.status ?? ANY}
                        onValueChange={(value) =>
                            applyFilter(
                                { status: value === ANY ? null : value },
                                filters,
                            )
                        }
                    >
                        <SelectTrigger className="sm:w-44">
                            <SelectValue placeholder="All statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ANY}>All statuses</SelectItem>
                            {statuses.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={filters.court ?? ANY}
                        onValueChange={(value) =>
                            applyFilter(
                                { court: value === ANY ? null : value },
                                filters,
                            )
                        }
                    >
                        <SelectTrigger className="sm:w-44">
                            <SelectValue placeholder="All courts" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ANY}>All courts</SelectItem>
                            {courts.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {isEmpty ? (
                    <div className="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border bg-muted/20 p-12 text-center">
                        <span className="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <CalendarRange className="size-6" />
                        </span>
                        <div className="grid gap-1">
                            <p className="font-medium">
                                {hasFilters
                                    ? 'No sessions match your filters'
                                    : 'No open play sessions yet'}
                            </p>
                            <p className="max-w-sm text-sm text-muted-foreground">
                                {hasFilters
                                    ? 'Try adjusting your search or filters.'
                                    : 'Schedule your first drop-in session to get started.'}
                            </p>
                        </div>
                        {!hasFilters && (
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus className="size-4" />
                                    New session
                                </Link>
                            </Button>
                        )}
                    </div>
                ) : (
                    <>
                        {/* Desktop table */}
                        <div className="hidden overflow-x-auto rounded-xl border border-border bg-card md:block">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-muted/40 hover:bg-muted/40">
                                        <TableHead>Date</TableHead>
                                        <TableHead>Time</TableHead>
                                        <TableHead>Court</TableHead>
                                        <TableHead>Title</TableHead>
                                        <TableHead>Level</TableHead>
                                        <TableHead>Players</TableHead>
                                        <TableHead>Price</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {sessions.data.map((session) => (
                                        <TableRow key={session.id}>
                                            <TableCell className="whitespace-nowrap">
                                                {session.session_date_label}
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap text-muted-foreground">
                                                {session.time_range}
                                            </TableCell>
                                            <TableCell>
                                                {session.court.name}
                                            </TableCell>
                                            <TableCell className="font-medium">
                                                {session.title}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {session.skill_level_label ??
                                                    'All levels'}
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap">
                                                {session.occupied_slots}/
                                                {session.capacity}
                                            </TableCell>
                                            <TableCell>
                                                {session.price_formatted}
                                            </TableCell>
                                            <TableCell>
                                                <SessionStatusBadge
                                                    status={session.status}
                                                    label={session.status_label}
                                                />
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <SessionActions
                                                    session={session}
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        {/* Mobile cards */}
                        <div className="flex flex-col gap-3 md:hidden">
                            {sessions.data.map((session) => (
                                <Card key={session.id}>
                                    <CardContent className="flex flex-col gap-3">
                                        <div className="flex items-start justify-between gap-2">
                                            <div>
                                                <p className="font-medium">
                                                    {session.title}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {session.court.name}
                                                </p>
                                            </div>
                                            <SessionStatusBadge
                                                status={session.status}
                                                label={session.status_label}
                                            />
                                        </div>
                                        <div className="grid grid-cols-2 gap-2 text-sm">
                                            <span className="text-muted-foreground">
                                                {session.session_date_label}
                                            </span>
                                            <span className="text-right">
                                                {session.time_range}
                                            </span>
                                            <span className="text-muted-foreground">
                                                {session.skill_level_label ??
                                                    'All levels'}
                                            </span>
                                            <span className="text-right">
                                                {session.occupied_slots}/
                                                {session.capacity} players
                                            </span>
                                            <span className="font-medium">
                                                {session.price_formatted}
                                            </span>
                                        </div>
                                        <SessionActions session={session} />
                                    </CardContent>
                                </Card>
                            ))}
                        </div>

                        <Pagination meta={sessions.meta} />
                    </>
                )}
            </div>
        </>
    );
}

OpenPlayIndex.layout = {
    title: 'Open play',
};
