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
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [
    laravel({ input: ['resources/css/app.css', 'resources/js/app.ts'], refresh: true }),
    tailwindcss(),
    vue(),
  ],
  resolve: { alias: { '@': '/resources/js' } },
});
```

`resources/css/app.css`
```css
@import "tailwindcss";
@import "./globals.css";              /* design tokens: var(--token) — NUNCA hex hardcodeado */

/* unstyled mode no tiene darkModeSelector de PrimeVue: el dark var se enlaza a la clase .dark */
@custom-variant dark (&:is(.dark *));

@source "../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php";
@source "../**/*.blade.php";
@source "../**/*.vue";
```
> Tailwind v4 NO usa `tailwind.config.js` ni `postcss.config.js`. Tema = solo la clase `.dark` en `<html>` (light = ausencia de clase). Nunca `data-theme`, nunca `.light`.

---

## 3. TypeScript

`tsconfig.json`
```json
{
  "compilerOptions": {
    "target": "ES2022",
    "lib": ["ES2022", "DOM", "DOM.Iterable"],
    "module": "ESNext",
    "moduleResolution": "bundler",
    "jsx": "preserve",
    "strict": true,
    "noEmit": true,
    "isolatedModules": true,
    "esModuleInterop": true,
    "resolveJsonModule": true,
    "types": ["vite/client"],
    "paths": { "@/*": ["./resources/js/*"] }
  },
  "include": ["resources/js/**/*.ts", "resources/js/**/*.vue", "resources/js/**/*.d.ts"]
}
```
> Type-check con **`vue-tsc --noEmit`** (no `tsc` a secas: necesita entender SFCs `.vue`). `strict: true` obligatorio en TODO `.vue`/`.ts`. Prohibido `any` y `@ts-ignore`.

---

## 4. Inertia 3 + Vue 3.5

`bootstrap/app.php`
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\HandleInertiaRequests::class,
    ]);
})
```

`resources/js/app.ts`
```ts
import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import { createPinia } from 'pinia';
import { PiniaColada } from '@pinia/colada';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import '../css/app.css';
import './echo';

createInertiaApp({
  // 'pages' shorthand del plugin @inertiajs/vite (v3) evita el glob manual:
  resolve: name => {
    const pages = import.meta.glob<{ default: unknown }>('./Pages/**/*.vue', { eager: true });
    return pages[`./Pages/${name}.vue`];
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(createPinia())
      .use(PiniaColada)
      .use(PrimeVue, { unstyled: true }) // sin theme preset: estilos vía Volt + Tailwind v4
      .use(ToastService)
      .mount(el);
  },
});
```

Página tipada (`<script setup lang="ts">`) + layout + Pinia Colada
```vue
<script setup lang="ts">
import { useQuery } from '@pinia/colada';
import AppLayout from '@/common/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

interface Props {
  user: { id: number; name: string; email: string };
  flash: { message?: string };
}
const props = defineProps<Props>();

// Server-state SIEMPRE vía Pinia Colada (nunca un ref() manual para datos del backend):
const { data: stats, isPending } = useQuery({
  key: () => ['dashboard', 'stats', props.user.id],
  query: () => fetch('/api/dashboard/stats').then(r => r.json()),
});
</script>

<template>
  <h1>Hola, {{ user.name }}</h1>
  <p v-if="isPending">Cargando…</p>
  <pre v-else>{{ stats }}</pre>
</template>
```
> Breaking v3: sin Axios incluido · `Inertia::lazy()` → `Inertia::optional()` · `router.cancel()` → `router.cancelAll()` · SSR automático en modo dev de Vite (sin servidor Node aparte) · el bloque `future` de v2 se elimina del `createInertiaApp`.

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

Escuchar en Vue (`<script setup lang="ts">`)
```vue
<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps<{ orderId: number }>();
const status = ref('pending');
let channel: ReturnType<typeof window.Echo.private> | undefined;

onMounted(() => {
  channel = window.Echo.private(`orders.${props.orderId}`)
    .listen('.order.updated', (e: { order: { status: string } }) => {
      status.value = e.order.status;
    });
});
onUnmounted(() => channel?.stopListening('.order.updated'));
</script>
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

## 24. PrimeVue v4 unstyled + Volt (UI)

PrimeVue se registra `{ unstyled: true }` (sin theme preset). Los componentes se traen con **Volt** (code-ownership: el código vive en tu repo bajo `resources/js/volt/`).

```bash
./vendor/bin/sail npx volt-vue add button card dialog inputtext datatable toast
```
```vue
<script setup lang="ts">
import Button from '@/volt/Button.vue';
import Card from '@/volt/Card.vue';
import { useToast } from 'primevue/usetoast';

const toast = useToast();
const notify = () =>
  toast.add({ severity: 'success', summary: 'Listo', detail: 'Guardado', life: 3000 });
</script>

<template>
  <Card>
    <template #content>
      <Button label="Guardar" icon="pi pi-check" @click="notify" />
    </template>
  </Card>
</template>
```
> DataTable = PrimeVue `DataTable` server-side (`:lazy`). Toasts = `Toast` + `useToast()`. Formularios = `@primevue/forms` + **Zod v4**. Iconos = `primeicons` (`pi pi-*`). Helper de clases: `cn()` con `tailwind-merge` + `clsx` en `resources/js/lib/`.

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
- ✅ `tsconfig.json` - TypeScript strict + vue-tsc
- ✅ `vite.config.ts` - Plugin Vue + input `app.ts`
- ✅ `resources/css/app.css` - Tailwind v4 CSS-based + `.dark` variant + tokens
- ✅ `resources/js/app.ts` - Inertia v3 + Vue 3.5 + Pinia + Pinia Colada + PrimeVue unstyled
- ✅ `resources/js/echo.ts` - Reverb/Echo configurado
- ✅ `resources/js/Pages/Welcome.vue` - Página de ejemplo (`<script setup lang="ts">`)
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
- ❌ `resources/js/volt/` - Componentes Volt (PrimeVue unstyled) por añadir vía `npx volt-vue add`
- ❌ `app/Agents/` - Crear agentes AI SDK
- ❌ `app/Data/` - DTOs con Spatie Data
- ❌ Migración `google2fa_secret` en users
- ❌ Modelos con Spatie traits (MediaLibrary, ActivityLog, etc)
