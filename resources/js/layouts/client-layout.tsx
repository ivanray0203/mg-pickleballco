import { ClientHeader } from '@/components/client/client-header';

type ClientLayoutProps = {
    children: React.ReactNode;
};

/**
 * Shell for the customer-facing area: a responsive top header (with a mobile
 * drawer) over a centered content column. Built from the shared design tokens
 * and UI primitives — no bespoke copies.
 */
export default function ClientLayout({ children }: ClientLayoutProps) {
    return (
        <div className="min-h-screen bg-muted/30">
            <ClientHeader />
            <main className="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 sm:py-8">
                {children}
            </main>
        </div>
    );
}
