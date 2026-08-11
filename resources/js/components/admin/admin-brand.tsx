import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { dashboard } from '@/routes/admin';

/**
 * Brand mark shown at the top of the admin sidebar and mobile drawer.
 */
export function AdminBrand() {
    return (
        <Link
            href={dashboard()}
            prefetch
            className="flex items-center gap-3 px-2 py-1"
        >
            <span className="flex aspect-square size-9 items-center justify-center overflow-hidden rounded-lg border border-border bg-white">
                <AppLogoIcon className="size-full" />
            </span>
            <span className="grid text-left leading-tight">
                <span className="truncate text-sm font-semibold">
                    Pickleball
                </span>
                <span className="truncate text-xs text-muted-foreground">
                    Admin Panel
                </span>
            </span>
        </Link>
    );
}
