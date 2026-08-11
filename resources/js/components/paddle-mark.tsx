/** Inline paddle-and-ball mark used as the brand logo. */
export default function PaddleMark({ className }: { className?: string }) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            className={className}
            aria-hidden="true"
        >
            {/* Paddle, tucked up and to the right to make room for the ball. */}
            <g transform="translate(2.6 -1.6) scale(0.86)">
                <path
                    d="M12 1.8c4.3 0 7.4 3.2 7.4 7.3 0 3.9-2.7 6.7-6 7.2v3.1a1.4 1.4 0 0 1-2.8 0v-3.1c-3.3-.5-6-3.3-6-7.2C4.6 5 7.7 1.8 12 1.8Z"
                    fill="currentColor"
                    opacity="0.15"
                />
                <path
                    d="M12 1.8c4.3 0 7.4 3.2 7.4 7.3 0 3.9-2.7 6.7-6 7.2v3.1a1.4 1.4 0 0 1-2.8 0v-3.1c-3.3-.5-6-3.3-6-7.2C4.6 5 7.7 1.8 12 1.8Z"
                    stroke="currentColor"
                    strokeWidth="1.8"
                    strokeLinejoin="round"
                />
                {/* Holes in the paddle face */}
                <circle cx="9.6" cy="7.6" r="1" fill="currentColor" />
                <circle cx="14.4" cy="7.6" r="1" fill="currentColor" />
                <circle cx="12" cy="10.4" r="1" fill="currentColor" />
            </g>

            {/* Ball resting at the paddle's heel. */}
            <circle
                cx="5.6"
                cy="18.2"
                r="3.4"
                fill="currentColor"
                opacity="0.15"
            />
            <circle
                cx="5.6"
                cy="18.2"
                r="3.4"
                stroke="currentColor"
                strokeWidth="1.6"
            />
            <circle cx="4.5" cy="17.2" r="0.7" fill="currentColor" />
            <circle cx="6.9" cy="18.9" r="0.7" fill="currentColor" />
        </svg>
    );
}
