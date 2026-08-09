import { Link } from '@inertiajs/react';
import { ImageIcon, Pencil } from 'lucide-react';
import { EquipmentStatusBadge } from '@/components/admin/equipment/equipment-status-badge';
import { EquipmentStatusToggle } from '@/components/admin/equipment/equipment-status-toggle';
import { EquipmentTypeBadge } from '@/components/admin/equipment/equipment-type-badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { edit } from '@/routes/admin/equipment';
import type { Equipment } from '@/types';

export function EquipmentTable({ equipment }: { equipment: Equipment[] }) {
    return (
        <div className="hidden overflow-hidden rounded-xl border border-border bg-card md:block">
            <Table>
                <TableHeader>
                    <TableRow className="bg-muted/40 hover:bg-muted/40">
                        <TableHead>Equipment</TableHead>
                        <TableHead>Type</TableHead>
                        <TableHead>Hourly rate</TableHead>
                        <TableHead>Stock</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead className="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {equipment.map((item) => (
                        <TableRow key={item.id}>
                            <TableCell>
                                <div className="flex items-center gap-3">
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
                                    <span className="font-medium">
                                        {item.name}
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <EquipmentTypeBadge
                                    type={item.type}
                                    label={item.type_label}
                                />
                            </TableCell>
                            <TableCell className="font-medium">
                                {item.hourly_rate_formatted}
                                <span className="text-muted-foreground">
                                    {' '}
                                    /hr
                                </span>
                            </TableCell>
                            <TableCell className="text-muted-foreground">
                                {item.total_quantity} units
                            </TableCell>
                            <TableCell>
                                <EquipmentStatusBadge active={item.is_active} />
                            </TableCell>
                            <TableCell>
                                <div className="flex items-center justify-end gap-2">
                                    <Button asChild variant="outline" size="sm">
                                        <Link
                                            href={edit({ equipment: item.id })}
                                        >
                                            <Pencil className="size-4" />
                                            Edit
                                        </Link>
                                    </Button>
                                    <EquipmentStatusToggle equipment={item} />
                                </div>
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}
