<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $portalBusiness = null;
        if (request()->segment(1) == 'portal' && request()->segment(2)) {
            $customerSlug = request()->segment(2);
            $customerData = \App\Models\Customer::with('business')->where('slug', $customerSlug)->first();
            if ($customerData) {
                $portalBusiness = $customerData->business;
            }
        }
    @endphp

    <title>
        {{ $title ?? ($portalBusiness ? 'Portal Pelanggan - ' . $portalBusiness->name : 'Super POS - Smart Supply') }}
    </title>

    <meta name="description"
        content="{{ $description ?? 'Aplikasi kasir cerdas (POS) untuk manajemen penjualan, stok, dan pencatatan keuangan bisnis secara real-time.' }}">
    <meta name="keywords"
        content="Aplikasi Kasir, Point of Sales, POS, Manajemen Bisnis, Portal Pelanggan, Pencatatan Keuangan, Super POS">
    <meta name="author" content="{{ $portalBusiness ? $portalBusiness->name : 'Super POS' }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="icon" type="image/png" href="{{ asset('images/brand/icon-colour.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="{{ $color ?? '#16a34a' }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Super POS">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/icon-colour.png') }}">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title"
        content="{{ $title ?? ($portalBusiness ? 'Portal Pelanggan - ' . $portalBusiness->name : 'Portal Pelanggan - Super POS') }}">
    <meta property="og:description"
        content="{{ $description ?? 'Klik tautan ini untuk melihat detail tagihan dan informasi riwayat transaksi Anda.' }}">

    @if($portalBusiness && $portalBusiness->logo)
        <meta property="og:image" itemprop="image" content="{{ asset('storage/' . $portalBusiness->logo) }}">
        <meta property="og:image:secure_url" content="{{ asset('storage/' . $portalBusiness->logo) }}">
    @else
        <meta property="og:image" itemprop="image" content="{{ asset('images/brand/icon-colour.png') }}">
        <meta property="og:image:secure_url" content="{{ asset('images/brand/icon-colour.png') }}">
    @endif

    <meta property="og:site_name" content="{{ $portalBusiness ? $portalBusiness->name : 'Super POS' }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title"
        content="{{ $title ?? ($portalBusiness ? 'Portal Pelanggan - ' . $portalBusiness->name : 'Portal Pelanggan - Super POS') }}">
    <meta name="twitter:description"
        content="{{ $description ?? 'Klik tautan ini untuk melihat detail tagihan dan informasi riwayat transaksi Anda.' }}">

    @if($portalBusiness && $portalBusiness->logo)
        <meta name="twitter:image" content="{{ asset('storage/' . $portalBusiness->logo) }}">
    @else
        <meta name="twitter:image" content="{{ asset('images/brand/icon-colour.png') }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style> 
        [x-cloak] { display: none !important; } 
        
        *::-webkit-scrollbar { 
            display: none !important; 
        }
        * { 
            -ms-overflow-style: none !important; 
            scrollbar-width: none !important; 
        }
    </style>

    @if(config('services.ga.measurement_id') || env('GA_MEASUREMENT_ID'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ env('GA_MEASUREMENT_ID') }}"></script>
    @endif

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css" />
    <script src="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.js.iife.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <style>
        .driver-popover {
            border-radius: 1.5rem !important;
            padding: 1.5rem !important;
            border: 1px solid #f4f4f5 !important;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1) !important;
            font-family: inherit !important;
        }

        .dark .driver-popover {
            background-color: #27272a !important;
            border-color: #3f3f46 !important;
        }

        .driver-popover-title {
            font-size: 1.125rem !important;
            font-weight: 800 !important;
            color: #27272a !important;
        }

        .dark .driver-popover-title {
            color: #f4f4f5 !important;
        }

        .driver-popover-description {
            font-size: 0.875rem !important;
            color: #71717a !important;
            margin-top: 0.5rem !important;
            line-height: 1.5 !important;
        }

        .dark .driver-popover-description {
            color: #a1a1aa !important;
        }

        .driver-popover-next-btn {
            background-color: #16a34a !important;
            color: white !important;
            border-radius: 0.75rem !important;
            font-weight: 700 !important;
            padding: 0.6rem 1.2rem !important;
            border: none !important;
            text-shadow: none !important;
            box-shadow: 0 4px 6px -1px rgb(22 163 74 / 0.3) !important;
        }

        .driver-popover-prev-btn,
        .driver-popover-close-btn {
            background-color: #f4f4f5 !important;
            color: #52525b !important;
            border-radius: 0.75rem !important;
            font-weight: 700 !important;
            padding: 0.6rem 1.2rem !important;
            border: none !important;
            text-shadow: none !important;
        }

        .dark .driver-popover-prev-btn,
        .dark .driver-popover-close-btn {
            background-color: #3f3f46 !important;
            color: #e4e4e7 !important;
        }
    </style>
</head>

<body>
    {{ $slot }}

    @livewireScripts
</body>

</html>