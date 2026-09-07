@use('App\Support\PageMeta')
@php($meta ??= PageMeta::default())
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

        <title>{{ $meta->documentTitle() }}</title>
        <meta name="description" content="{{ $meta->description }}">
        <meta name="robots" content="{{ $meta->robots() }}">
        @if($meta->canonical)
            <link rel="canonical" href="{{ $meta->canonical }}">
        @endif

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ PageMeta::siteName() }}">
        <meta property="og:title" content="{{ $meta->title }}">
        <meta property="og:description" content="{{ $meta->description }}">
        <meta property="og:url" content="{{ $meta->canonical ?? url()->current() }}">
        <meta property="og:image" content="{{ $meta->imageUrl() }}">
        <meta property="og:image:width" content="{{ $meta->imageWidth }}">
        <meta property="og:image:height" content="{{ $meta->imageHeight }}">
        <meta property="og:image:alt" content="{{ $meta->imageAlt }}">

        <meta name="twitter:card" content="{{ $meta->twitterCard() }}">
        <meta name="twitter:title" content="{{ $meta->title }}">
        <meta name="twitter:description" content="{{ $meta->description }}">
        <meta name="twitter:image" content="{{ $meta->imageUrl() }}">
        <meta name="twitter:image:alt" content="{{ $meta->imageAlt }}">

        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        <link rel="manifest" href="/site.webmanifest">
        <meta name="apple-mobile-web-app-title" content="{{ PageMeta::siteName() }}">
        <meta name="theme-color" content="#FBFAF7">

        @if($meta->structuredData)
            <script type="application/ld+json">{!! json_encode($meta->structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
        @endif

        <script>
            (function () {
                var stored = localStorage.getItem('theme');
                var theme = stored === 'dark' ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', theme);
            })();
        </script>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400..800&family=Hanken+Grotesk:ital,wght@0,300..800;1,400..600&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">

        @inertiaHead
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    </head>
    <body class="antialiased">
        @inertia
    </body>
</html>
