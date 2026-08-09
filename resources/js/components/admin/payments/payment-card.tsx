import { Link } from '@inertiajs/react';
import { PaymentStatusBadge } from '@/components/admin/payments/payment-status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { show } from '@/routes/admin/payments';
import type { Payment } from '@/types';

export function PaymentCard({ payment }: { payment: Payment }) {
    return (
        <Card>
            <CardContent className="flex flex-col gap-3">
                <div className="flex items-start justify-between gap-2">
                    <div className="grid">
                        <span className="font-mono text-xs text-muted-foreground">
                            {payment.payment_reference}
                        </span>
                        <span className="font-medium">
                            {payment.booking?.customer.name ?? '—'}
                        </span>
                    </div>
                    <PaymentStatusBadge
                        status={payment.status}
                        label={payment.status_label}
                    />
                </div>

                <div className="grid gap-1 text-sm text-muted-foreground">
                    <span>
                        {payment.payment_method_label} ·{' '}
                        {payment.provider_label}
                    </span>
                    {payment.booking && (
                        <span className="font-mono text-xs">
                            {payment.booking.reference}
                        </span>
                    )}
                </div>

                <div className="flex items-center justify-between">
                    <span className="font-semibold">
                        {payment.amount_formatted}
                    </span>
                    <Button variant="outline" size="sm" asChild>
                        <Link href={show(payment.id)}>View</Link>
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}
