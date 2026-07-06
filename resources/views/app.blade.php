<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Theme FOUC killer (FRONTEND/SKILL.md §1.5). Mirrors useThemeStore's
             useColorMode: reads the SAME `vidula-theme` key, whose value is the
             mode name ('auto' | 'light' | 'dark'). Dark-first when nothing is
             stored. Nonce comes from the request-scoped CSP binding shared with
             the SecurityHeaders middleware. --}}
        <script nonce="{{ app('csp-nonce') }}">
            (function () {
                try {
                    var stored = localStorage.getItem('vidula-theme');
                    var systemDark = window.matchMedia
                        && window.matchMedia('(prefers-color-scheme: dark)').matches;
                    var isDark;
                    if (stored === 'light') { isDark = false; }
                    else if (stored === 'dark') { isDark = true; }
                    else if (stored === 'auto') { isDark = systemDark; }
                    else { isDark = true; } /* dark-first default */
                    if (isDark) {
                        document.documentElement.classList.add('dark');
                    }
                    document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
                } catch (e) { /* localStorage blocked — fall through to light */ }
            })();
        </script>

        {{-- Default document title (DB brand + env tagline), supplied by the
             `app` view composer in AppServiceProvider. The `inertia` attribute
             lets Inertia's Head manager replace it per page after hydration. --}}
        <title inertia>{{ $documentTitle }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
