<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Theme FOUC killer (FRONTEND/SKILL.md §1.5). Dark-first: apply `.dark`
             before first paint when no stored preference exists. Nonce comes from
             the request-scoped CSP binding shared with the SecurityHeaders middleware. --}}
        <script nonce="{{ app('csp-nonce') }}">
            (function () {
                try {
                    var stored = localStorage.getItem('app:theme');
                    var theme = stored || 'dark';
                    if (theme === 'dark') {
                        document.documentElement.classList.add('dark');
                    }
                    document.documentElement.style.colorScheme = theme;
                } catch (e) { /* localStorage blocked — fall through to light */ }
            })();
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
