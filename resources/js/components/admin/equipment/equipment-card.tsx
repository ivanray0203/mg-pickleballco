import { Link } from '@inertiajs/react';
import { ImageIcon, Layers, Pencil } from 'lucide-react';
import { EquipmentStatusBadge } from '@/components/admin/equipment/equipment-status-badge';
import { EquipmentStatusToggle } from '@/components/admin/equipment/equipment-status-toggle';
import { EquipmentTypeBadge } from '@/components/admin/equipment/equipment-type-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { edit } from '@/routes/admin/equipment';
import type { Equipment } from '@/types';

export function EquipmentCard({ equipment }: { equipment: Equipment }) {
    return (
        <Card className="overflow-hidden py-0">
            <div className="flex aspect-video w-full items-center justify-center bg-muted">
                {equipment.image_url ? (
                    <img
                        src={equipment.image_url}
                        alt={equipment.name}
                        className="size-full object-cover"
                    />
                ) : (
                    <ImageIcon className="size-8 text-muted-foreground" />
                )}
            </div>

            <CardContent className="flex flex-col gap-3 py-4">
                <div className="flex items-start justify-between gap-2">
                    <div className="grid gap-1">
                        <h3 className="font-semibold">{equipment.name}</h3>
                        <p className="text-sm text-muted-foreground">
                            {equipment.hourly_rate_formatted} / hr
                        </p>
                    </div>
                    <EquipmentStatusBadge active={equipment.is_active} />
                </div>

                <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                    <EquipmentTypeBadge
                        type={equipment.type}
                        label={equipment.type_label}
                    />
                    <span className="flex items-center gap-1">
                        <Layers className="size-3.5" />
                        {equipment.total_quantity} units
                    </span>
                </div>

                <div className="flex items-center gap-2 border-t border-border pt-3">
                    <Button
                        asChild
                        variant="outline"
                        size="sm"
                        className="flex-1"
                    >
                        <Link href={edit({ equipment: equipment.id })}>
                            <Pencil className="size-4" />
                            Edit
                        </Link>
                    </Button>
                    <EquipmentStatusToggle equipment={equipment} />
                </div>
            </CardContent>
        </Card>
    );
}
