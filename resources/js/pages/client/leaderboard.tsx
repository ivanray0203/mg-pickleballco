import { Head, router } from '@inertiajs/react';
import { Trophy } from 'lucide-react';
import { LeaderboardPanel } from '@/components/open-play/leaderboard-panel';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index as leaderboardIndex } from '@/routes/leaderboard';
import type { LeaderboardStanding, Season, SelectOption } from '@/types';

type LeaderboardProps = {
    seasons: { data: Season[] };
    standings: LeaderboardStanding[];
    skillLevels: SelectOption[];
    filters: { season: number | null; skill_level: string | null };
};

export default function ClientLeaderboard({
    seasons,
    standings,
    skillLevels,
    filters,
}: LeaderboardProps) {
    const hasSeasons = seasons.data.length > 0;

    const navigate = (next: {
        season?: number | null;
        skill_level?: string | null;
    }) => {
        const merged = { ...filters, ...next };
        const query: Record<string, string> = {};

        if (merged.season) {
            query.season = String(merged.season);
        }

        if (merged.skill_level) {
            query.skill_level = merged.skill_level;
        }

        router.get(leaderboardIndex.url(), query, {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <>
            <Head title="Leaderboard" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6">
                <div>
                    <h1 className="text-2xl font-semibold">Leaderboard</h1>
                    <p className="text-sm text-muted-foreground">
                        Season standings across all open play sessions.
                    </p>
                </div>

                {!hasSeasons ? (
                    <div className="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border bg-background p-12 text-center">
                        <span className="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <Trophy className="size-6" />
                        </span>
                        <p className="max-w-sm text-sm text-muted-foreground">
                            No active seasons right now. Check back once a
                            season is running.
                        </p>
                    </div>
                ) : (
                    <Card>
                        <CardContent className="flex flex-col gap-4">
                            <div className="flex flex-col gap-1.5 sm:w-64">
                                <Label htmlFor="season-select">Season</Label>
                                <Select
                                    value={
                                        filters.season
                                            ? String(filters.season)
                                            : undefined
                                    }
                                    onValueChange={(value) =>
                                        navigate({
                                            season: Number(value),
                                            skill_level: null,
                                        })
                                    }
                                >
                                    <SelectTrigger id="season-select">
                                        <SelectValue placeholder="Select a season" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {seasons.data.map((season) => (
                                            <SelectItem
                                                key={season.id}
                                                value={String(season.id)}
                                            >
                                                {season.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <LeaderboardPanel
                                standings={standings}
                                skillLevels={skillLevels}
                                skillLevel={filters.skill_level}
                                onSkillChange={(value) =>
                                    navigate({ skill_level: value })
                                }
                            />
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}
