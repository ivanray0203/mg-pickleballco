import AdminLayoutTemplate from '@/layouts/admin/admin-sidebar-layout';
import type { AdminLayoutProps } from '@/types';

export default function AdminLayout({ children, title }: AdminLayoutProps) {
    return <AdminLayoutTemplate title={title}>{children}</AdminLayoutTemplate>;
}
