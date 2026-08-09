import { useEffect, useState } from 'react';

type HoldCountdownProps = {
    /** Authoritative server expiry (ISO 8601). */
    expiresAt: string;
    /** Called once the countdown reaches zero. */
    onExpire: () => void;
};

function remainingSeconds(expiresAt: string): number {
    return Math.max(
        0,
        Math.floor((new Date(expiresAt).getTime() - Date.now()) / 1000),
    );
}

/**
 * UX-only countdown to the hold's expiry. The server expiry remains
 * authoritative — this simply reflects it and notifies the parent at zero.
 */
export function HoldCountdown({ expiresAt, onExpire }: HoldCountdownProps) {
    const [seconds, setSeconds] = useState(() => remainingSeconds(expiresAt));

    useEffect(() => {
        if (seconds <= 0) {
            onExpire();

            return;
        }

        const timer = setTimeout(() => {
            setSeconds(remainingSeconds(expiresAt));
        }, 1000);

        return () => clearTimeout(timer);
    }, [seconds, expiresAt, onExpire]);

    const minutes = Math.floor(seconds / 60);
    const secs = seconds % 60;

    return (
        <span
            className="font-mono text-2xl font-bold tabular-nums"
            role="timer"
            aria-live="polite"
        >
            {String(minutes).padStart(2, '0')}:{String(secs).padStart(2, '0')}
        </span>
    );
}
