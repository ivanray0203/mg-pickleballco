import { router } from '@inertiajs/react';
import { LoaderCircle, Power, PowerOff } from 'lucide-react';
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
import { toggleStatus } from '@/routes/admin/courts';
import type { Court } from '@/types';

type CourtStatusToggleProps = {
    court: Court;
    /** Render as a compact icon button (used inside table rows). */
    variant?: 'button' | 'menu';
};

/**
 * Confirmation-guarded activate/deactivate control for a court.
 */
export function CourtStatusToggle({
    court,
    variant = 'button',
}: CourtStatusToggleProps) {
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    const deactivating = court.is_active;

    const submit = () => {
        router.patch(
            toggleStatus.url({ court: court.id }),
            {},
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => {
                    setProcessing(false);
                    setOpen(false);
                },
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    variant={deactivating ? 'outline' : 'default'}
                    size={variant === 'button' ? 'sm' : 'default'}
                    className={variant === 'menu' ? 'w-full justify-start' : ''}
                >
                    {deactivating ? (
                        <PowerOff className="size-4" />
                    ) : (
                        <Power className="size-4" />
                    )}
                    {deactivating ? 'Deactivate' : 'Activate'}
                </Button>
            </DialogTrigger>

            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {deactivating
                            ? 'Deactivate this court?'
                            : 'Activate this court?'}
                    </DialogTitle>
                    <DialogDescription>
                        {deactivating
                            ? `"${court.name}" will no longer be available for new customer bookings. Existing bookings are not affected.`
                            : `"${court.name}" will become available for customer bookings again.`}
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
                        variant={deactivating ? 'destructive' : 'default'}
                        onClick={submit}
                        disabled={processing}
                    >
                        {processing && (
                            <LoaderCircle className="size-4 animate-spin" />
                        )}
                        {deactivating ? 'Deactivate' : 'Activate'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
