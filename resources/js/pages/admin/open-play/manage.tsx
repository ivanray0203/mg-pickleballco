import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    CheckCircle2,
    ClipboardList,
    Trophy,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { MatchQueueCard } from '@/components/admin/open-play/match-queue-card';
import { SessionStatusBadge } from '@/components/admin/open-play/session-status-badge';
import { PodiumCard } from '@/components/open-play/podium-card';
import { StandingsTable } from '@/components/open-play/standings-table';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { cn } from '@/lib/utils';
import { complete, edit, index } from '@/routes/admin/open-play';
import {
    destroy as destroyGame,
    store as storeGame,
} from '@/routes/admin/open-play/games';
import {
    checkIn as checkInRoute,
    undoCheckIn as undoCheckInRoute,
} from '@/routes/admin/open-play/registrations';
import {
    confirm as confirmSubmission,
    reject as rejectSubmission,
} from '@/routes/admin/open-play/submissions';
import type {
    OpenPlayGameRow,
    OpenPlayMatchupRow,
    OpenPlayPendingSubmission,
    OpenPlayRegistrationRow,
    OpenPlaySession,
    OpenPlayStanding,
    Paginated,
} from '@/types';

type ManageProps = {
    session: { data: OpenPlaySession };
    registrations: { data: OpenPlayRegistrationRow[] };
    games: Paginated<OpenPlayGameRow>;
    standings: OpenPlayStanding[];
    submissions: OpenPlayPendingSubmission[];
    matchups: OpenPlayMatchupRow[];
    courts: string[];
};

type Team = 'a' | 'b';

function TeamLine({
    players,
    won,
}: {
    players: { name: string }[];
    won: boolean;
}) {
    return (
        <span
            className={cn(
                'flex flex-wrap items-center gap-1',
                won && 'font-semibold',
            )}
        >
            {players.map((player) => player.name).join(' & ') || '—'}
            {won && <Trophy className="size-3.5 text-amber-500" />}
        </span>
    );
}

