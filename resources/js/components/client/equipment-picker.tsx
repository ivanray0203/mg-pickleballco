import { ImageIcon, Minus, Plus } from 'lucide-react';
import { EquipmentTypeBadge } from '@/components/admin/equipment/equipment-type-badge';
import { Button } from '@/components/ui/button';
import type { EquipmentOption } from '@/types';

type EquipmentPickerProps = {
    equipment: EquipmentOption[];
    /** Booking length in hours; equipment is priced per hour. */
    durationHours: number;
    /** Current selection, keyed by equipment id → quantity. */
    selection: Record<number, number>;
    onChange: (selection: Record<number, number>) => void;
};

/**
 * Lets a customer add rentable equipment (paddles, balls, …) to their booking.
 * Quantity is capped at the units available for the chosen time slot. Prices
 * shown are a live preview; the server recomputes the authoritative total.
 */
export function EquipmentPicker({
    equipment,
    durationHours,
    selection,
    onChange,
}: EquipmentPickerProps) {
    if (equipment.length === 0) {
        return null;
    }

    const setQuantity = (id: number, quantity: number) => {
        const next = { ...selection };

        if (quantity <= 0) {
            delete next[id];
        } else {
            next[id] = quantity;
        }

        onChange(next);
    };

    return (
        <div className="flex flex-col gap-3">
            <div>
                <h2 className="text-base font-semibold">Add equipment</h2>
                <p className="text-sm text-muted-foreground">
                    Optional rentals for your session, priced per hour.
                </p>
            </div>

            <div className="flex flex-col divide-y divide-border overflow-hidden rounded-xl border border-border">
                {equipment.map((item) => {
                    const quantity = selection[item.id] ?? 0;
                    const soldOut = item.available <= 0;
                    const lineTotal =
                        item.hourly_rate * quantity * durationHours;

                    return (
                        <div
                            key={item.id}
                            className="flex items-center gap-3 p-3"
                        >
                            <div className="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-muted">
                                {item.image_url ? (
                                    <img
                                        src={item.image_url}
                                        alt={item.name}
                                        className="size-full object-cover"
                                    />
                                ) : (
                                    <ImageIcon className="size-4 text-muted-foreground" />
                                )}
                            </div>

                            <div className="grid flex-1 gap-0.5">
                                <div className="flex items-center gap-2">
                                    <span className="font-medium">
                                        {item.name}
                                    </span>
                                    <EquipmentTypeBadge
                                        type={item.type}
                                        label={item.type_label}
                                    />
                                </div>
                                <span className="text-xs text-muted-foreground">
                                    {item.hourly_rate_formatted} / hr ·{' '}
                                    {soldOut
                                        ? 'None available'
                                        : `${item.available} available`}
                                </span>
                            </div>

                            {quantity > 0 && (
                                <span className="hidden text-sm font-medium sm:inline">
                                    ₱
                                    {lineTotal.toLocaleString(undefined, {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2,
                                    })}
                                </span>
                            )}

                            <div className="flex items-center gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    className="size-8"
                                    disabled={quantity <= 0}
                                    onClick={() =>
                                        setQuantity(item.id, quantity - 1)
                                    }
                                    aria-label={`Remove one ${item.name}`}
                                >
                                    <Minus className="size-4" />
                                </Button>
                                <span className="w-6 text-center text-sm font-medium tabular-nums">
                                    {quantity}
                                </span>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    className="size-8"
                                    disabled={
                                        soldOut || quantity >= item.available
                                    }
                                    onClick={() =>
                                        setQuantity(item.id, quantity + 1)
                                    }
                                    aria-label={`Add one ${item.name}`}
                                >
                                    <Plus className="size-4" />
                                </Button>
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
