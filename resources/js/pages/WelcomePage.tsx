import { Head, Link, router, usePage } from '@inertiajs/react';
import * as React from 'react';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { SharedProps } from '@/types/inertia';

export default function WelcomePage(): React.JSX.Element {
    const { auth } = usePage<SharedProps>().props;

    function logout(): void {
        router.post('/logout');
    }

    return (
        <>
            <Head title="Bienvenido" />
            <div className="flex min-h-screen flex-col bg-background">
                <header className="border-b border-border">
                    <nav
                        className="mx-auto flex h-14 w-full max-w-5xl items-center justify-between px-4"
                        aria-label="Principal"
                    >
                        <div className="flex items-center gap-6">
                            <Link href="/" className="text-sm font-semibold tracking-wide">
                                VIDULA
                            </Link>
                            <Link
                                href="/"
                                className="text-sm text-muted-foreground transition-colors hover:text-foreground"
                            >
                                Inicio
                            </Link>
                        </div>

                        <div className="flex items-center gap-3">
                            {auth.user ? (
                                <>
                                    <span className="hidden text-sm text-muted-foreground sm:inline">
                                        {auth.user.name}
                                    </span>
                                    <Button variant="outline" size="sm" onClick={logout}>
                                        Cerrar sesión
                                    </Button>
                                </>
                            ) : (
                                <Button size="sm" render={<Link href="/login" />}>
                                    Iniciar sesión
                                </Button>
                            )}
                        </div>
                    </nav>
                </header>

                <main className="mx-auto flex w-full max-w-5xl flex-1 items-center px-4">
                    <Card className="w-full">
                        <CardHeader>
                            <CardTitle>Bienvenido a Vidula</CardTitle>
                            <CardDescription>
                                {auth.user
                                    ? `Sesión iniciada como ${auth.user.email}`
                                    : 'Inicia sesión para comenzar.'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm text-muted-foreground">
                                Laravel 13 · Inertia 3 · React 19 · Fortify · shadcn/ui
                            </p>
                        </CardContent>
                    </Card>
                </main>
            </div>
        </>
    );
}
