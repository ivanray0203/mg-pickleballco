import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { PaginationMeta } from '@/types';

type PaginationProps = {
    meta: PaginationMeta;
};

/**
 * Renders Laravel paginator links. Hidden when there is only a single page.
 */
export function Pagination({ meta }: PaginationProps) {
    if (meta.last_page <= 1) {
        return null;
    }

    return (
        <nav
            className="flex flex-col items-center justify-between gap-3 sm:flex-row"
            aria-label="Pagination"
        >
            <p className="text-sm text-muted-foreground">
                Showing {meta.from ?? 0}–{meta.to ?? 0} of {meta.total}
            </p>

            <div className="flex flex-wrap items-center gap-1">
                {meta.links.map((link, index) =>
                    link.url ? (
                        <Link
                            key={index}
                            href={link.url}
                            preserveScroll
                            preserveState
                            className={cn(
                                'inline-flex h-9 min-w-9 items-center justify-center rounded-md px-3 text-sm transition-colors',
                                link.active
                                    ? 'bg-primary text-primary-foreground'
                                    : 'text-foreground hover:bg-accent hover:text-accent-foreground',
                            )}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ) : (
                        <span
                            key={index}
                            className="inline-flex h-9 min-w-9 items-center justify-center rounded-md px-3 text-sm text-muted-foreground opacity-50"
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ),
                )}
            </div>
        </nav>
    );
}
