# Laravel 13 — Stack 2026 · Configuraciones

Sintaxis de configuración tras instalar + `vendor:publish`. Cada sección indica el **archivo** y el **código**.

---

## 1. Sail — cambiar a PHP 8.5

`docker-compose.yml`
```yaml
laravel.test:
  build:
    context: ./vendor/laravel/sail/runtimes/8.5   # 8.4 por defecto
    dockerfile: Dockerfile
    args:
      WWWGROUP: '${WWWGROUP}'
  image: sail-8.5/app
```
```bash
./vendor/bin/sail build --no-cache && ./vendor/bin/sail up -d
```

---

## 2. Vite + Tailwind v4

`vite.config.ts`
```ts
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [
    laravel({ input: ['resources/css/app.css', 'resources/js/app.tsx'], refresh: true }),
    tailwindcss(),
    react(),
  ],
  resolve: { alias: { '@': '/resources/js' } },
});
```

`resources/css/app.css`
```css
@import "tailwindcss";
@import "./globals.css";              /* design tokens: var(--token) — NUNCA hex hardcodeado */

/* Tema = solo la clase .dark en <html> (light = ausencia de clase). El dark: variant se enlaza a .dark */
@custom-variant dark (&:is(.dark *));

@source "../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php";
@source "../**/*.blade.php";
@source "../**/*.tsx";
```
> Tailwind v4 NO usa `tailwind.config.js` ni `postcss.config.js`. Tema = solo la clase `.dark` en `<html>` (light = ausencia de clase). Nunca `data-theme`, nunca `.light`.

---

## 3. TypeScript 6

`tsconfig.json`
```json
{
  "compilerOptions": {
    "target": "ES2022",
    "lib": ["ES2022", "DOM", "DOM.Iterable"],
    "module": "ESNext",
    "moduleResolution": "bundler",
    "jsx": "react-jsx",
    "strict": true,
    "noEmit": true,
    "isolatedModules": true,
    "esModuleInterop": true,
    "resolveJsonModule": true,
    "types": ["vite/client"],
    "paths": { "@/*": ["./resources/js/*"] }
  },
  "include": ["resources/js/**/*.ts", "resources/js/**/*.tsx", "resources/js/**/*.d.ts"]
}
```
> **TypeScript 6.0** (GA 23-mar-2026) — última release del compilador JS antes del port nativo en Go (TS 7). Enciende `strict`/ESM/`es2025` por defecto. Type-check con **`tsc --noEmit`** (o `tsc -b`). `strict: true` obligatorio en TODO `.tsx`/`.ts`. Prohibido `any` y `@ts-ignore`.

---

## 4. Inertia 3 + React 19

`bootstrap/app.php`
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\HandleInertiaRequests::class,
    ]);
})
```

`resources/js/app.tsx`
```tsx
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { StrictMode } from 'react';
import '../css/app.css';
import './echo';

const queryClient = new QueryClient({
  defaultOptions: { queries: { staleTime: 1000 * 60 * 2 } },
});

createInertiaApp({
  // 'pages' shorthand del plugin @inertiajs/vite (v3) evita el glob manual:
  resolve: name => {
    const pages = import.meta.glob<{ default: unknown }>('./pages/**/*.tsx', { eager: true });
    return pages[`./pages/${name}.tsx`];
  },
  setup({ el, App, props }) {
    createRoot(el).render(
      <StrictMode>
        <QueryClientProvider client={queryClient}>
          <App {...props} />
        </QueryClientProvider>
      </StrictMode>,
    );
  },
});
```

Página tipada (`.tsx`) + layout + TanStack Query
```tsx
import { Head, usePage } from '@inertiajs/react';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import AppLayout from '@/pages/layouts/AppLayout';

interface Props {
  user: { id: number; name: string; email: string };
  flash: { message?: string };
}

