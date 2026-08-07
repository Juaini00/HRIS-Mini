import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 32 32"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
        >
            <circle cx="16" cy="11" r="5.5" />
            <path d="M6 27c0-5.523 4.477-10 10-10s10 4.477 10 10a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1Z" />
        </svg>
    );
}
