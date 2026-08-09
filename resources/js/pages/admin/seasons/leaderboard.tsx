import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { LeaderboardPanel } from '@/components/open-play/leaderboard-panel';
import { Card, CardContent } from '@/components/ui/card';
import { index, leaderboard } from '@/routes/admin/seasons';
import type { LeaderboardStanding, Season, SelectOption } from '@/types';

type LeaderboardProps = {
    season: { data: Season };
    standings: LeaderboardStanding[];
    skillLevels: SelectOption[];
    filters: { skill_level: string | null };
};

export default function SeasonLeaderboard({
    season,
    standings,
    skillLevels,
    filters,
}: LeaderboardProps) {
    const data = season.data;

    const applySkill = (value: string | null) => {
        router.get(
            leaderboard.url({ season: data.id }),
            value ? { skill_level: value } : {},
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title={`${data.name} — leaderboard`} />

            <div className="flex flex-col gap-6">
                <div>
                    <Link
                        href={index()}
                        className="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Back to seasons
                    </Link>
                    <h1 className="text-xl font-semibold">{data.name}</h1>
                    <p className="text-sm text-muted-foreground">
                        {data.date_range_label}
                    </p>
                </div>

                <Card>
                    <CardContent>
                        <LeaderboardPanel
                            standings={standings}
                            skillLevels={skillLevels}
                            skillLevel={filters.skill_level}
                            onSkillChange={applySkill}
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

SeasonLeaderboard.layout = {
    title: 'Season leaderboard',
};
