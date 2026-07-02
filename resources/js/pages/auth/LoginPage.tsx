import { Head, useForm } from '@inertiajs/react';
import * as React from 'react';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import GuestLayout from '@/pages/layouts/GuestLayout';

export default function LoginPage(): React.JSX.Element {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    function submit(event: React.FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        post('/login');
    }

    return (
        <>
            <Head title="Iniciar sesión" />
            <GuestLayout>
                <Card>
                    <CardHeader>
                        <CardTitle>Iniciar sesión</CardTitle>
                        <CardDescription>
                            Accede a tu cuenta de Vidula
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="flex flex-col gap-4" noValidate>
                            <div className="flex flex-col gap-2">
                                <label htmlFor="email" className="text-sm font-medium">
                                    Correo electrónico
                                </label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(event) => setData('email', event.target.value)}
                                    autoComplete="username"
                                    autoFocus
                                    required
                                    aria-invalid={errors.email ? true : undefined}
                                />
                                {errors.email ? (
                                    <p className="text-sm text-destructive" role="alert">
                                        {errors.email}
                                    </p>
                                ) : null}
                            </div>

                            <div className="flex flex-col gap-2">
                                <label htmlFor="password" className="text-sm font-medium">
                                    Contraseña
                                </label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(event) => setData('password', event.target.value)}
                                    autoComplete="current-password"
                                    required
                                    aria-invalid={errors.password ? true : undefined}
                                />
                                {errors.password ? (
                                    <p className="text-sm text-destructive" role="alert">
                                        {errors.password}
                                    </p>
                                ) : null}
                            </div>

                            <label
                                htmlFor="remember"
                                className="flex items-center gap-2 text-sm text-muted-foreground"
                            >
                                <input
                                    id="remember"
                                    type="checkbox"
                                    checked={data.remember}
                                    onChange={(event) => setData('remember', event.target.checked)}
                                    className="size-4 accent-primary"
                                />
                                Recordarme
                            </label>

                            <Button type="submit" disabled={processing} className="w-full">
                                {processing ? 'Entrando…' : 'Entrar'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </GuestLayout>
        </>
    );
}
