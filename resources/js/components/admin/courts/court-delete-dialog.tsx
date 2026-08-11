import { router } from '@inertiajs/react';
import { LoaderCircle, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { destroy } from '@/routes/admin/courts';
import type { Court } from '@/types';

type CourtDeleteDialogProps = {
    court: Court;
    /** Render full width, for the stacked mobile card layout. */
    variant?: 'button' | 'block';
};

/**
 * Confirmation-guarded permanent delete for a court.
 *
 * Courts with bookings or open play sessions cannot be deleted — those rows
 * cascade — so the trigger is disabled with an explanation instead.
 */
export function CourtDeleteDialog({
    court,
    variant = 'button',
}: CourtDeleteDialogProps) {
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    const submit = () => {
        router.delete(destroy.url({ court: court.id }), {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setOpen(false);
            },
        });
    };

    if (!court.can_delete) {
        return (
            <Tooltip>
                <TooltipTrigger asChild>
                    {/* Disabled buttons swallow pointer events, so the tooltip
                        needs a wrapper it can hang off. */}
                    <span className={variant === 'block' ? 'flex-1' : ''}>
                        <Button
                            variant="outline"
                            size="sm"
                            className="w-full text-muted-foreground"
                            disabled
                            aria-label={`Delete ${court.name}`}
                        >
                            <Trash2 className="size-4" />
                            Delete
                        </Button>
                    </span>
                </TooltipTrigger>
                <TooltipContent>
                    This court has bookings or open play sessions. Deactivate it
                    instead.
                </TooltipContent>
            </Tooltip>
        );
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    variant="outline"
                    size="sm"
                    className={
                        variant === 'block'
                            ? 'flex-1 text-destructive hover:text-destructive'
                            : 'text-destructive hover:text-destructive'
                    }
                    aria-label={`Delete ${court.name}`}
                >
                    <Trash2 className="size-4" />
                    Delete
                </Button>
            </DialogTrigger>

            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete this court?</DialogTitle>
                    <DialogDescription>
                        &quot;{court.name}&quot; will be permanently removed,
                        along with its weekly schedule, availability blocks and
                        image. This cannot be undone.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={() => setOpen(false)}
                        disabled={processing}
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        onClick={submit}
                        disabled={processing}
                    >
                        {processing && (
                            <LoaderCircle className="size-4 animate-spin" />
                        )}
                        Delete court
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
