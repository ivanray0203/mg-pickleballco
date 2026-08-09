import { CircleDot, Footprints, Package, Zap } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import type { EquipmentType } from '@/types';

type EquipmentTypeBadgeProps = {
    type: EquipmentType;
    label: string;
};

const ICONS = {
    paddle: Zap,
    ball: CircleDot,
    shoes: Footprints,
    other: Package,
} as const;

export function EquipmentTypeBadge({ type, label }: EquipmentTypeBadgeProps) {
    const Icon = ICONS[type] ?? Package;

    return (
        <Badge variant="secondary" className="gap-1.5">
            <Icon className="size-3" />
            {label}
        </Badge>
    );
}
