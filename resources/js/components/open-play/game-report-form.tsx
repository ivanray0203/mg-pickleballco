import { useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import type { OpenPlayParticipant } from '@/types';

type Team = 'a' | 'b';

type GameReportFormProps = {
    participants: OpenPlayParticipant[];
    myRegistrationId: number;
    submitUrl: string;
};

export function GameReportForm({
    participants,
    myRegistrationId,
    submitUrl,
}: GameReportFormProps) {
    const [assignments, setAssignments] = useState<Record<number, Team>>({});

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
    const includesMe = [...teamA, ...teamB].includes(myRegistrationId);

    const canSubmit =
        teamA.length >= 1 &&
        teamA.length <= 2 &&
        teamA.length === teamB.length &&
        includesMe &&
        scoresFilled &&
        scoreA !== scoreB;

    const assign = (registrationId: number, team: string) => {
        setAssignments((current) => {
            const next = { ...current };

            if (team === '') {
                delete next[registrationId];
            } else {
                next[registrationId] = team as Team;
            }

            return next;
        });
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            team_a: teamA,
            team_b: teamB,
            team_a_score: Number(data.team_a_score),
            team_b_score: Number(data.team_b_score),
        }));

        form.post(submitUrl, {
            preserveScroll: true,
            onSuccess: () => {
                setAssignments({});
                form.reset();
            },
        });
    };

    return (
        <form onSubmit={submit} className="flex flex-col gap-4">
            <p className="text-sm text-muted-foreground">
                Assign each player to a team, enter the score, and submit for
                the organizer to confirm. You must be on one of the teams.
            </p>

            <ul className="flex flex-col divide-y divide-border rounded-md border border-border">
                {participants.map((player) => (
                    <li
                        key={player.registration_id}
                        className="grid grid-cols-[1fr_auto] items-center gap-2 px-3 py-2"
                    >
                        <span className="text-sm">
                            {player.name}
                            {player.registration_id === myRegistrationId && (
                                <span className="ml-1 text-xs text-muted-foreground">
                                    (you)
                                </span>
                            )}
                        </span>
                        <ToggleGroup
                            type="single"
                            variant="outline"
                            size="sm"
                            value={assignments[player.registration_id] ?? ''}
                            onValueChange={(value) =>
                                assign(player.registration_id, value)
                            }
                        >
                            <ToggleGroupItem value="a" aria-label="Team A">
                                A
                            </ToggleGroupItem>
                            <ToggleGroupItem value="b" aria-label="Team B">
                                B
                            </ToggleGroupItem>
                        </ToggleGroup>
                    </li>
                ))}
            </ul>
            {(form.errors.team_a || form.errors.team_b) && (
                <InputError
                    message={form.errors.team_a ?? form.errors.team_b}
                />
            )}

            <div className="grid grid-cols-2 gap-3">
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="report_team_a_score">Team A score</Label>
                    <Input
                        id="report_team_a_score"
                        type="number"
                        min={0}
                        max={99}
                        value={form.data.team_a_score}
                        onChange={(e) =>
                            form.setData('team_a_score', e.target.value)
                        }
                    />
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="report_team_b_score">Team B score</Label>
                    <Input
                        id="report_team_b_score"
                        type="number"
                        min={0}
                        max={99}
                        value={form.data.team_b_score}
                        onChange={(e) =>
                            form.setData('team_b_score', e.target.value)
                        }
                    />
                </div>
            </div>
            {scoresFilled && scoreA === scoreB && (
                <InputError message="A game cannot end in a tie." />
            )}

            <div className="flex justify-end">
                <Button type="submit" disabled={!canSubmit || form.processing}>
                    {form.processing && (
                        <LoaderCircle className="size-4 animate-spin" />
                    )}
                    Submit result
                </Button>
            </div>
        </form>
    );
}
