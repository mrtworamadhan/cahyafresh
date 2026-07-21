<div class="min-h-screen bg-zinc-100 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 font-sans flex flex-col relative" 
     x-data="{ activeTab: 'unpaid', showCommissionModal: false }">
    
    <div class="text-white p-6 rounded-b-3xl shadow-lg relative overflow-hidden shrink-0" 
         style="background-color: {{ $business->theme_color ?? '#2563eb' }};">
        
        <div class="relative z-10">             
            <div class="flex justify-between items-center gap-4">
                <div>
                    <h1 class="text-2xl font-black tracking-tight uppercase">{{ $business->name ?? 'SMART SUPPLY' }}</h1>             
                    <p class="text-white/80 text-sm sm:text-sm mt-0.5">Portal Tagihan & Riwayat Transaksi</p>  
                    <p class="text-xs font-italic text-white dark:text-white mt-1">
                        this app powered by 
                    </p>  
                    <p class="text-xs font-italic text-white dark:text-white mt-1">
                        <a href="https://salakatech.com" target="_blank" 
                        class="text-zinc-200 dark:text-zinc-200 font-black hover:underline inline-flex items-center gap-0.5 transition whitespace-nowrap">
                            Cahya Salaka Tech
                        </a> 
                        part of Cahya Triloka Group.
                    </p>                         
                </div>

                @if(isset($business) && $business->logo)
                    <img src="{{ asset('storage/' . $business->logo) }}" alt="Logo {{ $business->name }}" 
                         class="w-14 h-14 rounded-full object-contain border-2 border-white/30 shadow-md shrink-0 bg-white">
                @else
                    <div class="w-14 h-14 rounded-full bg-white/20 border-2 border-white/30 flex items-center justify-center shrink-0 shadow-md">
                        <x-heroicon-o-building-storefront class="w-7 h-7 text-white" />
                    </div>
                @endif
            </div>
            
            <div class="mt-6 bg-white/10 p-4 rounded-2xl backdrop-blur-sm border border-white/20">                 
                <p class="text-xs text-white/70 uppercase tracking-wider font-bold mb-1">Halo,</p>                 
                <h2 class="text-xl font-black">{{ $customer->name }}</h2>                 
                <p class="text-sm font-medium opacity-80">{{ $customer->phone ?? '-' }}</p>             
            </div>             
            
            <div class="mt-6 grid grid-cols-3 gap-3">                 
                <div class="bg-white/10 p-3 rounded-xl backdrop-blur-sm border border-white/20 text-center">                     
                    <p class="text-[10px] text-white/80 uppercase font-bold mb-1">Piutang</p>                     
                    <p class="text-sm font-black text-rose-200">Rp {{ number_format($totalPiutang, 0, ',', '.') }}</p>                 
                </div>                 
                <div class="bg-white/10 p-3 rounded-xl backdrop-blur-sm border border-white/20 text-center">                     
                    <p class="text-[10px] text-white/80 uppercase font-bold mb-1">Deposit</p>                     
                    <p class="text-sm font-black text-green-200">Rp {{ number_format($totalDeposit, 0, ',', '.') }}</p>                 
                </div>                 
                
                <div @click="showCommissionModal = true" class="bg-white/10 p-3 rounded-xl backdrop-blur-sm border border-white/20 text-center cursor-pointer hover:bg-white/20 hover:scale-105 transition active:scale-95">                     
                    <p class="text-[10px] text-white/80 uppercase font-bold mb-1 flex items-center justify-center gap-1">
                        Komisi <x-heroicon-s-information-circle class="w-3 h-3 text-amber-200" />
                    </p>                     
                    <p class="text-sm font-black text-amber-200">Rp {{ number_format($totalKomisi, 0, ',', '.') }}</p>                 
                </div>             
            </div>         
        </div>         
        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-white opacity-10 rounded-full blur-2xl"></div>     
    </div>     

    <div class="px-4 mt-6 shrink-0"> 
        <div class="flex p-1.5 bg-zinc-100 dark:bg-zinc-900/60 rounded-2xl overflow-x-auto gap-1 [&::-webkit-scrollbar]:hidden [scrollbar-width:none]"> 
            
            <button @click="activeTab = 'unpaid'"                      
                    :class="activeTab === 'unpaid' ? 'bg-white dark:bg-zinc-800 shadow-sm text-blue-600 dark:text-blue-400 font-black' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 font-semibold'" 
                    class="flex-1 flex items-center justify-center gap-1.5 whitespace-nowrap py-2.5 px-3.5 text-xs sm:text-sm rounded-xl transition-all duration-300 shrink-0">                 
                <x-heroicon-o-document-text class="w-4 h-4" />
                <span>Tagihan</span>
                <span :class="activeTab === 'unpaid' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600' : 'bg-zinc-200/60 dark:bg-zinc-800 text-zinc-500'" 
                      class="px-1.5 py-0.5 text-[10px] font-bold rounded-md transition-colors duration-300">
                    {{ count($unpaidOrders) }}
                </span>
            </button>             
            
            <button @click="activeTab = 'draft'"                      
                    :class="activeTab === 'draft' ? 'bg-white dark:bg-zinc-800 shadow-sm text-amber-600 dark:text-amber-400 font-black' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 font-semibold'" 
                    class="flex-1 flex items-center justify-center gap-1.5 whitespace-nowrap py-2.5 px-3.5 text-xs sm:text-sm rounded-xl transition-all duration-300 shrink-0">                 
                <x-heroicon-o-clock class="w-4 h-4" />
                <span>Disiapkan</span>
                <span :class="activeTab === 'draft' ? 'bg-amber-50 dark:bg-amber-900/30 text-amber-600' : 'bg-zinc-200/60 dark:bg-zinc-800 text-zinc-500'" 
                      class="px-1.5 py-0.5 text-[10px] font-bold rounded-md transition-colors duration-300">
                    {{ count($draftOrders) }}
                </span>
            </button>             
            
            <button @click="activeTab = 'paid'"                      
                    :class="activeTab === 'paid' ? 'bg-white dark:bg-zinc-800 shadow-sm text-green-600 dark:text-green-400 font-black' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 font-semibold'" 
                    class="flex-1 flex items-center justify-center gap-1.5 whitespace-nowrap py-2.5 px-3.5 text-xs sm:text-sm rounded-xl transition-all duration-300 shrink-0">                 
                <x-heroicon-o-archive-box class="w-4 h-4" />
                <span>Arsip</span>
                <span :class="activeTab === 'paid' ? 'bg-green-50 dark:bg-green-900/30 text-green-600' : 'bg-zinc-200/60 dark:bg-zinc-800 text-zinc-500'" 
                      class="px-1.5 py-0.5 text-[10px] font-bold rounded-md transition-colors duration-300">
                    {{ count($paidOrders) }}
                </span>
            </button>

            <button @click="activeTab = 'history'" 
                    :class="activeTab === 'history' ? 'bg-white dark:bg-zinc-800 shadow-sm text-zinc-800 dark:text-zinc-100 font-black' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 font-semibold'"
                    class="flex-1 flex items-center justify-center gap-1.5 whitespace-nowrap py-2.5 px-3.5 text-xs sm:text-sm rounded-xl transition-all duration-300 shrink-0">
                <x-heroicon-o-arrows-right-left class="w-4 h-4" />
                <span>Mutasi</span>
                <span :class="activeTab === 'history' ? 'bg-zinc-100 dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200' : 'bg-zinc-200/60 dark:bg-zinc-800 text-zinc-500'" 
                      class="px-1.5 py-0.5 text-[10px] font-bold rounded-md transition-colors duration-300">
                    {{ count($transactionHistory ?? []) }}
                </span>
            </button>  

            <button @click="activeTab = 'commission'"                      
                    :class="activeTab === 'commission' ? 'bg-white dark:bg-zinc-800 shadow-sm text-purple-600 dark:text-purple-400 font-black' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-200 font-semibold'" 
                    class="flex-1 flex items-center justify-center gap-1.5 whitespace-nowrap py-2.5 px-3.5 text-xs sm:text-sm rounded-xl transition-all duration-300 shrink-0">                 
                <x-heroicon-o-gift class="w-4 h-4" />
                <span>Komisi</span>
                <span :class="activeTab === 'commission' ? 'bg-purple-50 dark:bg-purple-900/30 text-purple-600' : 'bg-zinc-200/60 dark:bg-zinc-800 text-zinc-500'" 
                      class="px-1.5 py-0.5 text-[10px] font-bold rounded-md transition-colors duration-300">
                    {{ count($commissionHistory ?? []) }}
                </span>
            </button>       
        </div>     
    </div>     

    <div class="flex-1 min-h-0">
        <div x-show="activeTab === 'unpaid'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="p-4 space-y-4">         
            @forelse($unpaidOrders as $order)             
                <div x-data="{ expanded: false }" class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-rose-200 dark:border-rose-900/50 overflow-hidden">                 
                    <button @click="expanded = !expanded" class="w-full p-4 flex justify-between items-center text-left bg-rose-50/50 dark:bg-rose-900/10 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition">                     
                        <div>                         
                            <span class="inline-block px-2 py-1 bg-rose-100 dark:bg-rose-900 text-rose-600 dark:text-rose-400 text-[10px] font-black uppercase rounded mb-1">Tagihan Aktif</span>                         
                            <h3 class="font-bold text-sm">{{ $order->order_number }}</h3>                         
                            <p class="text-xs text-zinc-500">Estimasi Kirim: {{ $order->delivery_date ? \Carbon\Carbon::parse($order->delivery_date)->format('d M Y') : 'Menunggu Info' }}</p>                     
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
                <div class="text-center py-16 opacity-50">                 
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
                            @if($order->status === 'processing')
                                <span class="inline-block px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 text-[10px] font-black uppercase rounded mb-1">Sedang Dalam Perjalanan</span>
                            @else
                                <span class="inline-block px-2 py-1 bg-amber-100 dark:bg-amber-900 text-amber-600 dark:text-amber-400 text-[10px] font-black uppercase rounded mb-1">Sedang Disiapkan</span>
                            @endif
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
                            
                            <div class="mt-4 p-3 {{ $order->status === 'processing' ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-100 dark:border-blue-800/50 text-blue-800 dark:text-blue-300' : 'bg-amber-50 dark:bg-amber-900/20 border-amber-100 dark:border-amber-800/50 text-amber-800 dark:text-amber-300' }} rounded-xl border text-center">                             
                                <p class="text-xs font-bold">                                 
                                    {{ $order->status === 'processing' ? 'Pesanan Anda telah dikonfirmasi dan saat ini barang sedang dibawa oleh kurir menuju alamat Anda.' : 'Pesanan ini sedang direkap/disiapkan oleh tim gudang kami dan belum masuk ke tagihan.' }}
                                </p>                         
                            </div>                     
                        </div>                 
                    </div>             
                </div>         
            @empty             
                <div class="text-center py-16 opacity-50">                 
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
                            <p class="text-xs text-zinc-500">Besaran Nota: {{ \Carbon\Carbon::parse($order->delivery_date)->format('d M Y') }}</p>                     
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
                <div class="text-center py-16 opacity-50">                 
                    <x-heroicon-o-archive-box class="w-16 h-16 mx-auto mb-3" />                 
                    <p class="font-bold">Belum ada riwayat transaksi lunas.</p>             
                </div>         
            @endforelse     
        </div>
        <div x-show="activeTab === 'history'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="p-4 space-y-4">
            
            <div class="bg-zinc-50 dark:bg-zinc-900/40 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800/50">
                <h3 class="font-black text-zinc-700 dark:text-zinc-300 text-sm">Rekening Koran Pelanggan</h3>
                <p class="text-xs text-zinc-400 mt-0.5 font-medium">Memantau transparansi kronologis timbulnya tagihan belanja serta catatan cicilan dana yang masuk ke kasir.</p>
            </div>

            <div class="relative border-l-2 border-zinc-200 dark:border-zinc-700 ml-3.5 space-y-6 py-2">
                @forelse($transactionHistory as $trx)
                    <div class="relative pl-6">
                        
                        <span class="absolute -left-[11px] top-1 flex h-5 w-5 items-center justify-center rounded-full ring-4 ring-white dark:ring-zinc-900 
                            {{ data_get($trx, 'type') === 'tagihan' ? 'bg-rose-500 text-white' : 'bg-green-500 text-white' }}">
                            @if(data_get($trx, 'type') === 'tagihan')
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            @else
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            @endif
                        </span>

                        <div class="bg-white dark:bg-zinc-800 p-4 rounded-2xl border border-zinc-200 dark:border-zinc-700 shadow-xs flex justify-between items-center gap-4 transition hover:border-zinc-300 dark:hover:border-zinc-600">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <h4 class="font-bold text-sm text-zinc-800 dark:text-zinc-200">{{ data_get($trx, 'title') }}</h4>
                                </div>
                                <p class="text-[11px] text-zinc-400 font-medium">Tanggal: {{ \Carbon\Carbon::parse(data_get($trx, 'date'))->format('d M Y - H:i') }} WIB</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 font-medium italic mt-1">{{ data_get($trx, 'note') }}</p>
                            </div>

                            <div class="text-right shrink-0">
                                @if(data_get($trx, 'type') === 'tagihan')
                                    <span class="text-base font-black text-rose-600 dark:text-rose-500">
                                        +Rp {{ number_format(data_get($trx, 'amount'), 0, ',', '.') }}
                                    </span>
                                    <p class="text-[9px] font-black text-rose-500/70 uppercase tracking-wider mt-0.5">Hutang Bertambah</p>
                                @else
                                    <span class="text-base font-black text-green-600 dark:text-green-500">
                                        -Rp {{ number_format(data_get($trx, 'amount'), 0, ',', '.') }}
                                    </span>
                                    <p class="text-[9px] font-black text-green-500/70 uppercase tracking-wider mt-0.5">Mengurangi Hutang</p>
                                @endif
                            </div>
                        </div>

                    </div>
                @empty
                    <div class="text-center py-16 opacity-50 pl-6">
                        <x-heroicon-o-document-text class="w-12 h-12 mx-auto mb-2 text-zinc-400" />
                        <p class="font-bold text-sm">Belum ada riwayat mutasi tagihan.</p>
                        <p class="text-xs text-zinc-400 mt-0.5">Seluruh jejak transaksi invoice and cicilan Anda akan otomatis tercatat rapi di sini.</p>
                    </div>
                @endforelse
            </div>

        </div>
        <div x-show="activeTab === 'commission'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="p-4 space-y-4">         
            
            <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-xl border border-purple-200 dark:border-purple-800/50 flex justify-between items-center">
                <div>
                    <h3 class="font-black text-purple-700 dark:text-purple-400">Mutasi & Rincian Komisi</h3>
                    <p class="text-xs text-purple-600/70 dark:text-purple-500 font-medium">Memantau seluruh jejak bonus masuk dari referral serta riwayat pencairan dana tunai Anda.</p>
                </div>
            </div>

            @forelse($commissionHistory as $history)         
                <div x-data="{ expanded: false }" class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">                
                    <button @click="if(data_get($history, 'type') === 'in') { expanded = !expanded }" 
                            class="w-full p-4 flex justify-between items-center text-left transition {{ data_get($history, 'type') === 'in' ? 'hover:bg-purple-50/30 dark:hover:bg-purple-900/5 cursor-pointer' : 'cursor-default' }}">                     
                        <div>                                             
                            @if(data_get($history, 'type') === 'in')
                                <span class="inline-block px-2 py-0.5 bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-400 text-[10px] font-black uppercase rounded mb-1">Komisi Masuk</span>
                            @else
                                <span class="inline-block px-2 py-0.5 bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 text-[10px] font-black uppercase rounded mb-1">Pencairan Sukses</span>
                            @endif
                            
                            <h3 class="font-bold text-sm text-zinc-700 dark:text-zinc-300">{{ data_get($history, 'title') }}</h3>                     
                            <p class="text-xs text-zinc-400">Tanggal: {{ data_get($history, 'date') ? \Carbon\Carbon::parse(data_get($history, 'date'))->format('d M Y - H:i') : '-' }}</p>                     
                            @if(data_get($history, 'type') === 'in')
                                @foreach(data_get($history, 'orderItems', []) as $item)
                                    <p class="text-[11px] text-zinc-400 dark:text-zinc-500 font-medium mt-0.5 italic">
                                        <span class="font-bold text-purple-600 dark:text-purple-400">[{{ data_get($history, 'customer_name') }}]</span> 
                                        
                                        {{ data_get($item, 'product.name', 'Produk Terhapus') }} : 
                                        {{ data_get($item, 'qty_billed', 0) }}xRp {{ number_format(data_get($item, 'commission_per_unit', 0), 0, ',', '.') }}
                                    </p>
                                @endforeach
                            @else
                                <p class="text-[11px] text-zinc-400 dark:text-zinc-500 font-medium mt-0.5 italic">{{ data_get($history, 'note') }}</p>
                            @endif
                            
                        </div>                     
                        <div class="text-right flex flex-col items-end gap-1">                                             
                            @if(data_get($history, 'type') === 'in')
                                <span class="text-base font-black text-purple-600 dark:text-purple-400">+Rp {{ number_format(data_get($history, 'amount', 0), 0, ',', '.') }}</span>                         
                                <x-heroicon-o-chevron-down class="w-4 h-4 text-zinc-400 transition-transform duration-300" x-bind:class="expanded ? 'rotate-180' : ''" />                    
                            @else
                                <span class="text-base font-black text-red-600 dark:text-red-400">-Rp {{ number_format(data_get($history, 'amount', 0), 0, ',', '.') }}</span>
                            @endif
                        </div>                
                    </button>                    
                    
                    @if(data_get($history, 'type') === 'in')
                        <div x-show="expanded" x-collapse>                     
                            <div class="p-4 border-t border-zinc-100 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-900/20">                         
                                <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-2.5">Rincian Barang Belanjaan:</p>
                                
                                <div class="space-y-2">                                             
                                    @foreach(data_get($history, 'orderItems', []) as $item)                                                 
                                        <div class="flex justify-between items-center text-xs p-2 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-100 dark:border-zinc-700/50 shadow-xs">                                         
                                            <div>
                                                <p class="font-bold text-zinc-700 dark:text-zinc-300">{{ data_get($item, 'product.name', 'Produk Terhapus') }}</p>
                                            </div>
                                            <div class="text-right">
                                                <span class="text-[11px] font-medium text-zinc-400">Jumlah Beli: {{ data_get($item, 'qty_billed', 0) }}x</span>
                                            </div>                                     
                                        </div>                                             
                                    @endforeach                         
                                </div>                         
                            </div>                     
                        </div> 
                    @endif                    
                </div>         
            @empty             
                <div class="text-center py-16 opacity-50">                 
                    <x-heroicon-o-gift class="w-16 h-16 mx-auto mb-3 text-purple-400" />                 
                    <p class="font-bold">Belum ada aktivitas mutasi komisi.</p>              
                    <p class="text-xs text-zinc-400 mt-1">Seluruh riwayat pendapatan bonus komisi dan penarikan tunai Anda akan terekam otomatis di halaman ini.</p>
                </div>         
            @endforelse     
        </div>
    </div>

    <footer class="mt-auto py-8 text-center px-4 shrink-0">
        <p class="text-[11px] font-bold tracking-wide text-zinc-400 dark:text-zinc-500 leading-normal uppercase">
            app powered by 
            <a href="https://salakatech.com" target="_blank" 
               class="text-blue-500 dark:text-blue-400 font-black hover:underline inline-flex items-center gap-0.5 transition whitespace-nowrap">
                Cahya Salaka Tech
            </a> 
            part of Cahya Triloka Group.
        </p>
    </footer>

    @if($totalUnpaid > 0)
        <div class="h-28 shrink-0"></div>
    @endif

    <div x-show="showCommissionModal" x-cloak class="fixed inset-0 z-[100] flex items-end justify-center bg-zinc-900/60 backdrop-blur-sm animate-fade-in">
        <div @click.away="showCommissionModal = false" 
             class="bg-white dark:bg-zinc-800 w-full max-w-md rounded-t-3xl p-5 shadow-2xl flex flex-col max-h-[80vh] transition-transform transform"
             x-show="showCommissionModal"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full">
            
            <div class="flex justify-between items-center pb-4 border-b border-zinc-100 dark:border-zinc-700 shrink-0">
                <div>
                    <h3 class="text-base font-black text-zinc-800 dark:text-zinc-100">Buku Mutasi Komisi</h3>
                    <p class="text-xs text-zinc-400">Log transparan klaim masuk dan rilis pencairan dana.</p>
                </div>
                <button @click="showCommissionModal = false" class="p-2 bg-zinc-100 dark:bg-zinc-700 rounded-full text-zinc-500 hover:text-red-500 transition">
                    <x-heroicon-o-x-mark class="w-4 h-4" />
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto py-4 space-y-3 pr-1 [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                @forelse($commissionHistory as $history)
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-900/50 rounded-xl border border-zinc-100 dark:border-zinc-700/50 flex justify-between items-center gap-2">
                        <div class="flex items-start gap-2.5">
                            <!-- PERBAIKAN: Menggunakan data_get untuk 'type' -->
                            @if(data_get($history, 'type') == 'in')
                                <div class="p-2 bg-green-50 dark:bg-green-950/50 text-green-600 dark:text-green-400 rounded-lg shrink-0 mt-0.5">
                                    <x-heroicon-o-arrow-down-left class="w-4 h-4" />
                                </div>
                            @else
                                <div class="p-2 bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 rounded-lg shrink-0 mt-0.5">
                                    <x-heroicon-o-arrow-up-right class="w-4 h-4" />
                                </div>
                            @endif

                            <div>
                                <!-- PERBAIKAN: Menggunakan data_get untuk 'title' -->
                                <p class="text-xs font-bold text-zinc-700 dark:text-zinc-300 leading-tight">{{ data_get($history, 'title') }}</p>
                                
                                <!-- PERBAIKAN: Pengecekan aman untuk 'date' -->
                                <p class="text-[10px] text-zinc-400 mt-0.5">
                                    {{ data_get($history, 'delivery_date') ? \Carbon\Carbon::parse(data_get($history, 'delivery_date'))->format('d M Y, H:i') : '-' }}
                                </p>
                                
                                <!-- PERBAIKAN: Pengecekan aman untuk 'note' -->
                                @if(data_get($history, 'note'))
                                    <p class="text-[10px] text-zinc-500 dark:text-zinc-400 italic mt-1 bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 rounded inline-block">{{ data_get($history, 'note') }}</p>
                                @endif
                            </div>
                        </div>
                        
                        <div class="text-right shrink-0">
                            <!-- PERBAIKAN: Menggunakan data_get untuk 'amount' dengan default 0 -->
                            @if(data_get($history, 'type') == 'in')
                                <span class="text-sm font-black text-green-600 dark:text-green-400">+Rp {{ number_format((float) data_get($history, 'amount', 0), 0, ',', '.') }}</span>
                                <p class="text-[9px] font-bold uppercase tracking-wider text-zinc-400 mt-0.5">Terbuku</p>
                            @else
                                <span class="text-sm font-black text-rose-600 dark:text-rose-400">-Rp {{ number_format((float) data_get($history, 'amount', 0), 0, ',', '.') }}</span>
                                <p class="text-[9px] font-bold uppercase tracking-wider text-blue-500 mt-0.5">Rilis/Cair</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12 text-zinc-400 opacity-60">
                        <x-heroicon-o-gift class="w-10 h-10 mx-auto mb-2 opacity-40" />
                        <p class="text-xs font-bold">Belum ada aktivitas mutasi komisi.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    @if($totalUnpaid > 0)
         <div x-data="{ showPayment: false }" class="fixed bottom-0 left-0 w-full bg-white dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700 p-4 shadow-[0_-10px_40px_rgba(0,0,0,0.1)] rounded-t-3xl z-50 transition-all duration-300">
            <div class="max-w-md mx-auto">
                <div class="flex justify-between items-end mb-2">
                    <div>
                        <p class="text-[10px] uppercase font-black tracking-wider text-rose-500 mb-0.5 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 bg-rose-500 rounded-full animate-pulse"></span>
                            <span>Total Sisa Tagihan</span>
                        </p>
                        <h2 class="text-3xl font-black text-rose-600 dark:text-rose-500">Rp {{ number_format($totalUnpaid, 0, ',', '.') }}</h2>
                    </div>
                    <button @click="showPayment = !showPayment" class="flex items-center gap-1 text-xs font-bold text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 px-3 py-2 rounded-xl transition hover:bg-blue-100">
                        <span x-text="showPayment ? 'Tutup' : 'Cara Bayar'"></span>
                        <x-heroicon-o-chevron-up class="w-4 h-4 transition-transform duration-300" x-bind:class="showPayment ? 'rotate-180' : ''" />
                    </button>
                </div>
                
                <div x-show="showPayment" x-collapse class="pt-3 border-t border-zinc-100 dark:border-zinc-700 mt-2" style="display: none;">
                    @if(count($wallets) > 0)
                        <div class="mb-4 p-3 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700">
                            <p class="text-xs font-bold text-zinc-500 mb-2">Instruksi Transfer ke Rekening Toko:</p>
                            
                            <div class="flex flex-col gap-2 max-h-44 overflow-y-auto pr-1 [&::-webkit-scrollbar]:w-1 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:bg-zinc-200 dark:[&::-webkit-scrollbar-thumb]:bg-zinc-700 [&::-webkit-scrollbar-thumb]:rounded-full">
                                @foreach($wallets as $acc)
                                    <div class="flex justify-between items-center bg-white dark:bg-zinc-800 p-2.5 rounded-lg border border-zinc-200 dark:border-zinc-700 shadow-xs">
                                        <div>
                                            <p class="text-xs font-black text-zinc-800 dark:text-zinc-200">{{ $acc->name }}</p>
                                            <p class="text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400 mt-0.5">{{ $acc->account_number ?? '-' }}</p>
                                        </div>
                                        @if($acc->account_number)
                                            <button onclick="navigator.clipboard.writeText('{{ $acc->account_number }}'); alert('Nomor Rekening {{ $acc->account_number }} berhasil disalin!');" class="text-[10px] font-bold bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-400 px-2.5 py-1.5 rounded-md hover:bg-blue-200 dark:hover:bg-blue-800 transition flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                                <span>Salin</span>
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
                        
                        $listNotaText = collect($unpaidOrders)->pluck('order_number')->implode(', ');
                        $templatePesan = "Halo Admin *" . $business->name . "*,\n\nSaya ingin konfirmasi pembayaran cicilan atas nama *" . $customer->name . "*.\n\n- *Total Sisa Tagihan:* Rp " . number_format($totalUnpaid, 0, ',', '.') . "\n- *Daftar Nota:* " . $listNotaText . "\n\nBerikut saya lampirkan bukti transfernya ya, mohon dicek. Terima kasih!";
                    @endphp
                    
                    <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode($templatePesan) }}" target="_blank" class="w-full bg-green-500 hover:bg-green-600 text-white font-black py-3.5 rounded-xl shadow-md shadow-green-500/20 flex justify-center items-center gap-2 transition text-sm mt-3 animate-pulse">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        <span>Kirim Bukti Pembayaran ke WA</span>
                    </a>
                </div>
            </div>
         </div>
    @endif
</div>