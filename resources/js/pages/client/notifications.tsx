import { Head, Link, router } from '@inertiajs/react';
import { Bell, Check, CheckCheck } from 'lucide-react';
import { Pagination } from '@/components/shared/pagination';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { show as bookingShow } from '@/routes/bookings';
import { read, readAll } from '@/routes/notifications';
import { show as openPlayShow } from '@/routes/open-play';
import type { NotificationItem, PaginatedNotifications } from '@/types';

type NotificationsProps = {
    notifications: PaginatedNotifications;
    unread_count: number;
};

function formatWhen(value: string | null): string {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleString(undefined, {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

function markAsRead(id: string): void {
    router.patch(read.url(id), {}, { preserveScroll: true });
}

function markAllAsRead(): void {
    router.patch(readAll.url(), {}, { preserveScroll: true });
}

function NotificationRow({ item }: { item: NotificationItem }) {
    const unread = item.read_at === null;

    return (
        <Card
            className={cn(
                'transition-colors',
                unread && 'border-primary/40 bg-primary/5',
            )}
        >
            <CardContent className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div className="flex min-w-0 items-start gap-3">
                    <span
                        className={cn(
                            'mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full',
                            unread
                                ? 'bg-primary/15 text-primary'
                                : 'bg-muted text-muted-foreground',
                        )}
                        aria-hidden="true"
                    >
                        <Bell className="size-4" />
                    </span>

                    <div className="min-w-0 space-y-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <p className="font-medium">{item.title}</p>
                            {unread && (
                                <span className="rounded-full bg-primary px-2 py-0.5 text-[11px] font-semibold tracking-wide text-primary-foreground uppercase">
                                    Unread
                                </span>
                            )}
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {item.message}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            {formatWhen(item.created_at)}
                        </p>
                    </div>
                </div>

                <div className="flex shrink-0 items-center gap-2 sm:flex-col sm:items-end">
                    {item.booking_id !== null && (
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={bookingShow.url({
                                    booking: item.booking_id,
                                })}
                            >
                                View booking
                            </Link>
                        </Button>
                    )}
                    {item.session_id !== null && (
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={openPlayShow.url({
                                    openPlaySession: item.session_id,
                                })}
                            >
                                View session
                            </Link>
                        </Button>
                    )}
                    {unread && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => markAsRead(item.id)}
                        >
                            <Check className="size-4" />
                            Mark read
                        </Button>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

export default function Notifications({
    notifications,
    unread_count,
}: NotificationsProps) {
    const isEmpty = notifications.data.length === 0;

    return (
        <>
            <Head title="Notifications" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Notifications
                        </h1>
                        <p
                            className="text-sm text-muted-foreground"
                            aria-live="polite"
                        >
                            {unread_count > 0
                                ? `You have ${unread_count} unread notification${
                                      unread_count === 1 ? '' : 's'
                                  }.`
                                : 'You are all caught up.'}
                        </p>
                    </div>

                    <Button
                        variant="outline"
                        size="sm"
                        onClick={markAllAsRead}
                        disabled={unread_count === 0}
                    >
                        <CheckCheck className="size-4" />
                        Mark all as read
                    </Button>
                </div>

                {isEmpty ? (
                    <div className="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border bg-background p-12 text-center">
                        <span className="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <Bell className="size-6" />
                        </span>
                        <div className="grid gap-1">
                            <p className="font-medium">No notifications yet</p>
                            <p className="max-w-sm text-sm text-muted-foreground">
                                Booking, payment, and open play updates will
                                appear here.
                            </p>
                        </div>
                    </div>
                ) : (
                    <>
                        <div className="flex flex-col gap-3">
                            {notifications.data.map((item) => (
                                <NotificationRow key={item.id} item={item} />
                            ))}
                        </div>

                        <Pagination meta={notifications.meta} />
                    </>
                )}
            </div>
        </>
    );
}
