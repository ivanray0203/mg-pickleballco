import { AdminHeader } from '@/components/admin/admin-header';
import { AdminSidebar } from '@/components/admin/admin-sidebar';
import type { AdminLayoutProps } from '@/types';

/**
 * Responsive shell for the admin panel: a fixed sidebar on desktop, a drawer
 * on mobile, and a sticky header carrying the current page title.
 */
export default function AdminSidebarLayout({
    children,
    title = 'Dashboard',
}: AdminLayoutProps) {
    return (
        <div className="flex min-h-screen w-full bg-background text-foreground">
            <AdminSidebar />

            <div className="flex min-w-0 flex-1 flex-col">
                <AdminHeader title={title} />

                <main className="flex-1 p-4 md:p-6">{children}</main>
            </div>
        </div>
    );
}
