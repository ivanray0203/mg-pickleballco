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
import { toggleStatus } from '@/routes/admin/equipment';
import type { Equipment } from '@/types';

type EquipmentStatusToggleProps = {
    equipment: Equipment;
    /** Render as a compact icon button (used inside table rows). */
    variant?: 'button' | 'menu';
};

/**
 * Confirmation-guarded activate/deactivate control for an equipment item.
 */
export function EquipmentStatusToggle({
    equipment,
    variant = 'button',
}: EquipmentStatusToggleProps) {
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    const deactivating = equipment.is_active;

    const submit = () => {
        router.patch(
            toggleStatus.url({ equipment: equipment.id }),
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
                            ? 'Deactivate this equipment?'
                            : 'Activate this equipment?'}
                    </DialogTitle>
                    <DialogDescription>
                        {deactivating
                            ? `"${equipment.name}" will no longer be offered for rental on new bookings. Existing bookings are not affected.`
                            : `"${equipment.name}" will be offered for rental again.`}
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
