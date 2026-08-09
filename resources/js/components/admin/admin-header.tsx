import { usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { AdminMobileNav } from '@/components/admin/admin-mobile-nav';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { UserMenuContent } from '@/components/user-menu-content';

type AdminHeaderProps = {
    title: string;
};

/**
 * Top bar for the admin panel: mobile menu trigger, current page title,
 * notifications, and the admin profile menu.
 */
export function AdminHeader({ title }: AdminHeaderProps) {
    const { auth } = usePage().props;

    return (
        <header className="sticky top-0 z-10 flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border bg-background/95 px-4 backdrop-blur supports-[backdrop-filter]:bg-background/80 md:px-6">
            <AdminMobileNav />

            <h1 className="truncate text-lg font-semibold">{title}</h1>

            <div className="ml-auto flex items-center gap-1">
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label="Notifications"
                    className="relative"
                >
                    <Bell className="size-5" />
                    <span className="absolute top-2 right-2 size-2 rounded-full bg-primary" />
                </Button>

                {auth.user && (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                variant="ghost"
                                className="flex h-auto items-center gap-2 py-1 pr-1 pl-2"
                                data-test="admin-user-menu"
                            >
                                <UserInfo user={auth.user} />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            className="min-w-56 rounded-lg"
                            align="end"
                        >
                            <UserMenuContent user={auth.user} />
                        </DropdownMenuContent>
                    </DropdownMenu>
                )}
            </div>
        </header>
    );
}
