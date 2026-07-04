# Laravel 13 — Stack 2026 · Checklist Composer & Publish

Marca cada caja al verificar/instalar. Todos los comandos asumen Sail.

---

## 0. Crear proyecto

```bash
laravel new myapp --vue
cd myapp && ./vendor/bin/sail up -d
```

> Stack objetivo 2026: **Laravel 13.16+** (PHP 8.3–8.5) · **PHP 8.5.0** (GA 20-nov-2025) · **Vue 3.5.38** · **Inertia v3** · **PrimeVue v4.5 unstyled + Volt** · **Pinia + Pinia Colada** · **Tailwind v4**. NO React, NO shadcn/ui.

---

## 0.1 TODO EN UN SOLO COMANDO — backend (prod) + dev + frontend

Encadena con `&&` los tres bloques. Ejecutar sobre el proyecto ya creado (paso 0).

```bash
./vendor/bin/sail composer require \
  bacon/bacon-qr-code barryvdh/laravel-dompdf inertiajs/inertia-laravel:^3.0 \
  intervention/image knuckleswtf/scribe laravel/ai laravel/fortify laravel/horizon \
  laravel/reverb laravel/sanctum laravel/socialite laravel/telescope \
  "league/flysystem-aws-s3-v3:^3.0" pragmarx/google2fa-laravel ramsey/uuid \
  resend/resend-laravel spatie/laravel-activitylog spatie/laravel-backup \
  spatie/laravel-cookie-consent spatie/laravel-data spatie/laravel-honeypot \
  spatie/laravel-medialibrary spatie/laravel-one-time-passwords \
  spatie/laravel-permission spatie/laravel-sitemap spatie/simple-excel && \
./vendor/bin/sail composer require --dev \
  barryvdh/laravel-debugbar barryvdh/laravel-ide-helper driftingly/rector-laravel \
  larastan/larastan laravel/pint rector/rector && \
./vendor/bin/sail npm install @inertiajs/vue3@^3.0 @inertiajs/vite@^3.0 vue@^3.5 \
  pinia @pinia/colada primevue@^4 @primevue/forms @primevue/themes primeicons zod \
  tailwind-merge clsx laravel-echo pusher-js && \
./vendor/bin/sail npm install -D typescript vue-tsc @vitejs/plugin-vue @types/node \
  tailwindcss @tailwindcss/vite laravel-vite-plugin @pinia/colada-devtools
```

> Server-state → **Pinia Colada** (`useQuery`/`useMutation`). Client-state → **Pinia** setup stores. UI primitives → **PrimeVue v4 unstyled + Volt** (`npx volt-vue add ...`). Forms → `@primevue/forms` + **Zod v4**. Iconos → `primeicons` (`pi pi-*`). `tailwind-merge` + `clsx` alimentan el helper `cn()`.

> ⚠️ Pest **no** se incluye: aún no soporta Laravel 13 (ver §2). Usa PHPUnit 12 (incluido).

---

## 1. Composer require — producción (26)

- [ ] bacon/bacon-qr-code
- [ ] barryvdh/laravel-dompdf
- [ ] inertiajs/inertia-laravel:^3.0
- [ ] intervention/image
- [ ] knuckleswtf/scribe
- [ ] laravel/ai  *(SDK oficial; usa prism-php/prism por debajo, no hace falta instalarlo aparte)*
- [ ] laravel/fortify
- [ ] laravel/horizon
- [ ] laravel/reverb
- [ ] laravel/sanctum
- [ ] laravel/socialite
- [ ] laravel/telescope
- [ ] league/flysystem-aws-s3-v3:^3.0
- [ ] pragmarx/google2fa-laravel
- [ ] ramsey/uuid
- [ ] resend/resend-laravel
- [ ] spatie/laravel-activitylog
- [ ] spatie/laravel-backup
- [ ] spatie/laravel-cookie-consent
- [ ] spatie/laravel-data
- [ ] spatie/laravel-honeypot
- [ ] spatie/laravel-medialibrary
- [ ] spatie/laravel-one-time-passwords
- [ ] spatie/laravel-permission
- [ ] spatie/laravel-sitemap
- [ ] spatie/simple-excel

