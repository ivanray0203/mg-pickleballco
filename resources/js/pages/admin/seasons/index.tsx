import { Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Trophy } from 'lucide-react';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { create, edit, leaderboard } from '@/routes/admin/seasons';
import type { Paginated, Season } from '@/types';

type SeasonsIndexProps = {
    seasons: Paginated<Season>;
};

function SeasonActions({ season }: { season: Season }) {
    return (
        <div className="flex items-center justify-end gap-1">
            <Button variant="outline" size="sm" asChild>
                <Link href={leaderboard.url({ season: season.id })}>
                    <Trophy className="size-4" />
                    Leaderboard
                </Link>
            </Button>
            <Button variant="outline" size="sm" asChild>
                <Link href={edit.url({ season: season.id })}>
                    <Pencil className="size-4" />
                    Edit
                </Link>
            </Button>
        </div>
    );
}

export default function SeasonsIndex({ seasons }: SeasonsIndexProps) {
    const isEmpty = seasons.data.length === 0;

    return (
        <>
            <Head title="Seasons" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Seasons</h1>
                        <p className="text-sm text-muted-foreground">
                            Group open play across dates into a ranked
                            leaderboard.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={create()}>
                            <Plus className="size-4" />
                            New season
                        </Link>
                    </Button>
                </div>

                {isEmpty ? (
                    <div className="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border bg-muted/20 p-12 text-center">
                        <span className="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <Trophy className="size-6" />
                        </span>
                        <div className="grid gap-1">
                            <p className="font-medium">No seasons yet</p>
                            <p className="max-w-sm text-sm text-muted-foreground">
                                Create a season to start ranking players across
                                sessions.
                            </p>
                        </div>
                        <Button asChild>
                            <Link href={create()}>
                                <Plus className="size-4" />
                                New season
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <>
                        {/* Desktop table */}
                        <div className="hidden overflow-x-auto rounded-xl border border-border bg-card md:block">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-muted/40 hover:bg-muted/40">
                                        <TableHead>Name</TableHead>
                                        <TableHead>Window</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {seasons.data.map((season) => (
                                        <TableRow key={season.id}>
                                            <TableCell className="font-medium">
                                                {season.name}
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap text-muted-foreground">
                                                {season.date_range_label}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant="outline"
                                                    className={
                                                        season.is_active
                                                            ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-600'
                                                            : 'border-border bg-muted text-muted-foreground'
                                                    }
                                                >
                                                    {season.is_active
                                                        ? 'Active'
                                                        : 'Inactive'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <SeasonActions
                                                    season={season}
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        {/* Mobile cards */}
                        <div className="flex flex-col gap-3 md:hidden">
                            {seasons.data.map((season) => (
                                <Card key={season.id}>
                                    <CardContent className="flex flex-col gap-3">
                                        <div className="flex items-start justify-between gap-2">
                                            <div>
                                                <p className="font-medium">
                                                    {season.name}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {season.date_range_label}
                                                </p>
                                            </div>
                                            <Badge
                                                variant="outline"
                                                className={
                                                    season.is_active
                                                        ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-600'
                                                        : 'border-border bg-muted text-muted-foreground'
                                                }
                                            >
                                                {season.is_active
                                                    ? 'Active'
                                                    : 'Inactive'}
                                            </Badge>
                                        </div>
                                        <SeasonActions season={season} />
                                    </CardContent>
                                </Card>
                            ))}
                        </div>

                        <Pagination meta={seasons.meta} />
                    </>
                )}
            </div>
        </>
    );
}

SeasonsIndex.layout = {
    title: 'Seasons',
};
