<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <main class="clinic-auth-page">
            <section class="clinic-card clinic-auth-card">
                <a class="clinic-brand clinic-auth-brand" href="/" aria-label="{{ config('clinique.nom', 'Clinique') }} — accueil">
                    <span class="clinic-brand__mark" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M10 3h4v7h7v4h-7v7h-4v-7H3v-4h7V3Z" fill="currentColor"/></svg>
                    </span>
                    <span>{{ config('clinique.nom', 'Clinique') }}</span>
                </a>
                {{ $slot }}
            </section>
        </main>
    </body>
</html>
