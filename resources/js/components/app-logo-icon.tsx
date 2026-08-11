import type { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(
    props: ImgHTMLAttributes<HTMLImageElement>,
) {
    return (
        <img
            src="/images/logo-mark.png"
            alt="MG Paddle & Co. Pickleball"
            {...props}
            className={`object-contain ${props.className ?? ''}`}
        />
    );
}
