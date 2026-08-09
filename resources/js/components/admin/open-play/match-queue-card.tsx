import { router } from '@inertiajs/react';
import { ListOrdered, Play, Shuffle, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    clear as clearQueue,
    court as assignCourtRoute,
    destroy as destroyMatchup,
    generate as generateMatchups,
    record as recordMatchup,
} from '@/routes/admin/open-play/matchups';
import type { OpenPlayMatchupRow } from '@/types';

type MatchQueueCardProps = {
    sessionId: number;
    matchups: OpenPlayMatchupRow[];
    courts: string[];
    canGenerate: boolean;
};

function TeamNames({ names }: { names: string[] }) {
    return <span>{names.join(' & ') || '—'}</span>;
}

function PlayingMatch({
    matchup,
    courts,
    takenCourts,
}: {
    matchup: OpenPlayMatchupRow;
    courts: string[];
    takenCourts: string[];
}) {
    const [scoreA, setScoreA] = useState('');
    const [scoreB, setScoreB] = useState('');

    const filled = scoreA !== '' && scoreB !== '';
    const canRecord = filled && Number(scoreA) !== Number(scoreB);

    const record = () => {
        router.patch(
            recordMatchup.url({ matchup: matchup.id }),
            { team_a_score: Number(scoreA), team_b_score: Number(scoreB) },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setScoreA('');
                    setScoreB('');
                },
            },
        );
    };

    const assignCourt = (label: string) => {
        router.patch(
            assignCourtRoute.url({ matchup: matchup.id }),
            { court_label: label },
            { preserveScroll: true },
        );
    };

    return (
        <div className="flex flex-col gap-3 rounded-lg border border-primary/40 bg-primary/5 p-3">
            <div className="flex items-center justify-between gap-2">
                <Badge className="gap-1.5">
                    <Play className="size-3" />
                    Playing now
                </Badge>
                {courts.length > 1 && (
                    <Select
                        value={matchup.court_label ?? undefined}
                        onValueChange={assignCourt}
                    >
                        <SelectTrigger className="h-8 w-32">
                            <SelectValue placeholder="Assign court" />
                        </SelectTrigger>
                        <SelectContent>
                            {courts.map((court) => (
                                <SelectItem
                                    key={court}
                                    value={court}
                                    disabled={
                                        court !== matchup.court_label &&
                                        takenCourts.includes(court)
                                    }
                                >
                                    {court}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                )}
                {courts.length <= 1 && matchup.court_label && (
                    <span className="text-xs font-medium text-muted-foreground">
                        {matchup.court_label}
                    </span>
                )}
            </div>
            {/* Pickleball court — teams on each side of the net, ball rallying. */}
            <div className="relative h-32 w-full overflow-hidden rounded-md bg-gradient-to-b from-sky-500 to-sky-600 ring-1 ring-sky-700/50">
                {/* Court boundary + interior lines. */}
                <div className="absolute inset-2 rounded-sm border-2 border-white/85">
                    {/* Non-volley zones (the "kitchen") either side of the net. */}
                    <div className="absolute inset-y-0 left-1/2 w-7 -translate-x-full border-r-2 border-white/70 bg-white/10" />
                    <div className="absolute inset-y-0 left-1/2 w-7 border-l-2 border-white/70 bg-white/10" />
                    {/* Center service lines on each half. */}
                    <div className="absolute top-1/2 left-0 h-px w-[calc(50%-1.75rem)] bg-white/45" />
                    <div className="absolute top-1/2 right-0 h-px w-[calc(50%-1.75rem)] bg-white/45" />
                    {/* The net. */}
                    <div className="absolute inset-y-0 left-1/2 -translate-x-1/2 border-l-2 border-dashed border-white" />
                </div>

                {/* Team labels on their side of the net. */}
                <div className="absolute inset-y-0 left-0 flex w-1/2 items-center justify-center px-2 text-center">
                    <span className="text-xs font-semibold text-white drop-shadow-[0_1px_2px_rgba(0,0,0,0.45)]">
                        <TeamNames names={matchup.team_a} />
                    </span>
                </div>
                <div className="absolute inset-y-0 right-0 flex w-1/2 items-center justify-center px-2 text-center">
                    <span className="text-xs font-semibold text-white drop-shadow-[0_1px_2px_rgba(0,0,0,0.45)]">
                        <TeamNames names={matchup.team_b} />
                    </span>
                </div>

                {/* VS chip on top of the net. */}
                <span className="absolute top-1 left-1/2 -translate-x-1/2 rounded-full bg-white/90 px-1.5 py-0.5 text-[10px] font-bold text-sky-700">
                    VS
                </span>

                {/* The animated pickleball. */}
                <span
                    className="pickleball-ball absolute z-10 size-3 rounded-full bg-yellow-300 shadow-md ring-1 ring-yellow-500"
                    style={{ top: '28%', left: '14%' }}
                    aria-hidden="true"
                />
            </div>
            <div className="grid grid-cols-[1fr_auto_1fr] items-end gap-2">
                <div className="flex flex-col gap-1">
                    <Label htmlFor={`mq_a_${matchup.id}`} className="text-xs">
                        Team A
                    </Label>
                    <Input
                        id={`mq_a_${matchup.id}`}
                        type="number"
                        min={0}
                        max={99}
                        value={scoreA}
                        onChange={(e) => setScoreA(e.target.value)}
                    />
                </div>
                <span className="pb-2 text-xs text-muted-foreground">–</span>
                <div className="flex flex-col gap-1">
                    <Label htmlFor={`mq_b_${matchup.id}`} className="text-xs">
                        Team B
                    </Label>
                    <Input
                        id={`mq_b_${matchup.id}`}
                        type="number"
                        min={0}
                        max={99}
                        value={scoreB}
                        onChange={(e) => setScoreB(e.target.value)}
                    />
                </div>
            </div>
            {filled && !canRecord && (
                <p className="text-sm text-destructive">
                    A game cannot end in a tie.
                </p>
            )}
            <div className="flex items-center justify-between gap-2">
                <Button
                    variant="ghost"
                    size="sm"
                    className="text-muted-foreground"
                    onClick={() =>
                        router.delete(
                            destroyMatchup.url({ matchup: matchup.id }),
                            { preserveScroll: true },
                        )
                    }
                >
                    <X className="size-4" />
                    Skip
                </Button>
                <Button size="sm" onClick={record} disabled={!canRecord}>
                    Record &amp; next
                </Button>
            </div>
        </div>
    );
}

export function MatchQueueCard({
    sessionId,
    matchups,
    courts,
    canGenerate,
}: MatchQueueCardProps) {
    const playing = matchups.filter((m) => m.status === 'playing');
    const queued = matchups.filter((m) => m.status === 'queued');
    const takenCourts = playing
        .map((m) => m.court_label)
        .filter((label): label is string => label !== null);

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex flex-wrap items-center gap-2 text-base">
                    <ListOrdered className="size-4" />
                    Match queue
                    <Badge variant="secondary">
                        {courts.length}{' '}
                        {courts.length === 1 ? 'court' : 'courts'}
                    </Badge>
                    <div className="ml-auto flex items-center gap-2">
                        {matchups.length > 0 && (
                            <Button
                                variant="ghost"
                                size="sm"
                                className="text-destructive hover:text-destructive"
                                onClick={() =>
                                    router.delete(
                                        clearQueue.url({
                                            openPlaySession: sessionId,
                                        }),
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                <Trash2 className="size-4" />
                                Clear
                            </Button>
                        )}
                        <Button
                            size="sm"
                            disabled={!canGenerate}
                            onClick={() =>
                                router.post(
                                    generateMatchups.url({
                                        openPlaySession: sessionId,
                                    }),
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            <Shuffle className="size-4" />
                            Shuffle
                        </Button>
                    </div>
                </CardTitle>
            </CardHeader>
            <CardContent className="flex flex-col gap-3">
                {matchups.length === 0 ? (
                    <p className="py-4 text-center text-sm text-muted-foreground">
                        {canGenerate
                            ? 'Shuffle the checked-in players into random 2v2 matches.'
                            : 'Check in at least four players, then shuffle to build the queue.'}
                    </p>
                ) : (
                    <>
                        {playing.length > 0 && (
                            <div className="grid gap-3 sm:grid-cols-2">
                                {playing.map((matchup) => (
                                    <PlayingMatch
                                        key={matchup.id}
                                        matchup={matchup}
                                        courts={courts}
                                        takenCourts={takenCourts}
                                    />
                                ))}
                            </div>
                        )}

                        {queued.length > 0 && (
                            <div className="flex flex-col gap-1.5">
                                <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                    Up next
                                </p>
                                <ol className="flex flex-col divide-y divide-border rounded-md border border-border">
                                    {queued.map((matchup, index) => (
                                        <li
                                            key={matchup.id}
                                            className="flex items-center gap-2 px-3 py-2 text-sm"
                                        >
                                            <span className="w-5 text-center text-xs text-muted-foreground">
                                                {index + 1}
                                            </span>
                                            <div className="grid flex-1 grid-cols-[1fr_auto_1fr] items-center gap-2">
                                                <TeamNames
                                                    names={matchup.team_a}
                                                />
                                                <span className="text-xs text-muted-foreground">
                                                    vs
                                                </span>
                                                <span className="text-right">
                                                    <TeamNames
                                                        names={matchup.team_b}
                                                    />
                                                </span>
                                            </div>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-7 text-muted-foreground"
                                                aria-label="Remove from queue"
                                                onClick={() =>
                                                    router.delete(
                                                        destroyMatchup.url({
                                                            matchup: matchup.id,
                                                        }),
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                <X className="size-4" />
                                            </Button>
                                        </li>
                                    ))}
                                </ol>
                            </div>
                        )}
                    </>
                )}
            </CardContent>
        </Card>
    );
}