export default function Dashboard(): React.JSX.Element {
  const { user } = usePage<Props>().props;

  // Server-state SIEMPRE vía TanStack Query (nunca un useState manual para datos del backend):
  const { data: stats, isPending } = useQuery({
    queryKey: ['dashboard', 'stats', user.id],
    queryFn: async () => (await axios.get('/dashboard/data/stats')).data,
  });

  return (
    <>
      <Head title="Dashboard" />
      <AppLayout>
        <h1>Hola, {user.name}</h1>
        {isPending ? <p>Cargando…</p> : <pre>{JSON.stringify(stats, null, 2)}</pre>}
      </AppLayout>
    </>
  );
}
```
> Breaking v3: sin Axios incluido (instálalo como peer dep para TanStack Query, o usa `useHttp`) · `Inertia::lazy()` → `Inertia::optional()` · `router.cancel()` → `router.cancelAll()` · SSR automático en modo dev de Vite (sin servidor Node aparte) · el bloque `future` de v2 se elimina del `createInertiaApp`. Novedades v3.1–3.3: `usePoll`/`router.poll`, `<InfiniteScroll>`, evento `httpException`.

---

## 5. Reverb (WebSockets)

`.env`
```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=my-app-id
REVERB_APP_KEY=my-app-key
REVERB_APP_SECRET=my-app-secret
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

`resources/js/echo.ts`
```ts
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;
window.Echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: import.meta.env.VITE_REVERB_PORT,
  wssPort: import.meta.env.VITE_REVERB_PORT,
  forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
  enabledTransports: ['ws', 'wss'],
});
```

Evento broadcast `app/Events/OrderStatusUpdated.php`
```php
class OrderStatusUpdated implements ShouldBroadcast {
    use InteractsWithSockets;
    public function __construct(public readonly Order $order) {}
    public function broadcastOn(): array {
        return [new PrivateChannel('orders.' . $this->order->id)];
    }
    public function broadcastAs(): string { return 'order.updated'; }
}
// Disparar:
broadcast(new OrderStatusUpdated($order))->toOthers();
```

`routes/channels.php`
```php
Broadcast::channel('orders.{orderId}', function (User $user, int $orderId) {
    return Order::where('id', $orderId)->where('user_id', $user->id)->exists();
});
```

Escuchar en React (`.tsx`)
```tsx
import { useEffect, useState } from 'react';

export function useOrderStatus(orderId: number): string {
  const [status, setStatus] = useState('pending');

  useEffect(() => {
    const channel = window.Echo.private(`orders.${orderId}`)
      .listen('.order.updated', (e: { order: { status: string } }) => {
        setStatus(e.order.status);
      });
    return () => channel.stopListening('.order.updated');
  }, [orderId]);

  return status;
}
```

Producción — `/etc/supervisor/conf.d/reverb.conf` + Nginx
```ini
[program:reverb]
command=/usr/bin/php /var/www/app/artisan reverb:start
autostart=true
autorestart=true
```
```nginx
location /app {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
}
```
> Escalar multi-servidor: `REVERB_SCALING_ENABLED=true` + Redis.

---

## 6. Resend (email)

`.env`
```env
RESEND_API_KEY=re_xxxxxxxxxxxxxxxxxxxx
MAIL_MAILER=resend
MAIL_FROM_ADDRESS=hola@tudominio.com
MAIL_FROM_NAME="Tu App"
RESEND_WEBHOOK_SECRET=whsec_xxxxxxxxxx
```

`config/mail.php`
```php
'resend' => ['transport' => 'resend'],
```

`config/services.php`
```php
'resend' => ['key' => env('RESEND_API_KEY')],
```
> Con `MAIL_MAILER=resend`, Fortify/OTP/notificaciones salen por Resend sin cambios.

---

## 7. Fortify (auth headless)

`bootstrap/providers.php`
```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\FortifyServiceProvider::class, // ← agregar
];
```

`config/fortify.php`
```php
'features' => [
    Features::registration(),
    Features::resetPasswords(),
    Features::emailVerification(),
    Features::updateProfileInformation(),
    Features::updatePasswords(),
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]),
],
```

