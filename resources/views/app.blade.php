<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- ==================== SEO META TAGS ==================== --}}
    <meta name="description" content="{{ $description ?? 'Sistem Inventory Universitas Al-Khairiyah — kelola dan pantau peminjaman inventaris kampus secara online dengan mudah dan aman.' }}">
    <meta name="keywords" content="sistem inventory, inventaris kampus, peminjaman barang, Universitas Al-Khairiyah">
    <meta name="author" content="Bayu ALbar Ladici">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $title ?? config('app.name', 'Sistem Inventory') }} - Universitas Al-Khairiyah">
    <meta property="og:description" content="{{ $description ?? 'Kelola dan pantau peminjaman inventaris kampus dengan sistem autentikasi modern.' }}">
    <meta property="og:image" content="{{ asset('images/og-cover.png') }}">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="{{ config('app.name', 'Sistem Inventory UNIVA') }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? config('app.name', 'Sistem Inventory') }} - Universitas Al-Khairiyah">
    <meta name="twitter:description" content="{{ $description ?? 'Kelola dan pantau peminjaman inventaris kampus dengan sistem autentikasi modern.' }}">
    <meta name="twitter:image" content="{{ asset('images/og-cover.png') }}">
    {{-- ========================================================= --}}


    {{-- Inline script to detect system dark mode preference and apply it immediately --}}
    <script>
        (function() {
            const appearance = '{{ $appearance ?? "system" }}';

            if (appearance === 'system') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                if (prefersDark) {
                    document.documentElement.classList.add('dark');
                }
            }
        })();
    </script>

    {{-- Inline style to set the HTML background color based on our theme in app.css --}}
    <style>
        html {
            background-color: oklch(1 0 0);
        }

        html.dark {
            background-color: oklch(0.145 0 0);
        }
    </style>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    @fonts

    @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
    <x-inertia::head>
        <title>{{ $title ?? config('app.name', 'Sistem Inventory - Universitas Al-Khairiyah') }}</title>
    </x-inertia::head>
</head>
<body class="font-sans antialiased">
    <x-inertia::app />
</body>
</html>
