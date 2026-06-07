<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Super POS - Smart Supply' }}</title>

    <meta name="description" content="{{ $description ?? 'Aplikasi kasir cerdas (POS) untuk manajemen penjualan, stok, dan pencatatan keuangan bisnis secara real-time.' }}">
    <meta name="keywords" content="Aplikasi Kasir, Point of Sales, POS, Manajemen Bisnis, Invoice Online, Pencatatan Keuangan, Super POS">
    <meta name="author" content="Super POS">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="icon" type="image/png" href="{{ asset('images/brand/icon-colour.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="{{ $color ?? '#16a34a' }}"> <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Super POS">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/icon-colour.png') }}">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $title ?? 'Invoice Tagihan - Super POS' }}">
    <meta property="og:description" content="{{ $description ?? 'Klik tautan ini untuk melihat detail tagihan dan informasi pembayaran transaksi Anda.' }}">
    <meta property="og:image" content="{{ asset('images/brand/icon-colour.png') }}">
    <meta property="og:site_name" content="Super POS">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? 'Invoice Tagihan - Super POS' }}">
    <meta name="twitter:description" content="{{ $description ?? 'Klik tautan ini untuk melihat detail tagihan dan informasi pembayaran transaksi Anda.' }}">
    <meta name="twitter:image" content="{{ asset('images/brand/icon-colour.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style> 
        [x-cloak] { display: none !important; } 
        body::-webkit-scrollbar { display: none; }
        body { -ms-overflow-style: none; scrollbar-width: none; }
    </style>

    @if(config('services.ga.measurement_id') || env('GA_MEASUREMENT_ID'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ env('GA_MEASUREMENT_ID') }}"></script>
    @endif

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css"/>
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
        .dark .driver-popover-title { color: #f4f4f5 !important; }
        .driver-popover-description {
            font-size: 0.875rem !important;
            color: #71717a !important;
            margin-top: 0.5rem !important;
            line-height: 1.5 !important;
        }
        .dark .driver-popover-description { color: #a1a1aa !important; }
        
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
        
        .driver-popover-prev-btn, .driver-popover-close-btn {
            background-color: #f4f4f5 !important;
            color: #52525b !important;
            border-radius: 0.75rem !important;
            font-weight: 700 !important;
            padding: 0.6rem 1.2rem !important;
            border: none !important;
            text-shadow: none !important;
        }
        .dark .driver-popover-prev-btn, .dark .driver-popover-close-btn {
            background-color: #3f3f46 !important;
            color: #e4e4e7 !important;
        }
    </style>
</head>
<body class="bg-zinc-100 dark:bg-zinc-950 text-zinc-900 dark:text-white font-sans h-screen overflow-hidden" 
      x-data="{ 
          sidebarOpen: false, 
          isCollapsed: localStorage.getItem('pos-sidebar-collapsed') === 'true' 
      }"
      x-init="$watch('isCollapsed', val => localStorage.setItem('pos-sidebar-collapsed', val))">

    <div class="md:hidden flex items-center justify-between bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800 p-4 shrink-0">
        <h1 class="font-black text-lg text-blue-600 dark:text-blue-500">SUPER POS</h1>
        <button @click="sidebarOpen = !sidebarOpen" class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
            <x-heroicon-o-bars-3 class="w-7 h-7" />
        </button>
    </div>

    <div class="flex h-full md:h-screen overflow-hidden relative">
        
        <aside :class="{
                  'translate-x-0': sidebarOpen,
                  '-translate-x-full': !sidebarOpen,
                  'w-64': !isCollapsed,
                  'w-64 md:w-20': isCollapsed
               }" 
               class="fixed md:static inset-y-0 left-0 z-50 bg-white dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-800 transition-all duration-300 ease-in-out md:translate-x-0 flex flex-col h-full shadow-2xl md:shadow-none overflow-x-hidden">
            
            <div class="hidden md:flex items-center justify-center h-16 border-b border-zinc-200 dark:border-zinc-800 shrink-0 transition-all">
                <h1 x-show="!isCollapsed" class="font-black text-xl tracking-wider text-blue-600 dark:text-blue-500 whitespace-nowrap">SUPER POS</h1>
                <h1 x-show="isCollapsed" style="display: none;" class="font-black text-2xl tracking-wider text-blue-600 dark:text-blue-500">SP</h1>
            </div>

            <nav class="flex-1 overflow-y-auto py-6 px-3 flex flex-col gap-2">
                <a href="/pos/penjualan" title="Penjualan (Sales)" 
                   class="{{ request()->is('pos/penjualan') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-bold' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800/50' }} flex items-center px-3 py-3 rounded-xl transition-all"
                   :class="isCollapsed ? 'justify-center' : 'gap-3'">
                    <x-heroicon-o-building-storefront class="w-6 h-6 shrink-0" />
                    <span x-show="!isCollapsed" class="whitespace-nowrap">Penjualan</span>
                </a>

                <a href="/pos/po" title="Manajemen PO & Packing"
                   class="{{ request()->is('pos/po') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 font-bold' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800/50' }} flex items-center px-3 py-3 rounded-xl transition-all"
                   :class="isCollapsed ? 'justify-center' : 'gap-3'">
                    <x-heroicon-o-truck class="w-6 h-6 shrink-0" />
                    <span x-show="!isCollapsed" class="whitespace-nowrap">Manajemen PO</span>
                </a>
                
                <a href="/pos/pembelian" title="Terima Barang (Restock)"
                   class="{{ request()->is('pos/pembelian') ? 'bg-teal-50 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400 font-bold' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800/50' }} flex items-center px-3 py-3 rounded-xl transition-all"
                   :class="isCollapsed ? 'justify-center' : 'gap-3'">
                    <x-heroicon-o-archive-box-arrow-down class="w-6 h-6 shrink-0" />
                    <span x-show="!isCollapsed" class="whitespace-nowrap">Belanja</span>
                </a>

                <a href="/pos/keuangan" title="Keuangan & Operasional"
                   class="{{ request()->is('pos/keuangan') ? 'bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 font-bold' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800/50' }} flex items-center px-3 py-3 rounded-xl transition-all"
                   :class="isCollapsed ? 'justify-center' : 'gap-3'">
                    <x-heroicon-o-banknotes class="w-6 h-6 shrink-0" />
                    <span x-show="!isCollapsed" class="whitespace-nowrap">Keuangan</span>
                </a>
            </nav>

            <div class="p-3 border-t border-zinc-200 dark:border-zinc-800 flex flex-col gap-2">
                <button @click="isCollapsed = !isCollapsed" title="Perkecil/Perbesar Menu"
                        class="hidden md:flex items-center px-3 py-2.5 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-all"
                        :class="isCollapsed ? 'justify-center' : 'gap-3'">
                    <x-heroicon-o-chevron-double-left x-show="!isCollapsed" class="w-5 h-5 shrink-0" />
                    <x-heroicon-o-chevron-double-right x-show="isCollapsed" style="display: none;" class="w-5 h-5 shrink-0" />
                    <span x-show="!isCollapsed" class="text-sm font-bold whitespace-nowrap">Perkecil Menu</span>
                </button>

                <a href="/admin" title="Keluar ke Dashboard Admin" 
                   class="flex items-center px-3 py-2.5 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-all"
                   :class="isCollapsed ? 'justify-center' : 'gap-3'">
                    <x-heroicon-o-arrow-left-on-rectangle class="w-5 h-5 shrink-0" />
                    <span x-show="!isCollapsed" class="text-sm font-bold whitespace-nowrap">Keluar ke Admin</span>
                </a>


            </div>
        </aside>

        <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-black/50 md:hidden" x-transition.opacity></div>

        <main class="flex-1 h-full overflow-hidden flex flex-col bg-zinc-100 dark:bg-zinc-950 relative transition-all duration-300">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>