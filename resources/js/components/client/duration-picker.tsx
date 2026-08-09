import { cn } from '@/lib/utils';

type DurationPickerProps = {
    max: number;
    selected: number;
    onSelect: (hours: number) => void;
};

/**
 * Offers only durations whose every consecutive hour is available — `max` is
 * computed server-side (consecutive availability capped by the configured
 * maximum booking duration).
 */
export function DurationPicker({
    max,
    selected,
    onSelect,
}: DurationPickerProps) {
    const options = Array.from({ length: max }, (_, i) => i + 1);

    return (
        <div
            className="flex flex-wrap gap-2"
            role="group"
            aria-label="Duration"
        >
            {options.map((hours) => (
                <button
                    key={hours}
                    type="button"
                    aria-pressed={hours === selected}
                    onClick={() => onSelect(hours)}
                    className={cn(
                        'rounded-lg border px-4 py-2 text-sm font-medium transition-colors',
                        hours === selected
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-border bg-background hover:border-primary hover:bg-primary/5',
                    )}
                >
                    {hours} {hours === 1 ? 'Hour' : 'Hours'}
                </button>
            ))}
        </div>
    );
}