`app/Providers/FortifyServiceProvider.php`
```php
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LoginResponse;

public function boot(): void {
    $this->app->instance(LoginResponse::class, new class implements LoginResponse {
        public function toResponse($request) {
            return $request->wantsJson()
                ? response()->json(['redirect' => '/dashboard'])
                : redirect()->intended('/dashboard');
        }
    });

    Fortify::authenticateUsing(function (Request $request) {
        $user = \App\Models\User::where('email', $request->email)->first();
        if ($user && \Hash::check($request->password, $user->password)) {
            return $user;
        }
    });
}
```
> Verificar rutas: `./vendor/bin/sail artisan route:list | grep -E "login|register|password|two-factor"`

---

## 8. Spatie OTP (one-time-passwords)

`config/one-time-passwords.php`
```php
return [
    'expires_in_seconds' => 120, // 2 min
    'length' => 6,
    'max_attempts' => 3,
];
```

`app/Models/User.php`
```php
use Spatie\OneTimePasswords\Models\Concerns\HasOneTimePasswords;

class User extends Authenticatable {
    use Notifiable, HasOneTimePasswords;
}
```

Flujo passwordless
```php
// Solicitar
$user->sendOneTimePassword();

// Verificar y loguear
$result = $user->attemptLoginUsingOneTimePassword($request->code);
if ($result->isOk()) {
    $request->session()->regenerate();
    return response()->json(['redirect' => '/dashboard']);
}
return response()->json(['errors' => ['code' => $result->validationMessage()]], 422);
```

Prune `routes/console.php`
```php
use Spatie\OneTimePasswords\Models\OneTimePassword;
Schedule::command('model:prune', ['--model' => [OneTimePassword::class]])->daily();
```

---

## 9. Google2FA (TOTP / Google Authenticator)

Migración — columna secret
```php
$table->text('google2fa_secret')->nullable();
```

Activar 2FA — generar secret + QR
```php
use PragmaRX\Google2FALaravel\Facade as Google2FA;

$secret = Google2FA::generateSecretKey(); // 32 chars (v9+)
$user->forceFill(['google2fa_secret' => $secret])->save();

$qrUrl = Google2FA::getQRCodeUrl(config('app.name'), $user->email, $secret);
```

Verificar código
```php
$valid = Google2FA::verifyKey($user->google2fa_secret, $request->input('otp'));
```

Middleware `bootstrap/app.php`
```php
$middleware->alias([
    '2fa' => PragmaRX\Google2FALaravel\Middleware::class,
]);
// routes/web.php
Route::middleware(['auth', '2fa'])->group(function () {
    Route::get('/panel', PanelController::class);
});
```

---

## 10. Sanctum (API tokens)

`app/Models/User.php`
```php
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable { use HasApiTokens; }

// Crear token:
$token = $user->createToken('mobile-app', ['read'])->plainTextToken;
```

---

## 11. Socialite (OAuth)

`config/services.php`
```php
'google' => [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect'      => env('GOOGLE_REDIRECT_URI'),
],
```

---

## 12. S3 / Flysystem

`config/filesystems.php`
```php
's3' => [
    'driver' => 's3',
    'key'    => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
],
```

---

## 13. Spatie Permission

`app/Models/User.php`
```php
use Spatie\Permission\Traits\HasRoles;
class User extends Authenticatable { use HasRoles; }
```

---

## 14. Spatie Laravel Data (DTO)

`app/Data/UserData.php`
```php
use Spatie\LaravelData\Data;

class UserData extends Data {
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}
// $data = UserData::from($request);
// $data = UserData::from(User::find(1));
```

---

## 15. Spatie MediaLibrary

`app/Models/Product.php`
```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia {
    use InteractsWithMedia;
}
// $product->addMedia($request->file('image'))->toMediaCollection('images');
```

---

## 16. Spatie ActivityLog

`app/Models/Order.php`
```php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Order extends Model {
    use LogsActivity;
    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
            ->logOnly(['status', 'total'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
```

---

## 17. Spatie Backup — scheduler

`routes/console.php`
```php
Schedule::command('backup:run')->daily()->at('02:00');
Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:monitor')->daily()->at('03:00');
```

