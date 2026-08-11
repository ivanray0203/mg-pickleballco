import { ExternalLink, LoaderCircle } from 'lucide-react';
import { useEffect, useState } from 'react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { onCheckoutOutcome } from '@/lib/embedded-checkout';
import type { CheckoutOutcome } from '@/lib/embedded-checkout';

type CheckoutFrameDialogProps = {
    /** The provider checkout URL, or null while none is open. */
    url: string | null;
    onOutcome: (outcome: CheckoutOutcome) => void;
    onDismiss: () => void;
};

/**
 * Hosts the provider's checkout in an on-site modal.
 *
 * The frame is cross-origin, so we can neither read it nor reliably detect a
 * refused render — a blocked frame looks the same from out here as a slow one.
 * Hence the always-visible "open in a new tab" escape hatch: the customer is
 * never trapped in front of a blank modal with no way to pay.
 */
export function CheckoutFrameDialog({
    url,
    onOutcome,
    onDismiss,
}: CheckoutFrameDialogProps) {
    // Derived rather than reset in an effect, so reopening with a different
    // URL shows the spinner again without a cascading render.
    const [loadedUrl, setLoadedUrl] = useState<string | null>(null);
    const loading = url !== null && loadedUrl !== url;

    useEffect(() => onCheckoutOutcome(onOutcome), [onOutcome]);

    return (
        <Dialog
            open={url !== null}
            onOpenChange={(open) => {
                if (!open) {
                    onDismiss();
                }
            }}
        >
            <DialogContent className="max-w-xl gap-0 p-0 sm:max-w-xl">
                <DialogHeader className="border-b border-border px-5 py-3">
                    <DialogTitle className="text-base">
                        Pay with GCash
                    </DialogTitle>
                    <DialogDescription className="text-xs">
                        Secure checkout, hosted by PayMongo.
                    </DialogDescription>
                </DialogHeader>

                <div className="relative h-[32rem] bg-muted/20">
                    {loading && (
                        <div className="absolute inset-0 flex items-center justify-center">
                            <LoaderCircle className="size-6 animate-spin text-muted-foreground" />
                        </div>
                    )}
                    {url && (
                        <iframe
                            key={url}
                            src={url}
                            title="GCash checkout"
                            className="relative size-full border-0"
                            // Enough for the provider's own scripts and forms,
                            // without granting it our origin.
                            sandbox="allow-scripts allow-forms allow-same-origin allow-popups allow-top-navigation-by-user-activation"
                            onLoad={() => setLoadedUrl(url)}
                        />
                    )}
                </div>

                <div className="border-t border-border px-5 py-3 text-center">
                    {url && (
                        <a
                            href={url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-1.5 text-xs text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                        >
                            <ExternalLink className="size-3.5" />
                            Not loading? Open the checkout in a new tab
                        </a>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
