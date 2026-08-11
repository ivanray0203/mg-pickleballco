/**
 * Bridge between the framed PayMongo checkout and the page hosting it.
 *
 * The provider's checkout runs cross-origin, so the parent cannot observe it.
 * What it *can* observe is the end: PayMongo sends the frame to our own
 * success/cancel URL, and that page — being same-origin again — posts a message
 * up so the host can close the modal and take over.
 */
export const CHECKOUT_MESSAGE_SOURCE = 'pickleball:checkout';

export type CheckoutOutcome = 'success' | 'cancelled';

export type CheckoutMessage = {
    source: typeof CHECKOUT_MESSAGE_SOURCE;
    outcome: CheckoutOutcome;
};

/** True when the current document is running inside a frame we opened. */
export function isEmbedded(): boolean {
    try {
        return window.self !== window.top;
    } catch {
        // A cross-origin parent throws on access, which still means embedded.
        return true;
    }
}

/**
 * Tell the host page the checkout finished. No-op when not embedded, so the
 * landing pages can call this unconditionally.
 */
export function notifyCheckoutOutcome(outcome: CheckoutOutcome): void {
    if (!isEmbedded()) {
        return;
    }

    const message: CheckoutMessage = {
        source: CHECKOUT_MESSAGE_SOURCE,
        outcome,
    };

    // Same-origin only — never broadcast booking activity to another site.
    window.parent.postMessage(message, window.location.origin);
}

/**
 * Listen for the frame reporting its outcome. Returns an unsubscribe function.
 */
export function onCheckoutOutcome(
    handler: (outcome: CheckoutOutcome) => void,
): () => void {
    const listener = (event: MessageEvent) => {
        if (event.origin !== window.location.origin) {
            return;
        }

        const data = event.data as CheckoutMessage | undefined;

        if (data?.source !== CHECKOUT_MESSAGE_SOURCE) {
            return;
        }

        handler(data.outcome);
    };

    window.addEventListener('message', listener);

    return () => window.removeEventListener('message', listener);
}
