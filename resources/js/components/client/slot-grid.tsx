import { cn } from '@/lib/utils';
import type { BookingSlot, BookingSlotState } from '@/types';

type SlotGridProps = {
    slots: BookingSlot[];
    /** Every hour currently in the selection, in order. */
    selected: string[];
    onSelect: (time: string) => void;
};

/** The caption under an hour that cannot be booked, or none when it is free. */
const STATE_CAPTIONS: Record<BookingSlotState, string | null> = {
    available: null,
    booked: 'Booked',
    open_play: 'Open Play',
    unavailable: 'Unavailable',
    past: 'Past',
};

/**
 * How each state reads at a glance: a booked hour is held by another customer,
 * an open-play hour is a session the customer could join instead, and the rest
 * are simply closed.
 */
const STATE_STYLES: Record<BookingSlotState, string> = {
    available:
        'border-border bg-background text-foreground hover:border-primary hover:bg-primary/5',
    booked: 'cursor-not-allowed border-amber-500/40 bg-amber-500/10 text-amber-700 dark:text-amber-500',
    open_play:
        'cursor-not-allowed border-sky-500/40 bg-sky-500/10 text-sky-700 dark:text-sky-400',
    unavailable:
        'cursor-not-allowed border-dashed border-border bg-muted/50 text-muted-foreground/60',
    past: 'cursor-not-allowed border-dashed border-border bg-muted/50 text-muted-foreground/60',
};

/**
 * Touch-friendly whole-hour slot grid. Each hour reads as Available, Selected,
 * Booked, Open Play, Past or Unavailable — enough for the customer to
 * understand why they can't pick an hour, without exposing who booked it or
 * why the court is blocked.
 */
export function SlotGrid({ slots, selected, onSelect }: SlotGridProps) {
    return (
        <div
            className="grid grid-cols-3 gap-2 sm:grid-cols-4"
            role="group"
            aria-label="Available times"
        >
            {slots.map((slot) => {
                const isSelected = selected.includes(slot.time);
                const caption = isSelected ? null : STATE_CAPTIONS[slot.state];

                return (
                    <button
                        key={slot.time}
                        type="button"
                        disabled={!slot.available}
                        aria-pressed={isSelected}
                        aria-label={
                            caption ? `${slot.label} — ${caption}` : slot.label
                        }
                        onClick={() => onSelect(slot.time)}
                        className={cn(
                            'flex min-h-12 flex-col items-center justify-center gap-0.5 rounded-lg border px-2 py-2 text-sm font-medium transition-colors',
                            isSelected
                                ? 'border-primary bg-primary text-primary-foreground'
                                : STATE_STYLES[slot.state],
                        )}
                    >
                        <span
                            className={cn(
                                slot.state === 'past' && 'line-through',
                            )}
                        >
                            {slot.label}
                        </span>
                        {caption && (
                            <span className="text-[0.625rem] leading-none font-normal tracking-wide uppercase">
                                {caption}
                            </span>
                        )}
                    </button>
                );
            })}
        </div>
    );
}
