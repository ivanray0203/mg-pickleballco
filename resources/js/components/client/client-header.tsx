import { Link, usePage } from '@inertiajs/react';
import { Bell, Menu } from 'lucide-react';
import { useState } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { clientNavItems } from '@/components/client/client-nav-items';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { UserMenuContent } from '@/components/user-menu-content';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as notificationsIndex } from '@/routes/notifications';

function Brand() {
    const { name } = usePage().props;

    return (
        <Link href={dashboard()} className="flex items-center gap-2">
            <span className="flex size-9 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                <AppLogoIcon className="size-5 fill-current" />
            </span>
            <span className="text-base font-semibold">{name}</span>
        </Link>
    );
}

export function ClientHeader() {
    const { auth, unreadNotifications } = usePage().props;
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const getInitials = useInitials();
    const [mobileOpen, setMobileOpen] = useState(false);

    const user = auth.user;
    const unreadCount = unreadNotifications ?? 0;
    const hasUnread = unreadCount > 0;
    const unreadLabel = hasUnread
        ? `Notifications, ${unreadCount} unread`
        : 'Notifications';

    return (
        <header className="sticky top-0 z-40 border-b border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80">
            <div className="mx-auto flex h-16 w-full max-w-7xl items-center justify-between gap-4 px-4 sm:px-6">
                <div className="flex items-center gap-8">
                    <Brand />

                    <nav
                        className="hidden items-center gap-1 md:flex"
                        aria-label="Primary"
                    >
                        {clientNavItems.map((item) => {
                            const active = isCurrentOrParentUrl(item.href);

                            return (
                                <Link
                                    key={item.title}
                                    href={item.href}
                                    prefetch
                                    aria-current={active ? 'page' : undefined}
                                    className={cn(
                                        'rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                        active
                                            ? 'bg-primary/10 text-primary'
                                            : 'text-foreground/70 hover:bg-accent hover:text-accent-foreground',
                                    )}
                                >
                                    {item.title}
                                </Link>
                            );
                        })}
                    </nav>
                </div>

                <div className="flex items-center gap-1.5">
                    <Button
                        variant="ghost"
                        size="icon"
                        asChild
                        aria-label={unreadLabel}
                    >
                        <Link
                            href={notificationsIndex()}
                            className="relative"
                            aria-current={
                                isCurrentOrParentUrl(notificationsIndex())
                                    ? 'page'
                                    : undefined
                            }
                        >
                            <Bell className="size-5" />
                            {hasUnread && (
                                <span
                                    className="absolute -top-0.5 -right-0.5 flex min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] leading-4 font-semibold text-primary-foreground"
                                    aria-hidden="true"
                                >
                                    {unreadCount > 9 ? '9+' : unreadCount}
                                </span>
                            )}
                        </Link>
                    </Button>

                    {user && (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="ghost"
                                    className="size-9 rounded-full p-0"
                                    aria-label="Account menu"
                                >
                                    <Avatar className="size-9">
                                        <AvatarImage
                                            src={user.avatar}
                                            alt={user.name}
                                        />
                                        <AvatarFallback className="bg-primary/10 text-primary">
                                            {getInitials(user.name)}
                                        </AvatarFallback>
                                    </Avatar>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent className="w-56" align="end">
                                <UserMenuContent user={user} />
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}

                    <Sheet open={mobileOpen} onOpenChange={setMobileOpen}>
                        <SheetTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="md:hidden"
                                aria-label="Open menu"
                            >
                                <Menu className="size-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" className="w-72">
                            <SheetHeader>
                                <SheetTitle className="text-left">
                                    Menu
                                </SheetTitle>
                            </SheetHeader>
                            <nav
                                className="mt-2 flex flex-col gap-1 px-2"
                                aria-label="Mobile"
                            >
                                {clientNavItems.map((item) => {
                                    const active = isCurrentOrParentUrl(
                                        item.href,
                                    );

                                    return (
                                        <Link
                                            key={item.title}
                                            href={item.href}
                                            prefetch
                                            onClick={() => setMobileOpen(false)}
                                            aria-current={
                                                active ? 'page' : undefined
                                            }
                                            className={cn(
                                                'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                                                active
                                                    ? 'bg-primary text-primary-foreground'
                                                    : 'text-foreground/80 hover:bg-accent hover:text-accent-foreground',
                                            )}
                                        >
                                            {item.icon && (
                                                <item.icon className="size-4" />
                                            )}
                                            {item.title}
                                        </Link>
                                    );
                                })}
                            </nav>
                        </SheetContent>
                    </Sheet>
                </div>
            </div>
        </header>
    );
}
