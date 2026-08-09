import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';
import type { OpenPlayStanding } from '@/types';

export function StandingsTable({
    standings,
}: {
    standings: OpenPlayStanding[];
}) {
    return (
        <div className="overflow-x-auto rounded-xl border border-border bg-card">
            <Table>
                <TableHeader>
                    <TableRow className="bg-muted/40 hover:bg-muted/40">
                        <TableHead className="w-10">#</TableHead>
                        <TableHead>Player</TableHead>
                        <TableHead className="text-center">W–L</TableHead>
                        <TableHead className="text-center">PF</TableHead>
                        <TableHead className="text-center">PA</TableHead>
                        <TableHead className="text-center">Diff</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {standings.map((row) => (
                        <TableRow key={row.registration_id}>
                            <TableCell className="font-medium text-muted-foreground">
                                {row.rank}
                            </TableCell>
                            <TableCell className="font-medium">
                                {row.name}
                            </TableCell>
                            <TableCell className="text-center whitespace-nowrap">
                                {row.wins}–{row.losses}
                            </TableCell>
                            <TableCell className="text-center text-muted-foreground">
                                {row.points_for}
                            </TableCell>
                            <TableCell className="text-center text-muted-foreground">
                                {row.points_against}
                            </TableCell>
                            <TableCell
                                className={cn(
                                    'text-center font-medium',
                                    row.point_diff > 0 && 'text-emerald-600',
                                    row.point_diff < 0 && 'text-destructive',
                                )}
                            >
                                {row.point_diff > 0 ? '+' : ''}
                                {row.point_diff}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}
