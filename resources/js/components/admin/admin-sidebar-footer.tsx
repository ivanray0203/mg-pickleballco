import { Link, router, usePage } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import { UserInfo } from '@/components/user-info';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { logout } from '@/routes';
import { edit as editProfile } from '@/routes/profile';

/**
 * Bottom section of the admin sidebar: a link to the profile settings and a
 * logout action.
 */
export function AdminSidebarFooter() {
    const { auth } = usePage().props;
    const cleanup = useMobileNavigation();

    const handleLogout = () => {
        cleanup();
        router.flushAll();
    };

    if (!auth.user) {
        return null;
    }

    return (
        <div className="flex flex-col gap-1 border-t border-sidebar-border pt-3">
            <Link
                href={editProfile()}
                prefetch
                onClick={cleanup}
                className="flex items-center gap-2 rounded-lg px-2 py-2 text-sm transition-colors hover:bg-accent hover:text-accent-foreground"
            >
                <UserInfo user={auth.user} showEmail />
            </Link>

            <Link
                href={logout()}
                as="button"
                onClick={handleLogout}
                data-test="logout-button"
                className="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-foreground/70 transition-colors hover:bg-destructive/10 hover:text-destructive"
            >
                <LogOut className="size-4 shrink-0" />
                <span>Log out</span>
            </Link>
        </div>
    );
}
