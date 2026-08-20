{{--
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

B12 (2026-08-20): Minimales Layout fuer Fehlerseiten, wenn kein
User eingeloggt ist. Kein Rail, kein Editor-Chrome — nur eine
zentrierte Buehne fuer den error-shell. Der authenticated Fall
laeuft weiterhin ueber `projects.layout`.
--}}

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>crowdCuratio · @yield('title', __('error'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper-0 text-ink-900 antialiased">
    <main role="main" class="flex min-h-screen items-center justify-center">
        @yield('content')
    </main>
</body>
</html>