```bash
./vendor/bin/sail composer require \
  bacon/bacon-qr-code \
  barryvdh/laravel-dompdf \
  inertiajs/inertia-laravel:^3.0 \
  intervention/image \
  knuckleswtf/scribe \
  laravel/ai \
  laravel/fortify \
  laravel/horizon \
  laravel/reverb \
  laravel/sanctum \
  laravel/socialite \
  laravel/telescope \
  "league/flysystem-aws-s3-v3:^3.0" \
  pragmarx/google2fa-laravel \
  ramsey/uuid \
  resend/resend-laravel \
  spatie/laravel-activitylog \
  spatie/laravel-backup \
  spatie/laravel-cookie-consent \
  spatie/laravel-data \
  spatie/laravel-honeypot \
  spatie/laravel-medialibrary \
  spatie/laravel-one-time-passwords \
  spatie/laravel-permission \
  spatie/laravel-sitemap \
  spatie/simple-excel
```

---

## 2. Composer require --dev (6)

- [ ] barryvdh/laravel-debugbar
- [ ] barryvdh/laravel-ide-helper
- [ ] driftingly/rector-laravel
- [ ] larastan/larastan
- [ ] laravel/pint
- [ ] rector/rector

```bash
./vendor/bin/sail composer require --dev \
  barryvdh/laravel-debugbar \
  barryvdh/laravel-ide-helper \
  driftingly/rector-laravel \
  larastan/larastan \
  laravel/pint \
  rector/rector
```

> ⚠️ **Pest no compatible con Laravel 13 (aún).** `pestphp/pest-plugin-laravel` soporta como máximo Laravel ^12 y entra en conflicto con `phpunit ^12` y `laravel/pail`. Usa **PHPUnit 12** (`./vendor/bin/sail artisan test`). Cuando haya soporte L13:
> ```bash
> ./vendor/bin/sail composer require --dev pestphp/pest pestphp/pest-plugin-laravel -W
> ```

---

## 3. Opcionales

- [ ] laravel/vonage-notification-channel  *(solo OTP por SMS)*
- [x] laravel/sail  *(ya viene incluido al crear el proyecto)*

```bash
./vendor/bin/sail composer require laravel/vonage-notification-channel
```

---

## 4. Publish & install (en orden)

- [ ] Barryvdh\DomPDF\ServiceProvider
- [ ] Intervention\Image\Laravel\ServiceProvider
- [ ] Inertia\ServiceProvider (--force)
- [ ] Laravel\Ai\AiServiceProvider
- [ ] Laravel\Fortify\FortifyServiceProvider
- [ ] Laravel\Sanctum\SanctumServiceProvider
- [ ] PragmaRX\Google2FALaravel\ServiceProvider
- [ ] scribe-config (--tag)
- [ ] Spatie\Activitylog\ActivitylogServiceProvider (activitylog-migrations)
- [ ] Spatie\Activitylog\ActivitylogServiceProvider (activitylog-config)
- [ ] Spatie\Backup\BackupServiceProvider
- [ ] Spatie\CookieConsent\CookieConsentServiceProvider
- [ ] Spatie\Honeypot\HoneypotServiceProvider (honeypot-config)
- [ ] Spatie\LaravelData\LaravelDataServiceProvider (data-config)
- [ ] Spatie\MediaLibrary\MediaLibraryServiceProvider (medialibrary-migrations)
- [ ] Spatie\Permission\PermissionServiceProvider
- [ ] Spatie\Sitemap\SitemapServiceProvider (sitemap-config)
- [ ] one-time-passwords-config (--tag)
- [ ] telescope:install
- [ ] horizon:install
- [ ] reverb:install
- [ ] inertia:middleware
- [ ] migrate
- [ ] scribe:generate

