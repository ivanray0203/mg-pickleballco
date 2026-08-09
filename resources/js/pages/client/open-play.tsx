import { Head, Link, router } from '@inertiajs/react';
import { CalendarClock, MapPin, Users, Volleyball } from 'lucide-react';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index as openPlayIndex, show } from '@/routes/open-play';
import type {
    ClientOpenPlayFilters,
    ClientOpenPlaySession,
    PaginationMeta,
    SelectOption,
} from '@/types';

type OpenPlayPageProps = {
    sessions: { data: ClientOpenPlaySession[]; meta: PaginationMeta };
    filters: ClientOpenPlayFilters;
    skillLevels: SelectOption[];
};

const ANY = 'any';

function applyFilter(
    next: Partial<ClientOpenPlayFilters>,
    current: ClientOpenPlayFilters,
) {
    const merged = { ...current, ...next };
    const query: Record<string, string> = {};

    if (merged.skill_level) {
        query.skill_level = merged.skill_level;
    }

    if (merged.date) {
        query.date = merged.date;
    }

    router.get(openPlayIndex.url(), query, {
        preserveState: true,
        replace: true,
    });
}

function RegistrationBadge({ session }: { session: ClientOpenPlaySession }) {
    const reg = session.my_registration;

    if (reg?.awaiting_payment) {
        return (
            <Badge
                variant="outline"
                className="border-amber-500/30 bg-amber-500/10 text-amber-600"
            >
                Payment pending
            </Badge>
        );
    }

    if (reg?.is_active) {
        return (
            <Badge
                variant="outline"
                className="border-emerald-500/30 bg-emerald-500/10 text-emerald-600"
            >
                Registered
            </Badge>
        );
    }

    if (session.is_full) {
        return (
            <Badge
                variant="outline"
                className="border-border bg-muted text-muted-foreground"
            >
                Full
            </Badge>
        );
    }

    return null;
}

export default function OpenPlayPage({
    sessions,
    filters,
    skillLevels,
}: OpenPlayPageProps) {
    const isEmpty = sessions.data.length === 0;

    return (
        <>
            <Head title="Open play" />

            <div className="flex flex-col gap-6">
                <div>
                    <h1 className="text-2xl font-semibold">Open play</h1>
                    <p className="text-sm text-muted-foreground">
                        Join a drop-in session and play with others.
                    </p>
                </div>

                <div className="flex flex-col gap-3 sm:flex-row">
                    <Select
                        value={filters.skill_level ?? ANY}
                        onValueChange={(value) =>
                            applyFilter(
                                { skill_level: value === ANY ? null : value },
                                filters,
                            )
                        }
                    >
                        <SelectTrigger className="sm:w-48">
                            <SelectValue placeholder="All levels" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ANY}>All levels</SelectItem>
                            {skillLevels.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Input
                        type="date"
                        value={filters.date ?? ''}
                        className="sm:w-44"
                        onChange={(e) =>
                            applyFilter(
                                { date: e.target.value || null },
                                filters,
                            )
                        }
                    />
                </div>

                {isEmpty ? (
                    <div className="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border bg-background p-12 text-center">
                        <span className="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <Volleyball className="size-6" />
                        </span>
                        <div className="grid gap-1">
                            <p className="font-medium">
                                No open play sessions available
                            </p>
                            <p className="max-w-sm text-sm text-muted-foreground">
                                Check back soon — new sessions are added
                                regularly.
                            </p>
                        </div>
                    </div>
                ) : (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            {sessions.data.map((session) => (
                                <Card
                                    key={session.id}
                                    className="flex flex-col"
                                >
                                    <CardContent className="flex flex-1 flex-col gap-3">
                                        <div className="flex items-start justify-between gap-2">
                                            <p className="font-semibold">
                                                {session.title}
                                            </p>
                                            <RegistrationBadge
                                                session={session}
                                            />
                                        </div>

                                        <dl className="grid gap-1.5 text-sm text-muted-foreground">
                                            <div className="flex items-center gap-2">
                                                <CalendarClock className="size-4 shrink-0" />
                                                <span>
                                                    {session.session_date_label}
                                                    , {session.start_time}–
                                                    {session.end_time}
                                                </span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <MapPin className="size-4 shrink-0" />
                                                <span>{session.court}</span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <Users className="size-4 shrink-0" />
                                                <span>
                                                    {session.is_full
                                                        ? 'Full'
                                                        : `${session.remaining_slots} of ${session.capacity} spots left`}
                                                </span>
                                            </div>
                                        </dl>

                                        <div className="mt-auto flex items-center justify-between gap-2 pt-1">
                                            <div className="flex flex-col">
                                                <span className="font-semibold">
                                                    {session.price_formatted}
                                                </span>
                                                {session.skill_level_label && (
                                                    <span className="text-xs text-muted-foreground">
                                                        {
                                                            session.skill_level_label
                                                        }
                                                    </span>
                                                )}
                                            </div>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={show.url({
                                                        openPlaySession:
                                                            session.id,
                                                    })}
                                                >
                                                    View details
                                                </Link>
                                            </Button>
                                        </div>
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