---

## 18. Honeypot (anti-spam)

```php
use Spatie\Honeypot\ProtectAgainstSpam;
Route::post('/register', RegisterController::class)
    ->middleware(ProtectAgainstSpam::class);
```

---

## 19. Telescope / Horizon — restringir acceso

`app/Providers/TelescopeServiceProvider.php` / `AppServiceProvider`
```php
Gate::define('viewTelescope', fn ($user) => in_array($user->email, ['admin@tuapp.com']));
Gate::define('viewHorizon',  fn ($user) => in_array($user->email, ['admin@tuapp.com']));
```

---

## 20. DomPDF

`config/dompdf.php`
```php
'enable_remote' => true, // solo si usas imágenes/CSS remotos (default false en v3)
```
```php
use Barryvdh\DomPDF\Facade\Pdf;
$pdf = Pdf::loadView('pdf.invoice', $data);
return $pdf->download('factura.pdf');
```

---

## 21. Scribe (API docs)

`config/scribe.php`
```php
'type' => 'laravel',         // o 'static'
'routes' => [[
    'match' => ['prefixes' => ['api/*']],
]],
'openapi' => ['enabled' => true],
'postman' => ['enabled' => true],
```
```bash
./vendor/bin/sail artisan scribe:generate   # docs en /docs
```

---

## 22. IDE Helper — automatizar

`composer.json`
```json
"scripts": {
  "post-update-cmd": [
    "@php artisan ide-helper:generate",
    "@php artisan ide-helper:models -N"
  ]
}
```

---

## 23. Calidad — Pint · Rector · Larastan

`pint.json`
```json
{ "preset": "laravel", "rules": { "declare_strict_types": true } }
```

`rector.php`
```php
use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([__DIR__.'/app', __DIR__.'/database'])
    ->withSets([LaravelSetList::LARAVEL_130])
    ->withPhpSets();
```

`phpstan.neon`
```neon
includes:
    - vendor/larastan/larastan/extension.neon
parameters:
    paths: [app, database, routes]
    level: 6
```

`composer.json` — un solo comando
```json
"scripts": {
  "review": ["pint --test", "rector process --dry-run", "phpstan analyse", "@php artisan test"],
  "fix":    ["pint", "rector process"]
}
```
> ⚠️ Se usa `@php artisan test` (PHPUnit 12) en vez de `pest`: Pest aún no soporta Laravel 13 (`pest-plugin-laravel` máx. ^12, choca con `phpunit ^12` y `laravel/pail`).
```bash
./vendor/bin/sail composer review   # verifica todo
./vendor/bin/sail composer fix      # formatea + refactoriza
```

---

## 24. shadcn/ui + TanStack Table + Zustand (UI)

UI primitives vía **shadcn/ui** (CLI-generated, code-ownership en `resources/js/shadcn/` — NUNCA se editan a mano, se regeneran). Es la ÚNICA librería UI del stack (no PrimeVue, no MUI).

```bash
./vendor/bin/sail npx shadcn@latest init
./vendor/bin/sail npx shadcn@latest add button card dialog input table
```
```tsx
import { Button } from '@/shadcn/button';
import { Card, CardContent } from '@/shadcn/card';
import { toast } from 'sileo';

export default function SaveCard(): React.JSX.Element {
  return (
    <Card>
      <CardContent>
        <Button onClick={() => toast.success('Guardado')}>Guardar</Button>
      </CardContent>
    </Card>
  );
}
```
> **DataTable = TanStack Table v8** (`@tanstack/react-table` con `useReactTable`), renderizada con las primitivas `Table` de shadcn — NUNCA el `data-table` de shadcn. Listados server-side vía TanStack Query (`:lazy`-equivalente = filtros en el `queryKey`). Toasts = **Sileo** (`toast.success/error`). Formularios = `react-hook-form` + **Zod v4** (`@hookform/resolvers`). Iconos = `lucide-react`. Animaciones = Framer Motion. Helper de clases: `cn()` con `tailwind-merge` + `clsx` en `resources/js/common/helpers/`.

