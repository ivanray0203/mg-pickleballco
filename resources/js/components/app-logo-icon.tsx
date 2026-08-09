import type { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(props: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            src="/storage/images/logo.png"
            alt="Skye Paddle Pickleball Courts"
            {...props}
            className={`object-contain ${props.className ?? ''}`}
        />
    );
}
