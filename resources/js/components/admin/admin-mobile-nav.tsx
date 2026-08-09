import { Menu } from 'lucide-react';
import { useState } from 'react';
import { AdminBrand } from '@/components/admin/admin-brand';
import { AdminNav } from '@/components/admin/admin-nav';
import { adminNavItems } from '@/components/admin/admin-nav-items';
import { AdminSidebarFooter } from '@/components/admin/admin-sidebar-footer';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';

/**
 * Touch-friendly collapsible drawer navigation for the admin panel on mobile.
 */
export function AdminMobileNav() {
    const [open, setOpen] = useState(false);

    return (
        <Sheet open={open} onOpenChange={setOpen}>
            <SheetTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="lg:hidden"
                    aria-label="Open navigation menu"
                >
                    <Menu className="size-5" />
                </Button>
            </SheetTrigger>
            <SheetContent
                side="left"
                className="flex w-72 flex-col gap-4 bg-sidebar p-4"
            >
                <SheetHeader className="space-y-0 p-0 text-left">
                    <SheetTitle className="sr-only">
                        Admin navigation
                    </SheetTitle>
                    <AdminBrand />
                </SheetHeader>

                <div className="flex-1 overflow-y-auto">
                    <AdminNav
                        items={adminNavItems}
                        onNavigate={() => setOpen(false)}
                    />
                </div>

                <AdminSidebarFooter />
            </SheetContent>
        </Sheet>
    );
}
