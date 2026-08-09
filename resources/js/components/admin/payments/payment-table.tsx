import { Link } from '@inertiajs/react';
import { PaymentStatusBadge } from '@/components/admin/payments/payment-status-badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { show } from '@/routes/admin/payments';
import type { Payment } from '@/types';

function formatDateTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

export function PaymentTable({ payments }: { payments: Payment[] }) {
    return (
        <div className="hidden overflow-x-auto rounded-xl border border-border bg-card lg:block">
            <Table>
                <TableHeader>
                    <TableRow className="bg-muted/40 hover:bg-muted/40">
                        <TableHead>Payment</TableHead>
                        <TableHead>Booking</TableHead>
                        <TableHead>Customer</TableHead>
                        <TableHead>Method</TableHead>
                        <TableHead>Amount</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Provider</TableHead>
                        <TableHead>Paid at</TableHead>
                        <TableHead>Created</TableHead>
                        <TableHead className="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {payments.map((payment) => (
                        <TableRow key={payment.id}>
                            <TableCell className="font-mono text-xs font-medium">
                                {payment.payment_reference}
                            </TableCell>
                            <TableCell className="font-mono text-xs text-muted-foreground">
                                {payment.booking?.reference ?? '—'}
                            </TableCell>
                            <TableCell>
                                {payment.booking?.customer.name ?? '—'}
                            </TableCell>
                            <TableCell>
                                {payment.payment_method_label}
                            </TableCell>
                            <TableCell className="font-medium">
                                {payment.amount_formatted}
                            </TableCell>
                            <TableCell>
                                <PaymentStatusBadge
                                    status={payment.status}
                                    label={payment.status_label}
                                />
                            </TableCell>
                            <TableCell className="text-muted-foreground">
                                {payment.provider_label}
                            </TableCell>
                            <TableCell className="whitespace-nowrap text-muted-foreground">
                                {formatDateTime(payment.paid_at)}
                            </TableCell>
                            <TableCell className="whitespace-nowrap text-muted-foreground">
                                {formatDateTime(payment.created_at)}
                            </TableCell>
                            <TableCell className="text-right">
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={show(payment.id)}>View</Link>
                                </Button>
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}