export default function ManageOpenPlaySession({
    session: { data: session },
    registrations: { data: registrations },
    games: { data: games, meta: gamesMeta },
    standings,
    submissions,
    matchups,
    courts,
}: ManageProps) {
    const readOnly =
        session.status === 'completed' || session.status === 'cancelled';

    const checkedIn = registrations.filter((r) => r.checked_in);

    const [assignments, setAssignments] = useState<Record<number, Team>>({});
    const [completeOpen, setCompleteOpen] = useState(false);

    const form = useForm<{
        team_a: number[];
        team_b: number[];
        team_a_score: string;
        team_b_score: string;
    }>({
        team_a: [],
        team_b: [],
        team_a_score: '',
        team_b_score: '',
    });

    const teamA = Object.entries(assignments)
        .filter(([, team]) => team === 'a')
        .map(([id]) => Number(id));
    const teamB = Object.entries(assignments)
        .filter(([, team]) => team === 'b')
        .map(([id]) => Number(id));

    const scoreA = Number(form.data.team_a_score);
    const scoreB = Number(form.data.team_b_score);
    const scoresFilled =
        form.data.team_a_score !== '' && form.data.team_b_score !== '';

    const canSubmit =
        !readOnly &&
        teamA.length >= 1 &&
        teamA.length <= 2 &&
        teamA.length === teamB.length &&
        scoresFilled &&
        scoreA !== scoreB;

    function assign(registrationId: number, team: string) {
        setAssignments((current) => {
            const next = { ...current };

            if (team === '') {
                delete next[registrationId];
            } else {
                next[registrationId] = team as Team;
            }

            return next;
        });
    }

    function toggleCheckIn(registration: OpenPlayRegistrationRow) {
        const route = registration.checked_in
            ? undoCheckInRoute.url({ registration: registration.id })
            : checkInRoute.url({ registration: registration.id });
        const method = registration.checked_in ? 'delete' : 'patch';

        router[method](route, {}, { preserveScroll: true });
    }

    function submitGame(event: FormEvent) {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            team_a: teamA,
            team_b: teamB,
            team_a_score: Number(data.team_a_score),
            team_b_score: Number(data.team_b_score),
        }));

        form.post(storeGame.url({ openPlaySession: session.id }), {
            preserveScroll: true,
            onSuccess: () => {
                setAssignments({});
                form.reset();
            },
        });
    }

    return (
        <>
            <Head title={`Manage — ${session.title}`} />

            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-3">
                    <Link
                        href={index()}
                        className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Back to open play
                    </Link>
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-xl font-semibold">
                                    {session.title}
                                </h1>
                                <SessionStatusBadge
                                    status={session.status}
                                    label={session.status_label}
                                />
                            </div>
                            <p className="text-sm text-muted-foreground">
                                {session.court.name} ·{' '}
                                {session.session_date_label} ·{' '}
                                {session.time_range}
                            </p>
                        </div>
                        {!readOnly && (
                            <Button
                                variant="outline"
                                onClick={() => setCompleteOpen(true)}
                            >
                                <CheckCircle2 className="size-4" />
                                Complete session
                            </Button>
                        )}
                    </div>
                </div>

                {readOnly && (
                    <div className="rounded-lg border border-border bg-muted/40 px-4 py-3 text-sm text-muted-foreground">
                        This session is {session.status_label.toLowerCase()} and
                        is read-only. Reopen it from the{' '}
                        <Link
                            href={edit.url({ openPlaySession: session.id })}
                            className="underline"
                        >
                            session settings
                        </Link>{' '}
                        if you need to make changes.
                    </div>
                )}

                <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
                    {/* Roster with check-in */}
                    <Card className="self-start">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Users className="size-4" />
                                Roster
                                <Badge variant="secondary" className="ml-auto">
                                    {checkedIn.length}/{registrations.length}{' '}
                                    checked in
                                </Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {registrations.length === 0 ? (
                                <p className="py-6 text-center text-sm text-muted-foreground">
                                    No confirmed players yet.
                                </p>
                            ) : (
                                <ul className="flex flex-col divide-y divide-border">
                                    {registrations.map((registration) => (
                                        <li
                                            key={registration.id}
                                            className="flex items-center justify-between gap-3 py-2.5"
                                        >
                                            <div>
                                                <p className="text-sm font-medium">
                                                    {registration.user.name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {registration.skill_level_label ??
                                                        'No skill set'}
                                                </p>
                                            </div>
                                            <Button
                                                variant={
                                                    registration.checked_in
                                                        ? 'secondary'
                                                        : 'outline'
                                                }
                                                size="sm"
                                                disabled={readOnly}
                                                onClick={() =>
                                                    toggleCheckIn(registration)
                                                }
                                            >
                                                {registration.checked_in ? (
                                                    <>
                                                        <CheckCircle2 className="size-4 text-emerald-500" />
                                                        Checked in
                                                    </>
                                                ) : (
                                                    'Check in'
                                                )}
                                            </Button>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>

                    <div className="flex flex-col gap-6">
                        {/* Auto-paired match queue */}
                        {!readOnly && (
                            <MatchQueueCard
                                sessionId={session.id}
                                matchups={matchups}
                                courts={courts}
                                canGenerate={checkedIn.length >= 4}
                            />
                        )}

                        {/* Player-reported results awaiting review */}
                        {!readOnly && submissions.length > 0 && (
                            <Card className="border-amber-500/40">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <ClipboardList className="size-4" />
                                        Reported results
                                        <Badge
                                            variant="secondary"
                                            className="ml-auto"
                                        >
                                            {submissions.length} pending
                                        </Badge>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ul className="flex flex-col divide-y divide-border">
                                        {submissions.map((submission) => (
                                            <li
                                                key={submission.id}
                                                className="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between"
                                            >
                                                <div className="text-sm">
                                                    <p>
                                                        {submission.team_a.join(
                                                            ' & ',
                                                        )}{' '}
                                                        <span className="font-mono text-xs">
                                                            {
                                                                submission.team_a_score
                                                            }{' '}
                                                            –{' '}
                                                            {
                                                                submission.team_b_score
                                                            }
                                                        </span>{' '}
                                                        {submission.team_b.join(
                                                            ' & ',
                                                        )}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        Reported by{' '}
                                                        {
                                                            submission.reporter_name
                                                        }
                                                    </p>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <Button
                                                        size="sm"
                                                        onClick={() =>
                                                            router.patch(
                                                                confirmSubmission.url(
                                                                    {
                                                                        submission:
                                                                            submission.id,
                                                                    },
                                                                ),
                                                                {},
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                    >
                                                        Confirm
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-destructive hover:text-destructive"
                                                        onClick={() =>
                                                            router.delete(
                                                                rejectSubmission.url(
                                                                    {
                                                                        submission:
                                                                            submission.id,
                                                                    },
                                                                ),
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                    >
                                                        Reject
                                                    </Button>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                </CardContent>
                            </Card>
                        )}

                        {/* Record a game */}
                        {!readOnly && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <ClipboardList className="size-4" />
                                        Record a game
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {checkedIn.length < 2 ? (
                                        <p className="py-4 text-center text-sm text-muted-foreground">
                                            Check in at least two players to
                                            record a game.
                                        </p>
                                    ) : (
                                        <form
                                            onSubmit={submitGame}
                                            className="flex flex-col gap-4"
                                        >
                                            <div className="flex flex-col gap-1.5">
                                                <div className="grid grid-cols-[1fr_auto] items-center gap-2 text-xs font-medium text-muted-foreground">
                                                    <span>Player</span>
                                                    <span>Team</span>
                                                </div>
                                                <ul className="flex flex-col divide-y divide-border rounded-md border border-border">
                                                    {checkedIn.map((player) => (
                                                        <li
                                                            key={player.id}
                                                            className="grid grid-cols-[1fr_auto] items-center gap-2 px-3 py-2"
                                                        >
                                                            <span className="text-sm">
                                                                {
                                                                    player.user
                                                                        .name
                                                                }
                                                            </span>
                                                            <ToggleGroup
                                                                type="single"
                                                                variant="outline"
                                                                size="sm"
                                                                value={
                                                                    assignments[
                                                                        player
                                                                            .id
                                                                    ] ?? ''
                                                                }
                                                                onValueChange={(
                                                                    value,
                                                                ) =>
                                                                    assign(
                                                                        player.id,
                                                                        value,
                                                                    )
                                                                }
                                                            >
                                                                <ToggleGroupItem
                                                                    value="a"
                                                                    aria-label="Team A"
                                                                >
                                                                    A
                                                                </ToggleGroupItem>
                                                                <ToggleGroupItem
                                                                    value="b"
                                                                    aria-label="Team B"
                                                                >
                                                                    B
                                                                </ToggleGroupItem>
                                                            </ToggleGroup>
                                                        </li>
                                                    ))}
                                                </ul>
                                                {(form.errors.team_a ||
                                                    form.errors.team_b) && (
                                                    <p className="text-sm text-destructive">
                                                        {form.errors.team_a ??
                                                            form.errors.team_b}
                                                    </p>
                                                )}
                                            </div>

                                            <div className="grid grid-cols-2 gap-3">
                                                <div className="flex flex-col gap-1.5">
                                                    <Label htmlFor="team_a_score">
                                                        Team A score
                                                    </Label>
                                                    <Input
                                                        id="team_a_score"
                                                        type="number"
                                                        min={0}
                                                        max={99}
                                                        value={
                                                            form.data
                                                                .team_a_score
                                                        }
                                                        onChange={(e) =>
                                                            form.setData(
                                                                'team_a_score',
                                                                e.target.value,
                                                            )
                                                        }
                                                    />
                                                </div>
                                                <div className="flex flex-col gap-1.5">
                                                    <Label htmlFor="team_b_score">
                                                        Team B score
                                                    </Label>
                                                    <Input
                                                        id="team_b_score"
                                                        type="number"
                                                        min={0}
                                                        max={99}
                                                        value={
                                                            form.data
                                                                .team_b_score
                                                        }
                                                        onChange={(e) =>
                                                            form.setData(
                                                                'team_b_score',
                                                                e.target.value,
                                                            )
                                                        }
                                                    />
                                                </div>
                                            </div>
                                            {(form.errors.team_a_score ||
                                                form.errors.team_b_score) && (
                                                <p className="text-sm text-destructive">
                                                    {form.errors.team_a_score ??
                                                        form.errors
                                                            .team_b_score}
                                                </p>
                                            )}
                                            {scoresFilled &&
                                                scoreA === scoreB && (
                                                    <p className="text-sm text-destructive">
                                                        A game cannot end in a
                                                        tie.
                                                    </p>
                                                )}

                                            <div className="flex justify-end">
                                                <Button
                                                    type="submit"
                                                    disabled={
                                                        !canSubmit ||
                                                        form.processing
                                                    }
                                                >
                                                    Record game
                                                </Button>
                                            </div>
                                        </form>
                                    )}
                                </CardContent>
                            </Card>
                        )}

                        {/* Recorded games */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Trophy className="size-4" />
                                    Games
                                    <Badge
                                        variant="secondary"
                                        className="ml-auto"
                                    >
                                        {gamesMeta.total}
                                    </Badge>
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {games.length === 0 ? (
                                    <p className="py-6 text-center text-sm text-muted-foreground">
                                        No games recorded yet.
                                    </p>
                                ) : (
                                    <ul className="flex flex-col divide-y divide-border">
                                        {games.map((game) => (
                                            <li
                                                key={game.id}
                                                className="flex items-center gap-3 py-2.5 text-sm"
                                            >
                                                <div className="grid flex-1 grid-cols-[1fr_auto_1fr] items-center gap-2">
                                                    <TeamLine
                                                        players={game.team_a}
                                                        won={
                                                            game.winning_team ===
                                                            'a'
                                                        }
                                                    />
                                                    <span className="rounded bg-muted px-2 py-0.5 font-mono text-xs">
                                                        {game.team_a_score} –{' '}
                                                        {game.team_b_score}
                                                    </span>
                                                    <span className="text-right">
                                                        <TeamLine
                                                            players={
                                                                game.team_b
                                                            }
                                                            won={
                                                                game.winning_team ===
                                                                'b'
                                                            }
                                                        />
                                                    </span>
                                                </div>
                                                {!readOnly && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-destructive hover:text-destructive"
                                                        onClick={() =>
                                                            router.delete(
                                                                destroyGame.url(
                                                                    {
                                                                        game: game.id,
                                                                    },
                                                                ),
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                    >
                                                        Delete
                                                    </Button>
                                                )}
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                            {gamesMeta.last_page > 1 && (
                                <CardFooter className="border-t border-border pt-4 [&>nav]:w-full">
                                    <Pagination meta={gamesMeta} />
                                </CardFooter>
                            )}
                        </Card>

                        {/* Standings preview */}
                        {standings.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <Trophy className="size-4" />
                                        Standings
                                        <Badge
                                            variant="secondary"
                                            className="ml-auto"
                                        >
                                            {readOnly ? 'Final' : 'Live'}
                                        </Badge>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="flex flex-col gap-4">
                                    <PodiumCard standings={standings} />
                                    <StandingsTable standings={standings} />
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>

            <Dialog open={completeOpen} onOpenChange={setCompleteOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Complete this session?</DialogTitle>
                        <DialogDescription>
                            Marking “{session.title}” complete locks game
                            recording and finalizes the standings. This can be
                            reopened from the session settings.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setCompleteOpen(false)}
                        >
                            Keep recording
                        </Button>
                        <Button
                            onClick={() =>
                                router.patch(
                                    complete.url({
                                        openPlaySession: session.id,
                                    }),
                                    {},
                                    {
                                        preserveScroll: true,
                                        onSuccess: () => setCompleteOpen(false),
                                    },
                                )
                            }
                        >
                            Complete session
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

ManageOpenPlaySession.layout = {
    title: 'Manage open play',
};