**Importante:** cada `vendor:publish` es un comando completo. Para correrlos todos de una sola vez, encadénalos con `&& \` (ejecuta en orden y se detiene si alguno falla de verdad). **Nunca** uses solo `\` entre ellos: eso los une en un comando y da el error `No arguments expected`.

```bash
./vendor/bin/sail artisan vendor:publish --provider='Barryvdh\DomPDF\ServiceProvider' && \
./vendor/bin/sail artisan vendor:publish --provider='Intervention\Image\Laravel\ServiceProvider' && \
./vendor/bin/sail artisan vendor:publish --provider='Inertia\ServiceProvider' --force && \
./vendor/bin/sail artisan vendor:publish --provider='Laravel\Ai\AiServiceProvider' && \
./vendor/bin/sail artisan vendor:publish --provider='Laravel\Fortify\FortifyServiceProvider' && \
./vendor/bin/sail artisan vendor:publish --provider='Laravel\Sanctum\SanctumServiceProvider' && \
./vendor/bin/sail artisan vendor:publish --provider='PragmaRX\Google2FALaravel\ServiceProvider' && \
./vendor/bin/sail artisan vendor:publish --tag=scribe-config && \
./vendor/bin/sail artisan vendor:publish --provider='Spatie\Activitylog\ActivitylogServiceProvider' --tag=activitylog-migrations && \
./vendor/bin/sail artisan vendor:publish --provider='Spatie\Activitylog\ActivitylogServiceProvider' --tag=activitylog-config && \
./vendor/bin/sail artisan vendor:publish --provider='Spatie\Backup\BackupServiceProvider' && \
./vendor/bin/sail artisan vendor:publish --provider='Spatie\CookieConsent\CookieConsentServiceProvider' && \
./vendor/bin/sail artisan vendor:publish --provider='Spatie\Honeypot\HoneypotServiceProvider' --tag=honeypot-config && \
./vendor/bin/sail artisan vendor:publish --provider='Spatie\LaravelData\LaravelDataServiceProvider' --tag=data-config && \
./vendor/bin/sail artisan vendor:publish --provider='Spatie\MediaLibrary\MediaLibraryServiceProvider' --tag=medialibrary-migrations && \
./vendor/bin/sail artisan vendor:publish --provider='Spatie\Permission\PermissionServiceProvider' && \
./vendor/bin/sail artisan vendor:publish --provider='Spatie\Sitemap\SitemapServiceProvider' --tag=sitemap-config && \
./vendor/bin/sail artisan vendor:publish --tag='one-time-passwords-config' && \
./vendor/bin/sail artisan telescope:install && \
./vendor/bin/sail artisan horizon:install && \
./vendor/bin/sail artisan reverb:install && \
./vendor/bin/sail artisan inertia:middleware && \
./vendor/bin/sail artisan migrate && \
./vendor/bin/sail artisan scribe:generate
```

> Reglas rápidas:
> - `&& \` entre comandos = los corre en orden, uno tras otro (esta es la forma correcta para "todos de una vez").
> - `\` solo (sin `&&`) = continuación → **solo** para el `composer require` largo (un único comando).
> - Comillas simples `'...'` → las `\` del nombre del provider quedan literales en cualquier shell.
> - Para ir uno por uno: `./vendor/bin/sail artisan vendor:publish` sin argumentos → eliges del menú por número.
> - *"Unable to locate publishable resources"* (Inertia / Sitemap / Laravel AI) no corta la cadena: ese paquete se auto-registra y no tiene config que publicar.

---

## 5. Sin publish (nota)

- [ ] spatie/simple-excel — auto-discover, sin config
- [ ] resend/resend-laravel — config en `mail.php` + `services.php`
- [ ] barryvdh/laravel-debugbar — se activa con `APP_DEBUG=true`
- [ ] barryvdh/laravel-ide-helper — `artisan ide-helper:generate`
- [ ] pint / rector / larastan — sin publish (configs propias)
- [ ] pragmarx/google2fa-laravel — requiere migración con columna `google2fa_secret`

---

## 6. Frontend (npm) — opcional si no usaste `--vue`

```bash
./vendor/bin/sail npm install @inertiajs/vue3@^3.0 @inertiajs/vite@^3.0 vue@^3.5
./vendor/bin/sail npm install pinia @pinia/colada primevue@^4 @primevue/forms @primevue/themes primeicons zod tailwind-merge clsx laravel-echo pusher-js
./vendor/bin/sail npm install -D tailwindcss @tailwindcss/vite @vitejs/plugin-vue vue-tsc @pinia/colada-devtools
./vendor/bin/sail npx volt-vue add button card dialog inputtext datatable toast
```

> **Volt** (volt.primevue.org) copia los componentes a `resources/js/volt/` (code-ownership). Construidos sobre el core **unstyled** de PrimeVue + Tailwind v4, WCAG AA, responsive y TypeScript. Añade más con `npx volt-vue add <componente>`.
