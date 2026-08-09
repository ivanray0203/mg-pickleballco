import { Home, Trees } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import type { CourtType } from '@/types';

type CourtTypeBadgeProps = {
    type: CourtType;
    label: string;
};

export function CourtTypeBadge({ type, label }: CourtTypeBadgeProps) {
    const Icon = type === 'indoor' ? Home : Trees;

    return (
        <Badge variant="secondary" className="gap-1.5">
            <Icon className="size-3" />
            {label}
        </Badge>
    );
}
