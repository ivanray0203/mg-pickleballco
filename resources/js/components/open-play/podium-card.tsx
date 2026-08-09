import { Award, Medal, Trophy } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { OpenPlayStanding } from '@/types';

const PLACES = [
    {
        rank: 1,
        label: '1st',
        icon: Trophy,
        accent: 'border-amber-500/40 bg-amber-500/10',
        iconColor: 'text-amber-500',
        order: 'sm:order-2',
    },
    {
        rank: 2,
        label: '2nd',
        icon: Medal,
        accent: 'border-slate-400/40 bg-slate-400/10',
        iconColor: 'text-slate-400',
        order: 'sm:order-1',
    },
    {
        rank: 3,
        label: '3rd',
        icon: Award,
        accent: 'border-orange-700/40 bg-orange-700/10',
        iconColor: 'text-orange-700',
        order: 'sm:order-3',
    },
] as const;

export function PodiumCard({ standings }: { standings: OpenPlayStanding[] }) {
    const byRank = new Map(standings.map((row) => [row.rank, row]));

    return (
        <div className="grid gap-3 sm:grid-cols-3 sm:items-end">
            {PLACES.map((place) => {
                const row = byRank.get(place.rank);
                const Icon = place.icon;

                return (
                    <div
                        key={place.rank}
                        className={cn(
                            'flex flex-col items-center gap-1 rounded-xl border p-4 text-center',
                            place.accent,
                            place.order,
                            place.rank === 1 && 'sm:pb-6',
                        )}
                    >
                        <Icon className={cn('size-6', place.iconColor)} />
                        <span className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                            {place.label}
                        </span>
                        {row ? (
                            <>
                                <span className="font-medium">{row.name}</span>
                                <span className="text-xs text-muted-foreground">
                                    {row.wins}W · {row.losses}L ·{' '}
                                    {row.point_diff > 0 ? '+' : ''}
                                    {row.point_diff}
                                </span>
                            </>
                        ) : (
                            <span className="text-sm text-muted-foreground">
                                —
                            </span>
                        )}
                    </div>
                );
            })}
        </div>
    );
}
