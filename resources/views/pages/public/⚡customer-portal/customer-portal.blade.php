<div class="min-h-screen bg-zinc-100 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 pb-72 font-sans" x-data="{ activeTab: 'unpaid' }">
    
    <div class="bg-blue-600 dark:bg-blue-800 text-white p-6 rounded-b-3xl shadow-lg relative overflow-hidden">
        <div class="relative z-10">
            <h1 class="text-2xl font-black">{{ $business->name ?? 'SMART SUPPLY' }}</h1>
            <p class="text-blue-100 text-sm mt-1">Portal Tagihan & Riwayat Transaksi</p>
            
            <div class="mt-6 bg-white/10 p-4 rounded-2xl backdrop-blur-sm border border-white/20">
                <p class="text-xs text-blue-100 uppercase tracking-wider font-bold mb-1">Halo,</p>
                <h2 class="text-xl font-black">{{ $customer->name }}</h2>
                <p class="text-sm font-medium opacity-80">{{ $customer->phone ?? '-' }}</p>
            </div>
            <div class="mt-6 grid grid-cols-3 gap-3">
                <div class="bg-white/10 p-3 rounded-xl backdrop-blur-sm border border-white/20 text-center">
                    <p class="text-[10px] text-blue-100 uppercase font-bold mb-1">Piutang</p>
                    <p class="text-sm font-black text-rose-300">Rp {{ number_format($totalPiutang, 0, ',', '.') }}</p>
                </div>
                <div class="bg-white/10 p-3 rounded-xl backdrop-blur-sm border border-white/20 text-center">
                    <p class="text-[10px] text-blue-100 uppercase font-bold mb-1">Deposit</p>
                    <p class="text-sm font-black text-green-300">Rp {{ number_format($totalDeposit, 0, ',', '.') }}</p>
                </div>
                <div class="bg-white/10 p-3 rounded-xl backdrop-blur-sm border border-white/20 text-center">
                    <p class="text-[10px] text-blue-100 uppercase font-bold mb-1">Komisi</p>
                    <p class="text-sm font-black text-amber-300">Rp {{ number_format($totalKomisi, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-white opacity-10 rounded-full blur-2xl"></div>
    </div>

    <div class="px-4 mt-6">
        <div class="flex p-1 bg-zinc-200 dark:bg-zinc-800 rounded-xl overflow-x-auto gap-1">
            <button @click="activeTab = 'unpaid'" 
                    :class="activeTab === 'unpaid' ? 'bg-white dark:bg-zinc-700 shadow text-blue-600 dark:text-blue-400' : 'text-zinc-500 hover:text-zinc-700'"
                    class="flex-1 whitespace-nowrap py-2 px-3 text-[11px] sm:text-sm font-bold rounded-lg transition-all duration-200">
                Belum Lunas ({{ count($unpaidOrders) }})
            </button>
            <button @click="activeTab = 'draft'" 
                    :class="activeTab === 'draft' ? 'bg-white dark:bg-zinc-700 shadow text-amber-600 dark:text-amber-400' : 'text-zinc-500 hover:text-zinc-700'"
                    class="flex-1 whitespace-nowrap py-2 px-3 text-[11px] sm:text-sm font-bold rounded-lg transition-all duration-200">
                PO / Disiapkan ({{ count($draftOrders) }})
            </button>
            <button @click="activeTab = 'paid'" 
                    :class="activeTab === 'paid' ? 'bg-white dark:bg-zinc-700 shadow text-green-600 dark:text-green-400' : 'text-zinc-500 hover:text-zinc-700'"
                    class="flex-1 whitespace-nowrap py-2 px-3 text-[11px] sm:text-sm font-bold rounded-lg transition-all duration-200">
                Riwayat ({{ count($paidOrders) }})
            </button>
        </div>
    </div>

    <div x-show="activeTab === 'unpaid'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="p-4 space-y-4">
        @forelse($unpaidOrders as $order)
            <div x-data="{ expanded: false }" class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-rose-200 dark:border-rose-900/50 overflow-hidden">
                <button @click="expanded = !expanded" class="w-full p-4 flex justify-between items-center text-left bg-rose-50/50 dark:bg-rose-900/10 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition">
                    <div>
                        <span class="inline-block px-2 py-1 bg-rose-100 dark:bg-rose-900 text-rose-600 dark:text-rose-400 text-[10px] font-black uppercase rounded mb-1">Tagihan Aktif</span>
                        <h3 class="font-bold text-sm">{{ $order->order_number }}</h3>
                        <p class="text-xs text-zinc-500">{{ \Carbon\Carbon::parse($order->order_date)->format('d M Y') }}</p>
                    </div>
                    <div class="text-right flex flex-col items-end gap-1">
                        <span class="text-lg font-black text-rose-600 dark:text-rose-500">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                        <x-heroicon-o-chevron-down class="w-4 h-4 text-zinc-400 transition-transform duration-300" x-bind:class="expanded ? 'rotate-180' : ''" />
                    </div>
                </button>

                <div x-show="expanded" x-collapse>
                    <div class="p-4 border-t border-zinc-100 dark:border-zinc-700">
                        <div class="space-y-2 mb-4">
                            @foreach($order->orderItems as $item)
                                <div class="flex justify-between text-xs font-medium">
                                    <span class="text-zinc-600 dark:text-zinc-400">{{ $item->qty_billed }}x {{ $item->product->name }}</span>
                                    <span>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="mt-3 flex gap-2">
                            <a href="/invoice/{{ $order->order_number }}" target="_blank" class="flex-1 flex justify-center items-center gap-1 py-2.5 bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-[11px] font-bold rounded-lg hover:bg-zinc-200 transition">
                                <x-heroicon-o-eye class="w-4 h-4" /> Lihat Nota
                            </a>
                            <a href="/invoice/{{ $order->order_number }}/download" class="flex-1 flex justify-center items-center gap-1 py-2.5 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[11px] font-bold rounded-lg hover:bg-blue-100 border border-blue-200 dark:border-blue-800 transition">
                                <x-heroicon-o-arrow-down-tray class="w-4 h-4" /> Download PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-10 opacity-50">
                <x-heroicon-o-face-smile class="w-16 h-16 mx-auto mb-3" />
                <p class="font-bold">Luar biasa! Tidak ada tagihan yang tertunggak.</p>
            </div>
        @endforelse
    </div>

    <div x-show="activeTab === 'draft'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="p-4 space-y-4">
        @forelse($draftOrders as $order)
            <div x-data="{ expanded: false }" class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-amber-200 dark:border-amber-900/50 overflow-hidden">
                <button @click="expanded = !expanded" class="w-full p-4 flex justify-between items-center text-left bg-amber-50/50 dark:bg-amber-900/10 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition">
                    <div>
                        <span class="inline-block px-2 py-1 bg-amber-100 dark:bg-amber-900 text-amber-600 dark:text-amber-400 text-[10px] font-black uppercase rounded mb-1">Sedang Disiapkan</span>
                        <h3 class="font-bold text-sm">{{ $order->order_number }}</h3>
                        <p class="text-xs text-zinc-500">Estimasi Kirim: {{ $order->delivery_date ? \Carbon\Carbon::parse($order->delivery_date)->format('d M Y') : 'Menunggu Info' }}</p>
                    </div>
                    <div class="text-right flex flex-col items-end gap-1">
                        <span class="text-lg font-black text-amber-600 dark:text-amber-500">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                        <x-heroicon-o-chevron-down class="w-4 h-4 text-zinc-400 transition-transform duration-300" x-bind:class="expanded ? 'rotate-180' : ''" />
                    </div>
                </button>

                <div x-show="expanded" x-collapse>
                    <div class="p-4 border-t border-zinc-100 dark:border-zinc-700">
                        <div class="space-y-2 mb-4">
                            @foreach($order->orderItems as $item)
                                <div class="flex justify-between text-xs font-medium">
                                    <span class="text-zinc-600 dark:text-zinc-400">{{ $item->qty_billed }}x {{ $item->product->name }}</span>
                                    <span>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-100 dark:border-amber-800/50 text-center">
                            <p class="text-xs font-bold text-amber-800 dark:text-amber-300">
                                Pesanan ini sedang direkap/disiapkan oleh gudang dan belum masuk tagihan.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-10 opacity-50">
                <x-heroicon-o-clock class="w-16 h-16 mx-auto mb-3" />
                <p class="font-bold">Tidak ada pesanan PO / Draft saat ini.</p>
            </div>
        @endforelse
    </div>

    <div x-show="activeTab === 'paid'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="p-4 space-y-4">
        @forelse($paidOrders as $order)
             <div x-data="{ expanded: false }" class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <button @click="expanded = !expanded" class="w-full p-4 flex justify-between items-center text-left hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                    <div>
                        <span class="inline-block px-2 py-1 bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 text-[10px] font-black uppercase rounded mb-1">LUNAS</span>
                        <h3 class="font-bold text-sm text-zinc-700 dark:text-zinc-300">{{ $order->order_number }}</h3>
                        <p class="text-xs text-zinc-500">{{ \Carbon\Carbon::parse($order->order_date)->format('d M Y') }}</p>
                    </div>
                    <div class="text-right flex flex-col items-end gap-1">
                        <span class="text-sm font-bold text-zinc-600 dark:text-zinc-400">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                        <x-heroicon-o-chevron-down class="w-4 h-4 text-zinc-400 transition-transform duration-300" x-bind:class="expanded ? 'rotate-180' : ''" />
                    </div>
                </button>
                <div x-show="expanded" x-collapse>
                    <div class="p-4 border-t border-zinc-100 dark:border-zinc-700">
                        <div class="flex gap-2">
                            <a href="/invoice/{{ $order->order_number }}" target="_blank" class="flex-1 flex justify-center items-center gap-1 py-2.5 bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-[11px] font-bold rounded-lg hover:bg-zinc-200 transition">
                                <x-heroicon-o-eye class="w-4 h-4" /> Lihat Nota
                            </a>
                            <a href="/invoice/{{ $order->order_number }}/download" class="flex-1 flex justify-center items-center gap-1 py-2.5 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[11px] font-bold rounded-lg hover:bg-blue-100 border border-blue-200 dark:border-blue-800 transition">
                                <x-heroicon-o-arrow-down-tray class="w-4 h-4" /> Download PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-10 opacity-50">
                <x-heroicon-o-archive-box class="w-16 h-16 mx-auto mb-3" />
                <p class="font-bold">Belum ada riwayat transaksi lunas.</p>
            </div>
        @endforelse
    </div>

    @if($totalUnpaid > 0)
        <div x-data="{ showPayment: false }" class="fixed bottom-0 left-0 w-full bg-white dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700 p-4 shadow-[0_-10px_40px_rgba(0,0,0,0.1)] rounded-t-3xl z-50 transition-all duration-300">
            <div class="max-w-md mx-auto">
                
                <div class="flex justify-between items-end mb-2">
                    <div>
                        <p class="text-[10px] uppercase font-bold tracking-wider text-rose-500 mb-1">Total Menunggu Pembayaran</p>
                        <h2 class="text-3xl font-black text-rose-600 dark:text-rose-500">Rp {{ number_format($totalUnpaid, 0, ',', '.') }}</h2>
                    </div>
                    
                    <button @click="showPayment = !showPayment" class="flex items-center gap-1 text-xs font-bold text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 px-3 py-2 rounded-xl transition hover:bg-blue-100">
                        <span x-text="showPayment ? 'Tutup' : 'Cara Bayar'"></span>
                        <x-heroicon-o-chevron-up class="w-4 h-4 transition-transform duration-300" x-bind:class="showPayment ? 'rotate-180' : ''" />
                    </button>
                </div>

                <div x-show="showPayment" x-collapse class="pt-3 border-t border-zinc-100 dark:border-zinc-700 mt-2">
                    
                    @if(count($wallets) > 0)
                        <div class="mb-4 p-3 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700">
                            <p class="text-xs font-bold text-zinc-500 mb-2">Instruksi Transfer ke Rekening:</p>
                            <div class="flex flex-col gap-2 max-h-40 overflow-y-auto pr-1">
                                @foreach($wallets as $acc)
                                    <div class="flex justify-between items-center bg-white dark:bg-zinc-800 p-2.5 rounded-lg border border-zinc-200 dark:border-zinc-700 shadow-sm">
                                        <div>
                                            <p class="text-sm font-black text-zinc-800 dark:text-zinc-200">{{ $acc->name }}</p>
                                            <p class="text-xs font-mono text-zinc-500 dark:text-zinc-400">{{ $acc->account_number ?? '-' }}</p>
                                        </div>
                                        @if($acc->account_number)
                                            <button onclick="navigator.clipboard.writeText('{{ $acc->account_number }}'); alert('Nomor Rekening {{ $acc->account_number }} berhasil disalin!');" class="text-[10px] font-bold bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-400 px-3 py-1.5 rounded-md hover:bg-blue-200 dark:hover:bg-blue-800 transition flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                                Salin Norek
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @php
                        $waNumber = $business->phone ?? '';
                        if (str_starts_with($waNumber, '0')) {
                            $waNumber = '62' . substr($waNumber, 1);
                        }
                    @endphp

                    <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode('Halo Admin ' . $business->name . ', saya ingin konfirmasi pembayaran untuk tagihan atas nama ' . $customer->name) }}" target="_blank" class="w-full bg-green-500 hover:bg-green-600 text-white font-black py-3.5 rounded-xl shadow-md shadow-green-500/20 flex justify-center items-center gap-2 transition text-sm mt-3">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        Konfirmasi Pembayaran via WhatsApp
                    </a>
                </div>

            </div>
        </div>
    @endif
</div>