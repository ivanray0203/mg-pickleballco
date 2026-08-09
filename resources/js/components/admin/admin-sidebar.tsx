import { AdminBrand } from '@/components/admin/admin-brand';
import { AdminNav } from '@/components/admin/admin-nav';
import { adminNavItems } from '@/components/admin/admin-nav-items';
import { AdminSidebarFooter } from '@/components/admin/admin-sidebar-footer';

/**
 * Fixed desktop sidebar for the admin panel. Hidden on mobile, where the
 * drawer in {@link AdminMobileNav} is used instead.
 */
export function AdminSidebar() {
    return (
        <aside className="hidden w-64 shrink-0 flex-col gap-4 border-r border-sidebar-border bg-sidebar p-4 lg:flex">
            <AdminBrand />

            <div className="flex-1 overflow-y-auto">
                <AdminNav items={adminNavItems} />
            </div>

            <AdminSidebarFooter />
        </aside>
    );
}
