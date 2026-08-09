import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { hourOptions } from '@/lib/time';

type HourSelectProps = {
    value: string | null;
    onChange: (value: string) => void;
    disabled?: boolean;
    id?: string;
    'aria-label'?: string;
};

const OPTIONS = hourOptions();

/**
 * Select constrained to whole-hour values ("HH:00").
 */
export function HourSelect({
    value,
    onChange,
    disabled,
    id,
    'aria-label': ariaLabel,
}: HourSelectProps) {
    return (
        <Select
            value={value ?? undefined}
            onValueChange={onChange}
            disabled={disabled}
        >
            <SelectTrigger id={id} className="w-full" aria-label={ariaLabel}>
                <SelectValue placeholder="—" />
            </SelectTrigger>
            <SelectContent>
                {OPTIONS.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
