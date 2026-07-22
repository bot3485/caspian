<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- PWA & iOS Optimization -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Caspian">
<link rel="apple-touch-icon" href="{{ asset('roulette.jpg') }}">
<link rel="manifest" href="{{ asset('manifest.json') }}" crossorigin="use-credentials">
<link rel="icon" type="image/jpeg" href="{{ asset('roulette.jpg') }}">

<!-- Preconnect CDNs (High-Velocity Hack) -->
<link rel="preconnect" href="https://cdnjs.cloudflare.com">
<link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">

<title>{{ config('app.name', 'Caspian') }} — Intelligence Hub</title>

<!-- Core Assets -->
@vite(['resources/css/app.css', 'resources/js/app.js'])

<!-- Dynamic Page Styles -->
@stack('styles')

<style>
    [x-cloak] { display: none !important; }
    :root { --app-height: 100svh; }
    body { background: #020202; color: #fff; overflow-x: hidden; }
</style>