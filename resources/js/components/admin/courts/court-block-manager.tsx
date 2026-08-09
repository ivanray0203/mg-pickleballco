import { router, useForm } from '@inertiajs/react';
import { LoaderCircle, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { HourSelect } from '@/components/admin/courts/hour-select';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { formatHour } from '@/lib/time';
import { destroy, store } from '@/routes/admin/courts/blocks';
import type { CourtBlock, SelectOption } from '@/types';

type CourtBlockManagerProps = {
    courtId: number;
    date: string;
    blocks: CourtBlock[];
    reasons: SelectOption[];
};

export function CourtBlockManager({
    courtId,
    date,
    blocks,
    reasons,
}: CourtBlockManagerProps) {
    const [deleting, setDeleting] = useState<CourtBlock | null>(null);
    const [removing, setRemoving] = useState(false);

    const { data, setData, transform, post, processing, errors, reset } =
        useForm({
            start_time: '08:00',
            end_time: '09:00',
            reason: reasons[0]?.value ?? '',
            notes: '',
        });

    // The block always applies to the currently selected date.
    transform((formData) => ({ ...formData, block_date: date }));

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        post(store.url({ court: courtId }), {
            preserveScroll: true,
            onSuccess: () => reset('notes'),
        });
    };

    const confirmDelete = () => {
        if (!deleting) {
            return;
        }

        router.delete(destroy.url({ court: courtId, block: deleting.id }), {
            preserveScroll: true,
            onStart: () => setRemoving(true),
            onFinish: () => {
                setRemoving(false);
                setDeleting(null);
            },
        });
    };

    return (
        <div className="flex flex-col gap-4">
            <div className="flex flex-col gap-3">
                <h2 className="text-sm font-semibold">Blocks for this day</h2>

                {blocks.length === 0 ? (
                    <p className="rounded-lg border border-dashed border-border bg-muted/20 p-4 text-sm text-muted-foreground">
                        No blocks for this date.
                    </p>
                ) : (
                    <ul className="flex flex-col gap-2">
                        {blocks.map((block) => (
                            <li
                                key={block.id}
                                className="flex items-start justify-between gap-3 rounded-lg border border-border p-3"
                            >
                                <div className="grid gap-1">
                                    <div className="flex items-center gap-2">
                                        <span className="font-medium">
                                            {formatHour(block.start_time)} –{' '}
                                            {formatHour(block.end_time)}
                                        </span>
                                        <Badge variant="secondary">
                                            {block.reason_label}
                                        </Badge>
                                    </div>
                                    {block.notes && (
                                        <p className="text-sm text-muted-foreground">
                                            {block.notes}
                                        </p>
                                    )}
                                </div>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    aria-label="Remove block"
                                    onClick={() => setDeleting(block)}
                                >
                                    <Trash2 className="size-4 text-destructive" />
                                </Button>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <form
                onSubmit={submit}
                className="flex flex-col gap-4 rounded-lg border border-border bg-muted/20 p-4"
            >
                <h3 className="text-sm font-semibold">Add a block</h3>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-1">
                        <Label>Start time</Label>
                        <HourSelect
                            value={data.start_time}
                            onChange={(value) => setData('start_time', value)}
                            aria-label="Block start time"
                        />
                        <InputError message={errors.start_time} />
                    </div>

                    <div className="grid gap-1">
                        <Label>End time</Label>
                        <HourSelect
                            value={data.end_time}
                            onChange={(value) => setData('end_time', value)}
                            aria-label="Block end time"
                        />
                        <InputError message={errors.end_time} />
                    </div>
                </div>

                <div className="grid gap-1">
                    <Label htmlFor="reason">Reason</Label>
                    <Select
                        value={data.reason}
                        onValueChange={(value) => setData('reason', value)}
                    >
                        <SelectTrigger id="reason" className="w-full">
                            <SelectValue placeholder="Select a reason" />
                        </SelectTrigger>
                        <SelectContent>
                            {reasons.map((reason) => (
                                <SelectItem
                                    key={reason.value}
                                    value={reason.value}
                                >
                                    {reason.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.reason} />
                </div>

                <div className="grid gap-1">
                    <Label htmlFor="notes">Notes (optional)</Label>
                    <Textarea
                        id="notes"
                        value={data.notes}
                        onChange={(e) => setData('notes', e.target.value)}
                        placeholder="Any extra details"
                    />
                    <InputError message={errors.notes} />
                </div>

                <div className="flex justify-end">
                    <Button type="submit" disabled={processing}>
                        {processing ? (
                            <LoaderCircle className="size-4 animate-spin" />
                        ) : (
                            <Plus className="size-4" />
                        )}
                        Add block
                    </Button>
                </div>
            </form>

            <Dialog
                open={deleting !== null}
                onOpenChange={(open) => !open && setDeleting(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Remove this block?</DialogTitle>
                        <DialogDescription>
                            The blocked hours will become available again.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setDeleting(null)}
                            disabled={removing}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={confirmDelete}
                            disabled={removing}
                        >
                            {removing && (
                                <LoaderCircle className="size-4 animate-spin" />
                            )}
                            Remove
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
