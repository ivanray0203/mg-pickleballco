import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { show } from '@/routes/admin/customers';
import type { Customer } from '@/types';

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

export function CustomerTable({ customers }: { customers: Customer[] }) {
    return (
        <div className="hidden overflow-x-auto rounded-xl border border-border bg-card md:block">
            <Table>
                <TableHeader>
                    <TableRow className="bg-muted/40 hover:bg-muted/40">
                        <TableHead>Name</TableHead>
                        <TableHead>Email</TableHead>
                        <TableHead className="text-right">Bookings</TableHead>
                        <TableHead className="text-right">Upcoming</TableHead>
                        <TableHead className="text-right">Total paid</TableHead>
                        <TableHead>Joined</TableHead>
                        <TableHead className="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {customers.map((customer) => (
                        <TableRow key={customer.id}>
                            <TableCell className="font-medium">
                                {customer.name}
                            </TableCell>
                            <TableCell className="text-muted-foreground">
                                {customer.email}
                            </TableCell>
                            <TableCell className="text-right">
                                {customer.total_bookings}
                            </TableCell>
                            <TableCell className="text-right">
                                {customer.upcoming_bookings}
                            </TableCell>
                            <TableCell className="text-right font-medium">
                                {customer.total_paid_formatted}
                            </TableCell>
                            <TableCell className="whitespace-nowrap text-muted-foreground">
                                {formatDate(customer.joined_at)}
                            </TableCell>
                            <TableCell className="text-right">
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={show(customer.id)}>View</Link>
                                </Button>
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}
