import { Head } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';

type ComingSoonProps = {
    title: string;
    description: string;
    icon: LucideIcon;
};

/**
 * Shared placeholder for customer areas whose features arrive in later phases.
 * Keeps navigation functional without stubbing bespoke pages each time.
 */
export function ComingSoon({
    title,
    description,
    icon: Icon,
}: ComingSoonProps) {
    return (
        <>
            <Head title={title} />

            <div className="flex flex-col gap-6">
                <h1 className="text-2xl font-semibold">{title}</h1>

                <Card>
                    <CardContent className="flex flex-col items-center justify-center gap-3 py-16 text-center">
                        <span className="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <Icon className="size-6" />
                        </span>
                        <div className="grid gap-1">
                            <p className="font-medium">Coming soon</p>
                            <p className="max-w-sm text-sm text-muted-foreground">
                                {description}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
