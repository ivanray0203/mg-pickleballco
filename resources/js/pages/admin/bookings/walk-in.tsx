import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Minus, Plus, Search, UserPlus, X } from 'lucide-react';
import { useState } from 'react';
import { DurationPicker } from '@/components/client/duration-picker';
import { SlotGrid } from '@/components/client/slot-grid';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/admin/bookings';
import { create, store } from '@/routes/admin/bookings/walk-in';
import type {
    BookingAvailability,
    BookingConstraints,
    SelectOption,
} from '@/types';

type CourtOption = { id: number; name: string; hourly_rate: number };
type EquipmentOption = { id: number; name: string; hourly_rate: number };
type CustomerResult = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
};

type WalkInProps = {
    courts: CourtOption[];
    equipment: EquipmentOption[];
    availability: BookingAvailability | null;
    customerResults: CustomerResult[];
    paymentMethods: SelectOption[];
    constraints: BookingConstraints;
};

const peso = (cents: number) => `₱${(cents / 100).toFixed(2)}`;

export default function WalkIn({
    courts,
    equipment,
    availability,
    customerResults,
    paymentMethods,
    constraints,
}: WalkInProps) {
    const [customerMode, setCustomerMode] = useState<'guest' | 'existing'>(
        'guest',
    );
    const [selectedCustomer, setSelectedCustomer] =
        useState<CustomerResult | null>(null);
    const [customerQuery, setCustomerQuery] = useState('');
    const [quantities, setQuantities] = useState<Record<number, number>>({});

    const form = useForm({
        customer: {
            mode: 'guest',
            user_id: null as number | null,
            name: '',
            phone: '',
            email: '',
        },
        court_id: '' as number | '',
        booking_date: constraints.min_date,
        start_time: '' as string,
        duration: 1,
        payment_method: 'cash',
    });

    const selectedCourt =
        courts.find((c) => c.id === form.data.court_id) ?? null;

    // Reset the time selection whenever the day's availability changes.
    const [trackedDate, setTrackedDate] = useState(availability?.date ?? null);

    if (availability && trackedDate !== availability.date) {
        setTrackedDate(availability.date);

        if (form.data.start_time) {
            form.setData('start_time', '');
        }
    }

    const reloadAvailability = (courtId: number | '', date: string) => {
        if (!courtId) {
            return;
        }

        router.get(
            create.url(),
            { court_id: courtId, date },
            {
                only: ['availability'],
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const onCourtChange = (id: number) => {
        form.setData('court_id', id);
        reloadAvailability(id, form.data.booking_date);
    };

    const onDateChange = (date: string) => {
        form.setData('booking_date', date);
        reloadAvailability(form.data.court_id, date);
    };

    const searchCustomers = (q: string) => {
        setCustomerQuery(q);
        router.get(
            create.url(),
            {
                customer_q: q,
                court_id: form.data.court_id || undefined,
                date: form.data.booking_date,
            },
            {
                only: ['customerResults'],
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const pickCustomer = (customer: CustomerResult) => {
        setSelectedCustomer(customer);
        form.setData('customer', {
            ...form.data.customer,
            mode: 'existing',
            user_id: customer.id,
        });
    };

    const setQty = (id: number, qty: number) => {
        setQuantities((prev) => ({ ...prev, [id]: Math.max(0, qty) }));
    };

    const selectedSlot =
        availability?.slots.find((s) => s.time === form.data.start_time) ??
        null;
    const maxDuration = selectedSlot?.max_duration ?? 0;

    const equipmentTotal = equipment.reduce(
        (sum, item) =>
            sum +
            (quantities[item.id] ?? 0) * item.hourly_rate * form.data.duration,
        0,
    );
    const courtTotal = (selectedCourt?.hourly_rate ?? 0) * form.data.duration;
    const grandTotal = courtTotal + equipmentTotal;

    const canSelectTimes = Boolean(
        availability?.bookable_date && availability?.is_open,
    );

    const customerValid =
        customerMode === 'existing'
            ? Boolean(form.data.customer.user_id)
            : form.data.customer.name.trim().length > 0;

    const canSubmit =
        customerValid &&
        Boolean(selectedCourt) &&
        Boolean(form.data.start_time) &&
        form.data.duration >= 1;

    const submit = () => {
        form.transform((data) => ({
            ...data,
            court_id: Number(data.court_id),
            equipment: Object.entries(quantities)
                .filter(([, qty]) => qty > 0)
                .map(([id, qty]) => ({ id: Number(id), quantity: qty })),
            customer:
                customerMode === 'existing'
                    ? { mode: 'existing', user_id: data.customer.user_id }
                    : {
                          mode: 'guest',
                          name: data.customer.name,
                          phone: data.customer.phone || null,
                          email: data.customer.email || null,
                      },
        }));
        form.post(store.url());
    };

    return (
        <>
            <Head title="Walk-in booking" />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6">
                <div>
                    <Link
                        href={index()}
                        className="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Back to bookings
                    </Link>
                    <h1 className="text-xl font-semibold">Walk-in booking</h1>
                    <p className="text-sm text-muted-foreground">
                        Create a booking for a customer at the counter.
                    </p>
                </div>

                <div className="grid gap-6 lg:grid-cols-[1fr_20rem]">
                    <div className="flex flex-col gap-6">
                        {/* Customer */}
                        <Card>
                            <CardHeader>
                                <CardTitle>1. Customer</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-4">
                                <div className="flex gap-2">
                                    <Button
                                        type="button"
                                        variant={
                                            customerMode === 'guest'
                                                ? 'default'
                                                : 'outline'
                                        }
                                        size="sm"
                                        onClick={() => {
                                            setCustomerMode('guest');
                                            setSelectedCustomer(null);
                                            form.setData('customer', {
                                                ...form.data.customer,
                                                mode: 'guest',
                                                user_id: null,
                                            });
                                        }}
                                    >
                                        <UserPlus className="size-4" />
                                        New guest
                                    </Button>
                                    <Button
                                        type="button"
                                        variant={
                                            customerMode === 'existing'
                                                ? 'default'
                                                : 'outline'
                                        }
                                        size="sm"
                                        onClick={() =>
                                            setCustomerMode('existing')
                                        }
                                    >
                                        <Search className="size-4" />
                                        Existing customer
                                    </Button>
                                </div>

                                {customerMode === 'guest' ? (
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="guest_name">
                                                Name
                                            </Label>
                                            <Input
                                                id="guest_name"
                                                value={form.data.customer.name}
                                                onChange={(e) =>
                                                    form.setData('customer', {
                                                        ...form.data.customer,
                                                        name: e.target.value,
                                                    })
                                                }
                                                placeholder="Juan Dela Cruz"
                                            />
                                            {form.errors['customer.name'] && (
                                                <p className="text-xs text-destructive">
                                                    {
                                                        form.errors[
                                                            'customer.name'
                                                        ]
                                                    }
                                                </p>
                                            )}
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="guest_phone">
                                                Phone (optional)
                                            </Label>
                                            <Input
                                                id="guest_phone"
                                                value={form.data.customer.phone}
                                                onChange={(e) =>
                                                    form.setData('customer', {
                                                        ...form.data.customer,
                                                        phone: e.target.value,
                                                    })
                                                }
                                                placeholder="0917 123 4567"
                                            />
                                        </div>
                                        <div className="grid gap-1.5 sm:col-span-2">
                                            <Label htmlFor="guest_email">
                                                Email (optional)
                                            </Label>
                                            <Input
                                                id="guest_email"
                                                type="email"
                                                value={form.data.customer.email}
                                                onChange={(e) =>
                                                    form.setData('customer', {
                                                        ...form.data.customer,
                                                        email: e.target.value,
                                                    })
                                                }
                                                placeholder="name@example.com"
                                            />
                                            <p className="text-xs text-muted-foreground">
                                                The approval email is sent only
                                                when an address is provided.
                                            </p>
                                            {form.errors['customer.email'] && (
                                                <p className="text-xs text-destructive">
                                                    {
                                                        form.errors[
                                                            'customer.email'
                                                        ]
                                                    }
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                ) : selectedCustomer ? (
                                    <div className="flex items-center justify-between rounded-lg border border-border bg-muted/40 px-3 py-2">
                                        <div>
                                            <p className="text-sm font-medium">
                                                {selectedCustomer.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {selectedCustomer.email ??
                                                    selectedCustomer.phone ??
                                                    '—'}
                                            </p>
                                        </div>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => {
                                                setSelectedCustomer(null);
                                                form.setData('customer', {
                                                    ...form.data.customer,
                                                    user_id: null,
                                                });
                                            }}
                                        >
                                            <X className="size-4" />
                                        </Button>
                                    </div>
                                ) : (
                                    <div className="flex flex-col gap-2">
                                        <Input
                                            value={customerQuery}
                                            onChange={(e) =>
                                                searchCustomers(e.target.value)
                                            }
                                            placeholder="Search by name, email or phone…"
                                        />
                                        {form.errors['customer.user_id'] && (
                                            <p className="text-xs text-destructive">
                                                {
                                                    form.errors[
                                                        'customer.user_id'
                                                    ]
                                                }
                                            </p>
                                        )}
                                        {customerResults.length > 0 && (
                                            <ul className="divide-y divide-border rounded-lg border border-border">
                                                {customerResults.map(
                                                    (customer) => (
                                                        <li key={customer.id}>
                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    pickCustomer(
                                                                        customer,
                                                                    )
                                                                }
                                                                className="flex w-full flex-col items-start px-3 py-2 text-left hover:bg-muted/50"
                                                            >
                                                                <span className="text-sm font-medium">
                                                                    {
                                                                        customer.name
                                                                    }
                                                                </span>
                                                                <span className="text-xs text-muted-foreground">
                                                                    {customer.email ??
                                                                        customer.phone ??
                                                                        '—'}
                                                                </span>
                                                            </button>
                                                        </li>
                                                    ),
                                                )}
                                            </ul>
                                        )}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Court, date, time */}
                        <Card>
                            <CardHeader>
                                <CardTitle>2. Court &amp; time</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-4">
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="court">Court</Label>
                                        <select
                                            id="court"
                                            value={form.data.court_id}
                                            onChange={(e) =>
                                                onCourtChange(
                                                    Number(e.target.value),
                                                )
                                            }
                                            className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                        >
                                            <option value="">
                                                Select a court…
                                            </option>
                                            {courts.map((court) => (
                                                <option
                                                    key={court.id}
                                                    value={court.id}
                                                >
                                                    {court.name} —{' '}
                                                    {peso(court.hourly_rate)}/hr
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="date">Date</Label>
                                        <Input
                                            id="date"
                                            type="date"
                                            min={constraints.min_date}
                                            max={constraints.max_date}
                                            value={form.data.booking_date}
                                            onChange={(e) =>
                                                onDateChange(e.target.value)
                                            }
                                        />
                                    </div>
                                </div>

                                <div className="grid gap-1.5">
                                    <Label>Start time</Label>
                                    {!selectedCourt ? (
                                        <p className="text-sm text-muted-foreground">
                                            Select a court to see available
                                            times.
                                        </p>
                                    ) : !canSelectTimes ? (
                                        <p className="text-sm text-muted-foreground">
                                            The court is closed or unavailable
                                            on this date.
                                        </p>
                                    ) : availability &&
                                      availability.slots.length > 0 ? (
                                        <SlotGrid
                                            slots={availability.slots}
                                            selected={
                                                form.data.start_time
                                                    ? [form.data.start_time]
                                                    : []
                                            }
                                            onSelect={(time) => {
                                                form.setData(
                                                    'start_time',
                                                    time,
                                                );
                                                form.setData('duration', 1);
                                            }}
                                        />
                                    ) : (
                                        <p className="text-sm text-muted-foreground">
                                            No open slots for this date.
                                        </p>
                                    )}
                                    {form.errors.start_time && (
                                        <p className="text-xs text-destructive">
                                            {form.errors.start_time}
                                        </p>
                                    )}
                                </div>

                                {form.data.start_time && maxDuration > 0 && (
                                    <div className="grid gap-1.5">
                                        <Label>Duration</Label>
                                        <DurationPicker
                                            max={Math.min(
                                                maxDuration,
                                                constraints.max_duration,
                                            )}
                                            selected={form.data.duration}
                                            onSelect={(hours) =>
                                                form.setData('duration', hours)
                                            }
                                        />
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Equipment */}
                        {equipment.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>
                                        3. Equipment (optional)
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="flex flex-col divide-y divide-border">
                                    {equipment.map((item) => (
                                        <div
                                            key={item.id}
                                            className="flex items-center justify-between py-3"
                                        >
                                            <div>
                                                <p className="text-sm font-medium">
                                                    {item.name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {peso(item.hourly_rate)}/hr
                                                </p>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="icon"
                                                    onClick={() =>
                                                        setQty(
                                                            item.id,
                                                            (quantities[
                                                                item.id
                                                            ] ?? 0) - 1,
                                                        )
                                                    }
                                                >
                                                    <Minus className="size-4" />
                                                </Button>
                                                <span className="w-6 text-center text-sm">
                                                    {quantities[item.id] ?? 0}
                                                </span>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="icon"
                                                    onClick={() =>
                                                        setQty(
                                                            item.id,
                                                            (quantities[
                                                                item.id
                                                            ] ?? 0) + 1,
                                                        )
                                                    }
                                                >
                                                    <Plus className="size-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Summary & payment */}
                    <div className="flex flex-col gap-6">
                        <Card className="lg:sticky lg:top-6">
                            <CardHeader>
                                <CardTitle>Summary</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-4">
                                <dl className="flex flex-col gap-2 text-sm">
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">
                                            Court
                                        </dt>
                                        <dd>{selectedCourt?.name ?? '—'}</dd>
                                    </div>
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">
                                            When
                                        </dt>
                                        <dd>
                                            {form.data.start_time
                                                ? `${form.data.start_time} · ${form.data.duration}h`
                                                : '—'}
                                        </dd>
                                    </div>
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">
                                            Court subtotal
                                        </dt>
                                        <dd>{peso(courtTotal)}</dd>
                                    </div>
                                    {equipmentTotal > 0 && (
                                        <div className="flex justify-between">
                                            <dt className="text-muted-foreground">
                                                Equipment
                                            </dt>
                                            <dd>{peso(equipmentTotal)}</dd>
                                        </div>
                                    )}
                                    <div className="mt-1 flex justify-between border-t border-border pt-2 font-semibold">
                                        <dt>Total</dt>
                                        <dd>{peso(grandTotal)}</dd>
                                    </div>
                                </dl>

                                <div className="grid gap-1.5">
                                    <Label htmlFor="payment_method">
                                        Payment
                                    </Label>
                                    <select
                                        id="payment_method"
                                        value={form.data.payment_method}
                                        onChange={(e) =>
                                            form.setData(
                                                'payment_method',
                                                e.target.value,
                                            )
                                        }
                                        className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                    >
                                        {paymentMethods.map((method) => (
                                            <option
                                                key={method.value}
                                                value={method.value}
                                            >
                                                {method.label}
                                            </option>
                                        ))}
                                    </select>
                                    <p className="text-xs text-muted-foreground">
                                        {form.data.payment_method === 'cash'
                                            ? 'Booking is confirmed and marked paid immediately.'
                                            : 'The customer is redirected to a GCash checkout to pay.'}
                                    </p>
                                </div>

                                <Button
                                    type="button"
                                    disabled={!canSubmit || form.processing}
                                    onClick={submit}
                                >
                                    {form.data.payment_method === 'cash'
                                        ? 'Create & mark paid'
                                        : 'Create & continue to GCash'}
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}

WalkIn.layout = {
    title: 'Walk-in booking',
};
