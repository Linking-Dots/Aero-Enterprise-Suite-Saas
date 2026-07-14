<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">

<head>
    <!-- Essential Meta Tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=yes maximum-scale=1 user-scalable=yes">
    <meta http-equiv=" X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />



    <!-- Security & Performance -->
    @production
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    @endproduction
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta name="referrer" content="strict-origin-when-cross-origin">

    <!-- SEO & Social Meta -->
    <meta name="description" content="{{ config('app.name') }} - Comprehensive Enterprise Resource Planning System for efficient business management">
    <meta name="keywords" content="ERP, Enterprise Resource Planning, Business Management, HR Management">
    <meta name="author" content="Emam Hosen">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#0C2742">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ config('app.name') }}">
    <meta property="og:description" content="Comprehensive Enterprise Resource Planning System">
    <meta property="og:image" content="{{ asset('assets/images/og-image.png') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ config('app.name') }}">
    <meta name="twitter:description" content="Comprehensive Enterprise Resource Planning System">
    <meta name="twitter:image" content="{{ asset('assets/images/twitter-card.png') }}">

    <!-- PWA Configuration -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ $siteName ?? config('app.name') }}">

    <!-- Favicon — white-label if configured, Meridian static icons otherwise -->
    @if(!empty($faviconUrl))
    <link rel="icon" type="image/x-icon" href="{{ $faviconUrl }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ $faviconUrl }}">
    @else
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icon-192.png') }}">
    @endif
    @if(!empty($logoUrl))
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $logoUrl }}">
    @else
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    @endif

    <!-- DNS Prefetch for Performance -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel="dns-prefetch" href="//translate.google.com">
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Font Loading -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Aeos Design System Fonts — core + Theme Studio typefaces -->
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&family=JetBrains+Mono:wght@400;500;600&family=Geist:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&family=Sora:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,700&family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">

   
</head>

<body>
   
    @routes
    @inertiaHead
    @viteReactRefresh
    @vite(['vendor/aero/ui/resources/css/app.css', 'vendor/aero/ui/resources/js/app.jsx'])

    <!-- Platform-managed tenant custom CSS — after the app bundle so it can
         override design-system rules (white-label console; kill switch upstream) -->
    @if(!empty($customCssUrl))
    <link rel="stylesheet" href="{{ $customCssUrl }}">
    @endif
    
    <!-- Main Inertia App Container -->
    @inertia

   
   
</body>

</html>
