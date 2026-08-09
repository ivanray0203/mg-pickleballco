import { Head } from '@inertiajs/react';
import { CreditCard } from 'lucide-react';
import { PaymentCard } from '@/components/admin/payments/payment-card';
import { PaymentFilters } from '@/components/admin/payments/payment-filters';
import { PaymentTable } from '@/components/admin/payments/payment-table';
import { Pagination } from '@/components/shared/pagination';
import type {
    Paginated,
    Payment,
    PaymentFilters as PaymentFiltersType,
    SelectOption,
} from '@/types';

type PaymentsIndexProps = {
    payments: Paginated<Payment>;
    filters: PaymentFiltersType;
    statuses: SelectOption[];
    methods: SelectOption[];
};

export default function PaymentsIndex({
    payments,
    filters,
    statuses,
    methods,
}: PaymentsIndexProps) {
    const hasFilters =
        Boolean(filters.search) ||
        Boolean(filters.status) ||
        Boolean(filters.method) ||
        Boolean(filters.date);
    const isEmpty = payments.data.length === 0;

    return (
        <>
            <Head title="Payments" />

            <div className="flex flex-col gap-6">
                <div>
                    <h1 className="text-xl font-semibold">Payments</h1>
                    <p className="text-sm text-muted-foreground">
                        Monitor GCash payments and their settlement status.
                    </p>
                </div>

                <PaymentFilters
                    filters={filters}
                    statuses={statuses}
                    methods={methods}
                />

                {isEmpty ? (
                    <div className="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border bg-muted/20 p-12 text-center">
                        <span className="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <CreditCard className="size-6" />
                        </span>
                        <div className="grid gap-1">
                            <p className="font-medium">
                                {hasFilters
                                    ? 'No payments match your filters'
                                    : 'No payments yet'}
                            </p>
                            <p className="max-w-sm text-sm text-muted-foreground">
                                {hasFilters
                                    ? 'Try adjusting your search or filters.'
                                    : 'Payments will appear here once customers pay for bookings.'}
                            </p>
                        </div>
                    </div>
                ) : (
                    <>
                        <PaymentTable payments={payments.data} />

                        <div className="grid gap-4 sm:grid-cols-2 lg:hidden">
                            {payments.data.map((payment) => (
                                <PaymentCard
                                    key={payment.id}
                                    payment={payment}
                                />
                            ))}
                        </div>

                        <Pagination meta={payments.meta} />
                    </>
                )}
            </div>
        </>
    );
}

PaymentsIndex.layout = {
    title: 'Payments',
};
