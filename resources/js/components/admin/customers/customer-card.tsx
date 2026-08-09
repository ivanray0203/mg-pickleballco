import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { show } from '@/routes/admin/customers';
import type { Customer } from '@/types';

export function CustomerCard({ customer }: { customer: Customer }) {
    return (
        <Card>
            <CardContent className="flex flex-col gap-3">
                <div className="grid">
                    <span className="font-medium">{customer.name}</span>
                    <span className="text-sm text-muted-foreground">
                        {customer.email}
                    </span>
                </div>

                <div className="grid grid-cols-3 gap-2 text-center text-sm">
                    <div className="rounded-lg bg-muted/40 p-2">
                        <p className="font-semibold">
                            {customer.total_bookings}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            Bookings
                        </p>
                    </div>
                    <div className="rounded-lg bg-muted/40 p-2">
                        <p className="font-semibold">
                            {customer.upcoming_bookings}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            Upcoming
                        </p>
                    </div>
                    <div className="rounded-lg bg-muted/40 p-2">
                        <p className="font-semibold">
                            {customer.total_paid_formatted}
                        </p>
                        <p className="text-xs text-muted-foreground">Paid</p>
                    </div>
                </div>

                <Button variant="outline" size="sm" asChild className="w-full">
                    <Link href={show(customer.id)}>View customer</Link>
                </Button>
            </CardContent>
        </Card>
    );
}
