import { Trophy } from 'lucide-react';
import { PodiumCard } from '@/components/open-play/podium-card';
import { StandingsTable } from '@/components/open-play/standings-table';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { LeaderboardStanding, SelectOption } from '@/types';

const ALL_LEVELS = 'all';

type LeaderboardPanelProps = {
    standings: LeaderboardStanding[];
    skillLevels: SelectOption[];
    skillLevel: string | null;
    onSkillChange: (value: string | null) => void;
};

export function LeaderboardPanel({
    standings,
    skillLevels,
    skillLevel,
    onSkillChange,
}: LeaderboardPanelProps) {
    const isEmpty = standings.length === 0;

    return (
        <div className="flex flex-col gap-4">
            <div className="flex flex-col gap-1.5 sm:w-56">
                <Label htmlFor="skill-filter">Skill level</Label>
                <Select
                    value={skillLevel ?? ALL_LEVELS}
                    onValueChange={(value) =>
                        onSkillChange(value === ALL_LEVELS ? null : value)
                    }
                >
                    <SelectTrigger id="skill-filter">
                        <SelectValue placeholder="All levels" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL_LEVELS}>All levels</SelectItem>
                        {skillLevels.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            {isEmpty ? (
                <div className="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border bg-muted/20 p-12 text-center">
                    <span className="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                        <Trophy className="size-6" />
                    </span>
                    <p className="max-w-sm text-sm text-muted-foreground">
                        No games have been played in this season yet. Standings
                        appear once results are recorded.
                    </p>
                </div>
            ) : (
                <>
                    <PodiumCard standings={standings} />
                    <StandingsTable standings={standings} />
                </>
            )}
        </div>
    );
}
