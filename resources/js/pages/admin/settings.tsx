import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { update } from '@/routes/admin/settings';
import type { AdminSettings, AuditLogEntry } from '@/types';

type SettingsProps = {
    settings: AdminSettings;
    auditLogs: { data: AuditLogEntry[] };
};

function formatDateTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

export default function AdminSettingsPage({
    settings,
    auditLogs,
}: SettingsProps) {
    const { data, setData, put, processing, errors } = useForm<AdminSettings>({
        business_name: settings.business_name,
        contact_email: settings.contact_email ?? '',
        contact_number: settings.contact_number ?? '',
        address: settings.address ?? '',
        booking_hold_minutes: settings.booking_hold_minutes,
        max_booking_hours: settings.max_booking_hours,
        advance_booking_days: settings.advance_booking_days,
        gcash_enabled: settings.gcash_enabled,
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        put(update.url(), { preserveScroll: true });
    };

    return (
        <>
            <Head title="Settings" />

            <form onSubmit={submit} className="flex flex-col gap-6">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Settings</h1>
                        <p className="text-sm text-muted-foreground">
                            Business, booking and payment configuration.
                        </p>
                    </div>
                    <Button type="submit" disabled={processing}>
                        {processing && (
                            <LoaderCircle className="size-4 animate-spin" />
                        )}
                        Save changes
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>General</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-1 sm:col-span-2">
                            <Label htmlFor="business_name">Business name</Label>
                            <Input
                                id="business_name"
                                value={data.business_name}
                                onChange={(e) =>
                                    setData('business_name', e.target.value)
                                }
                            />
                            <InputError message={errors.business_name} />
                        </div>
                        <div className="grid gap-1">
                            <Label htmlFor="contact_email">Contact email</Label>
                            <Input
                                id="contact_email"
                                type="email"
                                value={data.contact_email ?? ''}
                                onChange={(e) =>
                                    setData('contact_email', e.target.value)
                                }
                            />
                            <InputError message={errors.contact_email} />
                        </div>
                        <div className="grid gap-1">
                            <Label htmlFor="contact_number">
                                Contact number
                            </Label>
                            <Input
                                id="contact_number"
                                value={data.contact_number ?? ''}
                                onChange={(e) =>
                                    setData('contact_number', e.target.value)
                                }
                            />
                            <InputError message={errors.contact_number} />
                        </div>
                        <div className="grid gap-1 sm:col-span-2">
                            <Label htmlFor="address">Address</Label>
                            <Input
                                id="address"
                                value={data.address ?? ''}
                                onChange={(e) =>
                                    setData('address', e.target.value)
                                }
                            />
                            <InputError message={errors.address} />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Booking</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-3">
                        <div className="grid gap-1">
                            <Label htmlFor="booking_hold_minutes">
                                Hold duration (minutes)
                            </Label>
                            <Input
                                id="booking_hold_minutes"
                                type="number"
                                min={1}
                                max={120}
                                value={data.booking_hold_minutes}
                                onChange={(e) =>
                                    setData(
                                        'booking_hold_minutes',
                                        Number(e.target.value),
                                    )
                                }
                            />
                            <InputError message={errors.booking_hold_minutes} />
                        </div>
                        <div className="grid gap-1">
                            <Label htmlFor="max_booking_hours">
                                Max duration (hours)
                            </Label>
                            <Input
                                id="max_booking_hours"
                                type="number"
                                min={1}
                                max={24}
                                value={data.max_booking_hours}
                                onChange={(e) =>
                                    setData(
                                        'max_booking_hours',
                                        Number(e.target.value),
                                    )
                                }
                            />
                            <InputError message={errors.max_booking_hours} />
                        </div>
                        <div className="grid gap-1">
                            <Label htmlFor="advance_booking_days">
                                Advance limit (days)
                            </Label>
                            <Input
                                id="advance_booking_days"
                                type="number"
                                min={1}
                                max={365}
                                value={data.advance_booking_days}
                                onChange={(e) =>
                                    setData(
                                        'advance_booking_days',
                                        Number(e.target.value),
                                    )
                                }
                            />
                            <InputError message={errors.advance_booking_days} />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Payment</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <label className="flex items-start gap-3">
                            <Checkbox
                                checked={data.gcash_enabled}
                                onCheckedChange={(checked) =>
                                    setData('gcash_enabled', checked === true)
                                }
                            />
                            <span className="grid gap-0.5">
                                <span className="text-sm font-medium">
                                    GCash enabled
                                </span>
                                <span className="text-sm text-muted-foreground">
                                    Provider credentials are configured on the
                                    server, not here.
                                </span>
                            </span>
                        </label>
                        <InputError
                            className="mt-2"
                            message={errors.gcash_enabled}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Recent admin activity</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {auditLogs.data.length === 0 ? (
                            <p className="rounded-lg border border-dashed border-border bg-muted/20 p-6 text-center text-sm text-muted-foreground">
                                No activity recorded yet.
                            </p>
                        ) : (
                            <ul className="flex flex-col divide-y divide-border">
                                {auditLogs.data.map((log) => (
                                    <li
                                        key={log.id}
                                        className="flex flex-col gap-0.5 py-2 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <span className="text-sm font-medium">
                                            {log.description}
                                            {log.entity && (
                                                <span className="ml-1 font-normal text-muted-foreground">
                                                    · {log.entity}
                                                </span>
                                            )}
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            {log.actor} ·{' '}
                                            {formatDateTime(log.created_at)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </form>
        </>
    );
}

AdminSettingsPage.layout = {
    title: 'Settings',
};
