import { Link } from '@inertiajs/react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import type { NavItem } from '@/types';

type AdminNavProps = {
    items: NavItem[];
    onNavigate?: () => void;
};

/**
 * Renders the admin navigation list. Reused by both the desktop sidebar and
 * the mobile drawer so the two never drift apart.
 */
export function AdminNav({ items, onNavigate }: AdminNavProps) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <nav className="flex flex-col gap-1" aria-label="Admin">
            {items.map((item) => {
                const isActive = isCurrentOrParentUrl(item.href);

                return (
                    <Link
                        key={item.title}
                        href={item.href}
                        prefetch
                        onClick={onNavigate}
                        aria-current={isActive ? 'page' : undefined}
                        className={cn(
                            'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                            'focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                            isActive
                                ? 'bg-primary text-primary-foreground shadow-sm'
                                : 'text-foreground/70 hover:bg-accent hover:text-accent-foreground',
                        )}
                    >
                        {item.icon && <item.icon className="size-4 shrink-0" />}
                        <span className="truncate">{item.title}</span>
                    </Link>
                );
            })}
        </nav>
    );
}
