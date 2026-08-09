import { Head, Link } from '@inertiajs/react';
import { CreditCard } from 'lucide-react';
import { PaymentStatusBadge } from '@/components/admin/payments/payment-status-badge';
import { Pagination } from '@/components/shared/pagination';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { show } from '@/routes/payments';
import type { Paginated, Payment } from '@/types';

type PaymentsProps = {
    payments: Paginated<Payment>;
};

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

export default function Payments({ payments }: PaymentsProps) {
    const isEmpty = payments.data.length === 0;

    return (
        <>
            <Head title="Payments" />

            <div className="flex flex-col gap-6">
                <div>
                    <h1 className="text-2xl font-semibold">Payments</h1>
                    <p className="text-sm text-muted-foreground">
                        Your payment history.
                    </p>
                </div>

                {isEmpty ? (
                    <div className="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border bg-background p-12 text-center">
                        <span className="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <CreditCard className="size-6" />
                        </span>
                        <div className="grid gap-1">
                            <p className="font-medium">No payments yet</p>
                            <p className="max-w-sm text-sm text-muted-foreground">
                                Payments for your bookings will appear here.
                            </p>
                        </div>
                    </div>
                ) : (
                    <>
                        {/* Desktop table */}
                        <div className="hidden overflow-x-auto rounded-xl border border-border bg-card md:block">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-muted/40 hover:bg-muted/40">
                                        <TableHead>Payment</TableHead>
                                        <TableHead>Booking</TableHead>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Method</TableHead>
                                        <TableHead>Amount</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {payments.data.map((payment) => (
                                        <TableRow key={payment.id}>
                                            <TableCell className="font-mono text-xs font-medium">
                                                {payment.payment_reference}
                                            </TableCell>
                                            <TableCell className="font-mono text-xs text-muted-foreground">
                                                {payment.booking?.reference ??
                                                    '—'}
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap text-muted-foreground">
                                                {formatDate(payment.created_at)}
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
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={show.url({
                                                            payment: payment.id,
                                                        })}
                                                    >
                                                        View
                                                    </Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        {/* Mobile cards */}
                        <div className="flex flex-col gap-3 md:hidden">
                            {payments.data.map((payment) => (
                                <Card key={payment.id}>
                                    <CardContent className="flex flex-col gap-3">
                                        <div className="flex items-start justify-between gap-2">
                                            <span className="font-mono text-xs text-muted-foreground">
                                                {payment.payment_reference}
                                            </span>
                                            <PaymentStatusBadge
                                                status={payment.status}
                                                label={payment.status_label}
                                            />
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm text-muted-foreground">
                                                {payment.payment_method_label} ·{' '}
                                                {formatDate(payment.created_at)}
                                            </span>
                                            <span className="font-semibold">
                                                {payment.amount_formatted}
                                            </span>
                                        </div>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                            className="w-full"
                                        >
                                            <Link
                                                href={show.url({
                                                    payment: payment.id,
                                                })}
                                            >
                                                View
                                            </Link>
                                        </Button>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>

                        <Pagination meta={payments.meta} />
                    </>
                )}
            </div>
        </>
    );
}
