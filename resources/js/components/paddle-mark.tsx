/** Inline pickleball paddle mark used as the brand logo. */
export default function PaddleMark({ className }: { className?: string }) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            className={className}
            aria-hidden="true"
        >
            {/* Paddle face */}
            <path
                d="M12 1.8c4.3 0 7.4 3.2 7.4 7.3 0 3.9-2.7 6.7-6 7.2v3.1a1.4 1.4 0 0 1-2.8 0v-3.1c-3.3-.5-6-3.3-6-7.2C4.6 5 7.7 1.8 12 1.8Z"
                fill="currentColor"
                opacity="0.15"
            />
            <path
                d="M12 1.8c4.3 0 7.4 3.2 7.4 7.3 0 3.9-2.7 6.7-6 7.2v3.1a1.4 1.4 0 0 1-2.8 0v-3.1c-3.3-.5-6-3.3-6-7.2C4.6 5 7.7 1.8 12 1.8Z"
                stroke="currentColor"
                strokeWidth="1.6"
                strokeLinejoin="round"
            />
            {/* Holes */}
            <circle cx="9.6" cy="7.6" r="1" fill="currentColor" />
            <circle cx="14.4" cy="7.6" r="1" fill="currentColor" />
            <circle cx="12" cy="10.4" r="1" fill="currentColor" />
        </svg>
    );
}
