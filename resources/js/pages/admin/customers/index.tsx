import { Head } from '@inertiajs/react';
import { Users } from 'lucide-react';
import { CustomerCard } from '@/components/admin/customers/customer-card';
import { CustomerFilters } from '@/components/admin/customers/customer-filters';
import { CustomerTable } from '@/components/admin/customers/customer-table';
import { Pagination } from '@/components/shared/pagination';
import type {
    Customer,
    CustomerFilters as CustomerFiltersType,
    Paginated,
} from '@/types';

type CustomersIndexProps = {
    customers: Paginated<Customer>;
    filters: CustomerFiltersType;
};

export default function CustomersIndex({
    customers,
    filters,
}: CustomersIndexProps) {
    const isEmpty = customers.data.length === 0;

    return (
        <>
            <Head title="Customers" />

            <div className="flex flex-col gap-6">
                <div>
                    <h1 className="text-xl font-semibold">Customers</h1>
                    <p className="text-sm text-muted-foreground">
                        Operational visibility into customer bookings and
                        payments.
                    </p>
                </div>

                <CustomerFilters filters={filters} />

                {isEmpty ? (
                    <div className="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border bg-muted/20 p-12 text-center">
                        <span className="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <Users className="size-6" />
                        </span>
                        <div className="grid gap-1">
                            <p className="font-medium">
                                {filters.search
                                    ? 'No customers match your search'
                                    : 'No customers yet'}
                            </p>
                            <p className="max-w-sm text-sm text-muted-foreground">
                                {filters.search
                                    ? 'Try a different name or email.'
                                    : 'Customers will appear here once people register.'}
                            </p>
                        </div>
                    </div>
                ) : (
                    <>
                        <CustomerTable customers={customers.data} />

                        <div className="grid gap-4 sm:grid-cols-2 md:hidden">
                            {customers.data.map((customer) => (
                                <CustomerCard
                                    key={customer.id}
                                    customer={customer}
                                />
                            ))}
                        </div>

                        <Pagination meta={customers.meta} />
                    </>
                )}
            </div>
        </>
    );
}

CustomersIndex.layout = {
    title: 'Customers',
};
