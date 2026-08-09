import { useForm } from '@inertiajs/react';
import { LoaderCircle, XCircle } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
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
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cancel } from '@/routes/admin/bookings';
import type { Booking } from '@/types';

export function CancelBookingDialog({ booking }: { booking: Booking }) {
    const [open, setOpen] = useState(false);

    const { data, setData, patch, processing, errors, reset } = useForm({
        cancellation_reason: '',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        patch(cancel.url(booking.id), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="destructive">
                    <XCircle className="size-4" />
                    Cancel booking
                </Button>
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>Cancel this booking?</DialogTitle>
                        <DialogDescription>
                            This releases the reserved slot. The booking is kept
                            for records and marked as cancelled.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-1 py-4">
                        <Label htmlFor="cancellation_reason">
                            Cancellation reason
                        </Label>
                        <Textarea
                            id="cancellation_reason"
                            value={data.cancellation_reason}
                            onChange={(e) =>
                                setData('cancellation_reason', e.target.value)
                            }
                            placeholder="Why is this booking being cancelled?"
                            autoFocus
                        />
                        <InputError message={errors.cancellation_reason} />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpen(false)}
                            disabled={processing}
                        >
                            Keep booking
                        </Button>
                        <Button
                            type="submit"
                            variant="destructive"
                            disabled={processing}
                        >
                            {processing && (
                                <LoaderCircle className="size-4 animate-spin" />
                            )}
                            Cancel booking
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
