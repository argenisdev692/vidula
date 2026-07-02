import * as React from 'react';

interface GuestLayoutProps {
    children: React.ReactNode;
}

export default function GuestLayout({ children }: GuestLayoutProps): React.JSX.Element {
    return (
        <div className="flex min-h-screen items-center justify-center bg-background px-4">
            <div className="w-full max-w-sm">{children}</div>
        </div>
    );
}
