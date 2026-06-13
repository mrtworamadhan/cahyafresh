<div class="h-full flex flex-col p-4 animate-fade-in text-zinc-800 dark:text-zinc-200" x-data="{ activeTab: 'kiriman' }">
    
    <div class="flex justify-between items-center mb-6 shrink-0">
        <div>
            <h2 class="text-2xl font-black text-indigo-600 dark:text-indigo-400 flex items-center gap-2">
                <x-heroicon-o-clipboard-document-list class="w-7 h-7" /> Manajemen PO & Operasional
            </h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 font-medium mt-1">Pantau jadwal kirim, packing list, dan rencana belanja barang besok.</p>
        </div>
        
        <button x-data="{ isDark: document.documentElement.classList.contains('dark') }" 
            @click="isDark = !isDark; isDark ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark'); localStorage.setItem('pos-theme', isDark ? 'dark' : 'light');"
            class="p-2.5 bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 hidden sm:block">
            <x-heroicon-o-moon x-show="!isDark" class="w-5 h-5" />
            <x-heroicon-o-sun x-show="isDark" class="w-5 h-5 text-yellow-400" style="display: none;" />
        </button>
    </div>

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4 shrink-0 bg-white dark:bg-zinc-800 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <div class="flex items-center gap-2">
            <x-heroicon-o-calendar class="w-5 h-5 text-indigo-500" />
            <span class="text-sm font-bold text-zinc-600 dark:text-zinc-400">Filter Tanggal Kerja Logistik:</span>
        </div>
        <select wire:model.live="selectedDate" class="text-sm font-bold bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg px-4 py-2 outline-none focus:ring-2 focus:ring-indigo-500 transition w-full sm:w-auto cursor-pointer">
            @foreach($availableDates as $date)
                <option value="{{ $date }}">
                    {{ $date === now()->format('Y-m-d') ? ' Hari Ini — ' : '' }}{{ \Carbon\Carbon::parse($date)->translatedFormat('d M Y') }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="flex bg-white dark:bg-zinc-800 p-1.5 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 w-full mb-4 shrink-0 overflow-x-auto hide-scrollbar">
        <button @click="activeTab = 'kiriman'" :class="activeTab === 'kiriman' ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-400 font-bold shadow-sm' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'" class="flex-1 py-2.5 px-4 rounded-lg text-sm transition-all flex justify-center items-center gap-2 whitespace-nowrap">
            <x-heroicon-o-truck class="w-5 h-5" /> Daftar Kiriman
        </button>
        <button @click="activeTab = 'packing'" :class="activeTab === 'packing' ? 'bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-400 font-bold shadow-sm' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'" class="flex-1 py-2.5 px-4 rounded-lg text-sm transition-all flex justify-center items-center gap-2 whitespace-nowrap">
            <x-heroicon-o-cube class="w-5 h-5" /> Packing List
        </button>
        <button @click="activeTab = 'shopping'" :class="activeTab === 'shopping' ? 'bg-rose-100 dark:bg-rose-900/50 text-rose-700 dark:text-rose-400 font-bold shadow-sm' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'" class="flex-1 py-2.5 px-4 rounded-lg text-sm transition-all flex justify-center items-center gap-2 whitespace-nowrap">
            <x-heroicon-o-shopping-bag class="w-5 h-5" /> Shopping List (Esok)
        </button>
        <button @click="activeTab = 'semua_po'" :class="activeTab === 'semua_po' ? 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-400 font-bold shadow-sm' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'" class="flex-1 py-2.5 px-4 rounded-lg text-sm transition-all flex justify-center items-center gap-2 whitespace-nowrap">
            <x-heroicon-o-queue-list class="w-5 h-5" /> Semua PO Aktif
        </button>
    </div>

    <div class="flex-1 overflow-y-auto pb-10">
        
        <div x-show="activeTab === 'kiriman'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="flex flex-col gap-4">
            <div class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-xl border border-indigo-200 dark:border-indigo-800/50 flex justify-between items-center">
                <div>
                    <h3 class="font-black text-indigo-700 dark:text-indigo-400">Jadwal Kirim/Ambil: {{ $todayDate }}</h3>
                    <p class="text-xs text-indigo-600/70 dark:text-indigo-500 font-medium">Selesaikan pesanan di bawah ini jika barang sudah di tangan konsumen.</p>
                </div>
                <span class="bg-indigo-600 text-white text-xs font-bold px-3 py-1.5 rounded-lg">{{ count($todayDeliveries) }} Menunggu</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @forelse($todayDeliveries as $order)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl p-5 border border-zinc-200 dark:border-zinc-700 shadow-sm flex flex-col gap-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-1 bg-zinc-100 dark:bg-zinc-700 text-zinc-500 rounded">{{ $order->order_number }}</span>
                                <h4 class="font-black text-lg text-zinc-800 dark:text-zinc-100 mt-1">{{ $order->customer?->name ?? 'Pelanggan Umum' }}</h4>
                                <p class="text-xs font-bold {{ $order->payment_status === 'paid' ? 'text-green-500' : 'text-rose-500' }} mt-0.5">
                                    {{ $order->payment_status === 'paid' ? '✔ Sudah Lunas' : '❌ Belum Lunas (Rp ' . number_format($order->total_amount, 0, ',', '.') . ')' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <x-heroicon-s-truck class="w-6 h-6 {{ $order->delivery_type === 'delivery' ? 'text-blue-500' : 'text-amber-500' }} inline-block" />
                                <p class="text-[10px] font-bold text-zinc-400 uppercase mt-1">{{ $order->delivery_type === 'delivery' ? 'Kirim Kurir' : 'Ambil di Toko' }}</p>
                            </div>
                        </div>

                        <div class="bg-zinc-50 dark:bg-zinc-900/50 p-3 rounded-xl border border-zinc-100 dark:border-zinc-700">
                            <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-2">Rincian Barang:</p>
                            <ul class="space-y-1.5 max-h-24 overflow-y-auto">
                                @foreach($order->orderItems as $item)
                                    <li class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex justify-between">
                                        <span>• {{ $item->product->name }}</span>
                                        <span class="font-black">{{ $item->qty_billed }}x</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="flex gap-2 mt-auto pt-2">
                            <button wire:click="openCompleteModal({{ $order->id }})" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md transition flex items-center justify-center gap-1.5">
                                <x-heroicon-o-check-circle class="w-4 h-4" /> Proses Selesai
                            </button>
                            <button wire:click="openCancelModal({{ $order->id }})" class="py-2.5 px-3 bg-white dark:bg-zinc-800 hover:bg-rose-50 dark:hover:bg-rose-900/20 text-rose-500 border border-rose-200 dark:border-rose-800 text-xs font-bold rounded-xl transition flex items-center justify-center">
                                <x-heroicon-o-x-mark class="w-4 h-4" /> Batalkan
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-16 flex flex-col items-center justify-center text-zinc-400">
                        <x-heroicon-o-face-smile class="w-16 h-16 mb-4 opacity-50" />
                        <h3 class="text-lg font-bold text-zinc-500">Santai Dulu!</h3>
                        <p class="text-sm font-medium mt-1">Tidak ada jadwal pengiriman/pengambilan PO untuk tanggal ini.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div x-show="activeTab === 'packing'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="flex flex-col gap-4">
            <div class="bg-amber-50 dark:bg-amber-900/20 p-4 rounded-xl border border-amber-200 dark:border-amber-800/50 flex justify-between items-center">
                <div>
                    <h3 class="font-black text-amber-700 dark:text-amber-400">Packing List (Total Muatan: {{ $todayDate }})</h3>
                    <p class="text-xs text-amber-600/70 dark:text-amber-500 font-medium">Siapkan barang-barang di bawah ini dari gudang untuk pesanan pada tanggal terpilih.</p>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-700 uppercase text-[10px] font-bold text-zinc-500 tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Nama Produk</th>
                            <th class="px-6 py-4 text-center">Total Dibutuhkan</th>
                            <th class="px-6 py-4 text-center">Status / Cek</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @forelse($packingListToday as $item)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                                <td class="px-6 py-4 font-bold text-zinc-800 dark:text-zinc-200">{{ $item->product_name }}</td>
                                <td class="px-6 py-4 text-center font-black text-amber-600 dark:text-amber-400 text-lg">
                                    {{ $item->total_qty }} <span class="text-[10px] font-bold uppercase text-zinc-400">{{ $item->base_unit }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="checkbox" class="w-5 h-5 text-amber-500 rounded border-zinc-300 cursor-pointer">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-zinc-400 font-medium">Tidak ada muatan barang yang perlu disiapkan pada tanggal ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div x-show="activeTab === 'shopping'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="flex flex-col gap-4">
            <div class="bg-rose-50 dark:bg-rose-900/20 p-4 rounded-xl border border-rose-200 dark:border-rose-800/50 flex justify-between items-center">
                <div>
                    <h3 class="font-black text-rose-700 dark:text-rose-400">Shopping List (Kulakan Untuk Kiriman: {{ $tomorrowDate }})</h3>
                    <p class="text-xs text-rose-600/70 dark:text-rose-500 font-medium">Rekapan kebutuhan esok hari dari tanggal filter. Pastikan belanja hari ini agar besok stok aman.</p>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-700 uppercase text-[10px] font-bold text-zinc-500 tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Nama Produk</th>
                            <th class="px-6 py-4 text-center">Total Kebutuhan Kulakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @forelse($shoppingListTomorrow as $item)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                                <td class="px-6 py-4 font-bold text-zinc-800 dark:text-zinc-200 flex items-center gap-2">
                                    <x-heroicon-o-shopping-cart class="w-4 h-4 text-rose-400" />
                                    {{ $item->product_name }}
                                </td>
                                <td class="px-6 py-4 text-center font-black text-rose-600 dark:text-rose-400 text-lg">
                                    {{ $item->total_qty }} <span class="text-[10px] font-bold uppercase text-zinc-400">{{ $item->base_unit }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-6 py-8 text-center text-zinc-400 font-medium">Belum ada pesanan masuk untuk jadwal keesokan harinya.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if(count($shoppingListTomorrow) > 0)
                <div class="text-right">
                    <button onclick="window.print()" class="px-6 py-3 bg-zinc-800 text-white font-bold rounded-xl shadow-lg hover:bg-zinc-700 transition inline-flex items-center gap-2">
                        <x-heroicon-o-printer class="w-5 h-5" /> Cetak Daftar Belanja
                    </button>
                </div>
            @endif
        </div>

        <div x-show="activeTab === 'semua_po'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="flex flex-col gap-4">
            <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-xl border border-purple-200 dark:border-purple-800/50 flex justify-between items-center">
                <div>
                    <h3 class="font-black text-purple-700 dark:text-purple-400">Semua PO Aktif Belum Terkirim (Global)</h3>
                    <p class="text-xs text-purple-600/70 dark:text-purple-500 font-medium">Pantau, batalkan, atau kelola seluruh pesanan Pre-Order yang masih berjalan menyeluruh.</p>
                </div>
                <span class="bg-purple-600 text-white text-xs font-bold px-3 py-1.5 rounded-lg">{{ count($activePoOrders) }} Pesanan</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @forelse($activePoOrders as $order)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl p-5 border border-zinc-200 dark:border-zinc-700 shadow-sm flex flex-col gap-4 relative overflow-hidden">
                        
                        <div class="absolute top-0 right-0 bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-400 text-[10px] font-black px-3 py-1.5 rounded-bl-xl border-b border-l border-purple-200 dark:border-purple-800">
                            Kirim: {{ \Carbon\Carbon::parse($order->delivery_date)->translatedFormat('d M Y') }}
                        </div>

                        <div class="flex justify-between items-center mt-3">
                            <div>
                                <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-1 bg-zinc-100 dark:bg-zinc-700 text-zinc-500 rounded">{{ $order->order_number }}</span>
                                <h4 class="font-black text-lg text-zinc-800 dark:text-zinc-100 mt-1">{{ $order->customer?->name ?? 'Pelanggan Umum' }}</h4>
                                <p class="text-[10px] font-bold text-zinc-400 mt-0.5">Gelombang: {{ $order->poBatch?->name ?? '-' }}</p>
                            </div>
                        </div>

                        <div class="bg-zinc-50 dark:bg-zinc-900/50 p-3 rounded-xl border border-zinc-100 dark:border-zinc-700">
                            <ul class="space-y-1.5 max-h-24 overflow-y-auto">
                                @foreach($order->orderItems as $item)
                                    <li class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex justify-between">
                                        <span>• {{ $item->product->name }}</span>
                                        <span class="font-black">{{ $item->qty_billed }}x</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="flex justify-between items-center mt-auto pt-2">
                            <p class="text-xs font-bold {{ $order->payment_status === 'paid' ? 'text-green-500' : 'text-rose-500' }}">
                                {{ $order->payment_status === 'paid' ? '✔ Lunas' : '❌ Tagihan: Rp ' . number_format($order->total_amount, 0, ',', '.') }}
                            </p>
                            <div class="flex gap-2">
                                <a href="/pos/penjualan?edit_order={{ $order->id }}" class="px-3 py-2 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 text-blue-600 text-xs font-bold rounded-xl transition flex items-center justify-center border border-blue-200 dark:border-blue-800">
                                    <x-heroicon-o-pencil-square class="w-4 h-4" /> Edit
                                </a>
                                <button wire:click="openCancelModal({{ $order->id }})" class="py-2.5 px-3 bg-white dark:bg-zinc-800 hover:bg-rose-50 dark:hover:bg-rose-900/20 text-rose-500 border border-rose-200 dark:border-rose-800 text-xs font-bold rounded-xl transition flex items-center justify-center">
                                    <x-heroicon-o-x-mark class="w-4 h-4" /> Batalkan PO
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-16 flex flex-col items-center justify-center text-zinc-400">
                        <x-heroicon-o-document-check class="w-16 h-16 mb-4 opacity-50" />
                        <h3 class="text-lg font-bold text-zinc-500">Belum Ada PO Aktif</h3>
                        <p class="text-sm font-medium mt-1">Semua pesanan PO sudah terkirim atau dibatalkan.</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    @if($showCompleteModal)
        <div class="fixed inset-0 z-[105] flex items-center justify-center bg-zinc-900/80 backdrop-blur-sm p-4 animate-fade-in">
            <div class="bg-white dark:bg-zinc-800 rounded-3xl w-full max-w-md shadow-2xl border border-zinc-200 dark:border-zinc-700 p-6 flex flex-col animate-fade-in-up">
                <div class="flex items-center gap-4 mb-5 border-b border-zinc-200 dark:border-zinc-700 pb-4">
                    <div class="w-12 h-12 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-full flex items-center justify-center shrink-0">
                        <x-heroicon-o-check-badge class="w-7 h-7" />
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-zinc-800 dark:text-zinc-100">Selesaikan Pesanan?</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">Nota: {{ $completeOrderNumber }}</p>
                    </div>
                </div>
                <div class="bg-indigo-50 dark:bg-indigo-900/10 p-4 rounded-xl border border-indigo-200 dark:border-indigo-800/50 mb-6">
                    <p class="text-sm font-bold text-indigo-700 dark:text-indigo-400 mb-2">Konfirmasi Pengiriman:</p>
                    <ul class="text-xs text-indigo-600/80 dark:text-indigo-500 space-y-1.5 font-medium list-disc pl-4">
                        <li>Pastikan barang fisik <strong>sudah diserahkan</strong> ke pelanggan atau kurir.</li>
                        <li>Sistem akan otomatis <strong>memotong stok di gudang</strong> segera setelah tombol di bawah ditekan.</li>
                        <li>Tindakan ini akan mengubah status pesanan menjadi Selesai secara permanen.</li>
                    </ul>
                </div>
                <div class="flex gap-3">
                    <button type="button" wire:click="closeCompleteModal" class="flex-1 py-3.5 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 text-zinc-700 dark:text-zinc-300 font-bold rounded-xl text-sm transition">Batal</button>
                    <button type="button" wire:click="executeCompleteOrder" class="flex-1 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-indigo-500/20 text-sm transition flex justify-center items-center gap-2">
                        <x-heroicon-o-check-circle class="w-4 h-4" /> Ya, Proses Selesai
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($showCancelModal)
        <div class="fixed inset-0 z-[105] flex items-center justify-center bg-zinc-900/80 backdrop-blur-sm p-4 animate-fade-in">
            <div class="bg-white dark:bg-zinc-800 rounded-3xl w-full max-w-md shadow-2xl border border-zinc-200 dark:border-zinc-700 p-6 flex flex-col animate-fade-in-up">
                <div class="flex items-center gap-4 mb-5 border-b border-zinc-200 dark:border-zinc-700 pb-4">
                    <div class="w-12 h-12 bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 rounded-full flex items-center justify-center shrink-0">
                        <x-heroicon-o-exclamation-triangle class="w-7 h-7" />
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-zinc-800 dark:text-zinc-100">Batalkan Pesanan?</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">Nota: {{ $cancelOrderNumber }}</p>
                    </div>
                </div>
                <div class="bg-rose-50 dark:bg-rose-900/10 p-4 rounded-xl border border-rose-200 dark:border-rose-800/50 mb-6">
                    <p class="text-sm font-bold text-rose-700 dark:text-rose-400 mb-2">Perhatian Terkait Dana:</p>
                    <ul class="text-xs text-rose-600/80 dark:text-rose-500 space-y-1 font-medium list-disc pl-4">
                        <li>Status pesanan ini saat ini: <span class="font-black uppercase">{{ $cancelPaymentStatus === 'paid' ? 'LUNAS' : ($cancelPaymentStatus === 'partial' ? 'DP (Bayar Sebagian)' : 'BELUM BAYAR (Hutang)') }}</span></li>
                        <li>Total Tagihan: Rp {{ number_format($cancelOrderAmount, 0, ',', '.') }}</li>
                        <li class="mt-2 text-rose-500">Jika konsumen sudah membayar, membatalkan pesanan ini <strong>TIDAK</strong> akan otomatis mengembalikan uang di sistem kasir. Anda wajib mencatat Pengeluaran Operasional secara manual di menu Keuangan sebagai <strong>"Refund/Pengembalian Dana"</strong>.</li>
                    </ul>
                </div>
                <div class="flex gap-3">
                    <button type="button" wire:click="closeCancelModal" class="flex-1 py-3.5 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 text-zinc-700 dark:text-zinc-300 font-bold rounded-xl text-sm transition">Kembali</button>
                    <button type="button" wire:click="executeCancelOrder" class="flex-1 py-3.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl shadow-lg shadow-rose-500/20 text-sm transition flex justify-center items-center gap-2">
                        <x-heroicon-o-trash class="w-4 h-4" /> Ya, Batalkan PO
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($showSuccessModal)
        <div class="fixed inset-0 z-[110] flex items-center justify-center bg-zinc-900/80 backdrop-blur-sm p-4 animate-fade-in">
            <div x-data="{ copied: false }" class="bg-white dark:bg-zinc-800 rounded-3xl w-full max-w-sm shadow-2xl border border-zinc-200 dark:border-zinc-700 p-6 flex flex-col items-center text-center animate-fade-in-up">
                
                <div class="w-16 h-16 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center mb-4">
                    <x-heroicon-o-check-circle class="w-12 h-12" />
                </div>
                
                <h3 class="text-xl font-black text-zinc-800 dark:text-zinc-100 mb-2">Berhasil!</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-5 font-medium">{{ $successMessage }}</p>

                <div class="w-full space-y-2 mb-5">
                    <a href="{{ $waLink }}" target="_blank"
                       class="w-full py-3 px-4 bg-green-500 hover:bg-green-600 text-white rounded-xl text-sm font-bold flex items-center justify-center gap-2 transition浏览 shadow-md shadow-green-500/20">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        <span>Kirim via WhatsApp</span>
                    </a>
                    
                    <button type="button" 
                            @click="
                                if (navigator.clipboard && window.isSecureContext) {
                                    navigator.clipboard.writeText('{{ $shareLink }}');
                                } else {
                                    let textArea = document.createElement('textarea');
                                    textArea.value = '{{ $shareLink }}';
                                    textArea.style.position = 'absolute';
                                    textArea.style.left = '-999999px';
                                    document.body.appendChild(textArea);
                                    textArea.select();
                                    document.execCommand('copy');
                                    textArea.remove();
                                }
                                copied = true;
                                setTimeout(() => copied = false, 2000);
                            "
                            class="w-full py-3 px-4 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-xl text-sm font-bold flex items-center justify-center gap-2 transition duration-200">
                        <div class="flex items-center gap-2">
                            <template x-if="!copied">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                                    <span>Salin Link Invoice Digital</span>
                                </span>
                            </template>
                            <template x-if="copied">
                                <span class="flex items-center gap-2 text-green-600 dark:text-green-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    <span>Berhasil Disalin!</span>
                                </span>
                            </template>
                        </div>
                    </button>
                </div>

                <button type="button" wire:click="closeSuccessModal" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-indigo-500/20 text-sm transition">Tutup</button>
            </div>
        </div>
    @endif
</div>