**Estado de servidor vs cliente**
```tsx
// Server-state → TanStack Query v5
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';

// Client-state → Zustand v5 (stores en resources/js/modules/{context}/stores/)
import { create } from 'zustand';

type UiStore = { isFiltersOpen: boolean; setFiltersOpen: (v: boolean) => void };
export const useUiStore = create<UiStore>((set) => ({
  isFiltersOpen: false,
  setFiltersOpen: (v) => set({ isFiltersOpen: v }),
}));
```
> Nunca mezclar las dos capas: datos del backend SIEMPRE en TanStack Query; estado de UI compartido en Zustand. `persist` de Zustand solo para preferencias no sensibles (tema, sidebar).

---

## 25. AI SDK (laravel/ai)

`.env`
```env
OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...
GEMINI_API_KEY=AIza...
```

Agente `app/Agents/SupportAgent.php`
```php
use Laravel\Ai\Agent;
use Laravel\Ai\Concerns\RemembersConversations;

class SupportAgent extends Agent {
    use RemembersConversations;
    public string $model = 'gpt-4o';
    public function instructions(): string { return 'Responde dudas de soporte.'; }
}
// $result = SupportAgent::make()->prompt('hola');
```
> `laravel/ai` es oficial y usa `prism-php/prism` por debajo (no se instala aparte). Vector search requiere PostgreSQL + pgvector.

---

## Estado de Configuraciones - Proyecto VIDULA

### ✅ Completadas (2026-05-30)

**Core Stack:**
- ✅ `tsconfig.json` - TypeScript 6 strict + `tsc --noEmit`
- ✅ `vite.config.ts` - Plugin React + input `app.tsx`
- ✅ `resources/css/app.css` - Tailwind v4 CSS-based + `.dark` variant + tokens
- ✅ `resources/js/app.tsx` - Inertia v3 + React 19 + TanStack Query + Zustand + shadcn/ui
- ✅ `resources/js/echo.ts` - Reverb/Echo configurado
- ✅ `resources/js/pages/Welcome.tsx` - Página de ejemplo (`export default` + `<Head />`)
- ✅ `bootstrap/app.php` - HandleInertiaRequests middleware

**Backend Configs:**
- ✅ `config/inertia.php` - SSR habilitado
- ✅ `config/reverb.php` - WebSockets completo
- ✅ `config/horizon.php` - Queue workers
- ✅ `config/ai.php` - Todos los providers
- ✅ `config/fortify.php` - Features activados
- ✅ `config/sanctum.php` - API tokens
- ✅ `config/services.php` - Resend + Socialite
- ✅ `config/filesystems.php` - S3 configurado

**User Model:**
- ✅ `app/Models/User.php` - HasApiTokens, HasRoles, HasOneTimePasswords

**Providers:**
- ✅ `app/Providers/FortifyServiceProvider.php` - LoginResponse SPA

**Calidad de Código:**
- ✅ `pint.json` - Laravel preset + strict types
- ✅ `rector.php` - Laravel 13 set
- ✅ `phpstan.neon` - Larastan level 6

**Environment:**
- ✅ `.env.example` - Todas las variables agregadas (Reverb, AI SDK, Resend, etc)

### ⏳ Pendientes

- ✅ `compose.yaml` - Todos los servicios agregados (mysql, redis, meilisearch, minio, mailpit) + puerto 8080 para Reverb
- ❌ `routes/channels.php` - Configurar canales broadcast
- ✅ `app/Providers/TelescopeServiceProvider.php` - Gate existe pero email vacío (línea 60-62)
- ✅ `routes/console.php` - Scheduler configurado (backup, clean, monitor, prune OTP)
- ❌ `resources/js/shadcn/` - Componentes shadcn/ui por añadir vía `npx shadcn@latest add`
- ❌ `app/Agents/` - Crear agentes AI SDK
- ❌ `app/Data/` - DTOs con Spatie Data
- ❌ Migración `google2fa_secret` en users
- ❌ Modelos con Spatie traits (MediaLibrary, ActivityLog, etc)
