import { cn } from '@/lib/utils';
import type { BookingSlot } from '@/types';

type SlotGridProps = {
    slots: BookingSlot[];
    selected: string | null;
    onSelect: (time: string) => void;
};

/**
 * Touch-friendly whole-hour slot grid. Each slot is Available, Selected or
 * Unavailable — the customer is never shown why a slot is unavailable.
 */
export function SlotGrid({ slots, selected, onSelect }: SlotGridProps) {
    return (
        <div
            className="grid grid-cols-3 gap-2 sm:grid-cols-4"
            role="group"
            aria-label="Available start times"
        >
            {slots.map((slot) => {
                const isSelected = slot.time === selected;

                return (
                    <button
                        key={slot.time}
                        type="button"
                        disabled={!slot.available}
                        aria-pressed={isSelected}
                        onClick={() => onSelect(slot.time)}
                        className={cn(
                            'flex min-h-12 items-center justify-center rounded-lg border px-2 py-3 text-sm font-medium transition-colors',
                            isSelected &&
                                'border-primary bg-primary text-primary-foreground',
                            !isSelected &&
                                slot.available &&
                                'border-border bg-background text-foreground hover:border-primary hover:bg-primary/5',
                            !slot.available &&
                                'cursor-not-allowed border-dashed border-border bg-muted/50 text-muted-foreground/50 line-through',
                        )}
                    >
                        {slot.label}
                    </button>
                );
            })}
        </div>
    );
}
