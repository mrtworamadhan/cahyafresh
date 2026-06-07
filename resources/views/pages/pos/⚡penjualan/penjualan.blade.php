<div style="--theme-color: {{ $themeColor }};" class="h-full flex flex-col p-4 animate-fade-in text-zinc-800 dark:text-zinc-200">
    
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-black text-[var(--theme-color)] flex items-center gap-2">
            <x-heroicon-o-shopping-cart class="w-7 h-7" /> POS Penjualan
        </h2>
        <div class="flex items-center gap-2">
            <button x-data="{ isDark: document.documentElement.classList.contains('dark') }" 
                @click="isDark = !isDark; isDark ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark'); localStorage.setItem('pos-theme', isDark ? 'dark' : 'light');"
                class="p-2.5 bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700">
                <x-heroicon-o-moon x-show="!isDark" class="w-5 h-5" />
                <x-heroicon-o-sun x-show="isDark" class="w-5 h-5 text-yellow-400" style="display: none;" />
            </button>
        </div>
    </div>

    @if (session()->has('error'))
        <div class="p-4 mb-4 bg-red-100 dark:bg-red-950/30 text-red-700 dark:text-red-400 rounded-xl flex items-center gap-3 border border-red-200 dark:border-red-800">
            <x-heroicon-o-x-circle class="w-6 h-6 shrink-0" />
            <p class="text-sm font-bold">{{ session('error') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 flex-1 overflow-hidden">
        
        <div class="lg:col-span-2 flex flex-col gap-4 overflow-hidden">
            <div class="relative shrink-0">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <x-heroicon-o-magnifying-glass class="w-5 h-5 text-zinc-400" />
                </div>
                <input wire:model.live.debounce.300ms="searchProduct" type="text" placeholder="Cari nama barang atau scan barcode..." 
                        class="w-full pl-10 pr-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 focus:ring-2 focus:ring-[var(--theme-color)] outline-none transition">
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 overflow-y-auto pb-4 pr-1">
                @forelse($products as $product)
                    <button wire:click="addToCart({{ $product->id }})" class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 p-3 rounded-xl flex flex-col items-center text-center hover:border-[var(--theme-color)] dark:hover:border-[var(--theme-color)] transition group shadow-sm">
                        <div class="w-16 h-16 bg-zinc-100 dark:bg-zinc-700 rounded-lg flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                            <x-heroicon-o-cube class="w-8 h-8 text-zinc-400" />
                        </div>
                        <h3 class="text-sm font-semibold line-clamp-2">{{ $product->name }}</h3>
                        <p class="text-xs text-[var(--theme-color)] font-bold mt-1">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</p>
                    </button>
                @empty
                    <div class="col-span-full py-8 text-center text-zinc-500 dark:text-zinc-400">
                        <x-heroicon-o-inbox class="w-12 h-12 mx-auto mb-2 opacity-50" />
                        <p>Produk tidak ditemukan.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl flex flex-col h-full overflow-hidden shadow-sm">
            
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 rounded-t-xl shrink-0 flex flex-col gap-3">
                <div class="flex justify-between items-center">
                    <h2 class="font-bold flex items-center gap-2">
                        <x-heroicon-o-shopping-cart class="w-5 h-5 text-[var(--theme-color)]" /> Keranjang
                    </h2>
                    <span class="bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-xs py-1 px-2 rounded-lg font-bold">
                        {{ count($cart) }} Item
                    </span>
                </div>
                
                <select wire:model.live="customerId" class="w-full text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[var(--theme-color)] outline-none transition font-semibold">
                    <option value="">-- Pilih Pelanggan Umum --</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->phone ?? 'Tanpa No. HP' }})</option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1 min-h-0 overflow-y-auto p-4 flex flex-col gap-3">
                @forelse($cart as $index => $item)
                    <div class="flex flex-col gap-3 p-3 border border-zinc-200 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800/80 shadow-sm relative group">
                        <div class="flex justify-between items-start pr-6">
                            <h4 class="text-sm font-bold line-clamp-2 text-zinc-800 dark:text-zinc-100">{{ $item['name'] }}</h4>
                            <button wire:click="removeItem({{ $index }})" class="absolute right-3 top-3 text-zinc-400 hover:text-red-500 transition"><x-heroicon-o-trash class="w-4 h-4" /></button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-2">
                            <div class="flex flex-col gap-2">
                                <div>
                                    <label class="text-[10px] text-zinc-500 font-bold uppercase tracking-wider mb-1 block">Satuan</label>
                                    @if(count($item['available_units']) > 0)
                                        <select wire:change="changeCartUnit({{ $index }}, $event.target.value)" class="w-full text-xs bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-md py-1.5 px-2 outline-none">
                                            <option value="">Satuan Dasar</option>
                                            @foreach($item['available_units'] as $unit)
                                                <option value="{{ $unit['id'] }}" {{ $item['unit_id'] == $unit['id'] ? 'selected' : '' }}>{{ $unit['unit_name'] }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <div class="w-full text-xs bg-zinc-100 dark:bg-zinc-700 text-zinc-500 px-2 py-1.5 rounded-md font-semibold">Satuan Dasar</div>
                                    @endif
                                </div>
                                <div>
                                    <label class="text-[10px] text-zinc-500 font-bold uppercase tracking-wider mb-1 block">Harga (Edit)</label>
                                    <input type="number" wire:model.live.debounce.500ms="cart.{{ $index }}.price" wire:change="validateCart({{ $index }})" class="w-full px-2 py-1.5 text-xs font-bold bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 rounded-md">
                                </div>
                            </div>
                            <div class="flex flex-col gap-2">
                                <div>
                                    <label class="text-[10px] text-[var(--theme-color)] font-bold uppercase tracking-wider mb-1 block">Qty Bayar</label>
                                    <div class="flex items-center gap-1">
                                        <button wire:click="decreaseQty({{ $index }})" class="w-7 h-7 flex items-center justify-center rounded-md bg-zinc-100 text-zinc-600 hover:bg-red-100 transition"><x-heroicon-o-minus class="w-3 h-3" /></button>
                                        <input type="number" wire:model.live="cart.{{ $index }}.qty_billed" wire:change="validateCart({{ $index }})" class="w-full text-center font-bold text-sm bg-zinc-50 border border-zinc-200 rounded-md py-1">
                                        <button wire:click="increaseQty({{ $index }})" class="w-7 h-7 flex items-center justify-center rounded-md bg-[var(--theme-color)] text-white hover:opacity-80 transition"><x-heroicon-o-plus class="w-3 h-3" /></button>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-[10px] text-green-500 font-bold uppercase tracking-wider mb-1 block">Qty Bonus (Free)</label>
                                    <input type="number" wire:model.live="cart.{{ $index }}.qty_bonus" class="w-full px-2 py-1.5 text-xs font-bold text-center bg-green-50 border border-green-200 text-green-700 rounded-md">
                                </div>
                            </div>
                            <div class="flex flex-col gap-2">
                                <div>
                                    <label class="text-[10px] text-amber-500 font-bold uppercase tracking-wider mb-1 block">Komisi / Pcs</label>
                                    <input type="number" wire:model.live.debounce.500ms="cart.{{ $index }}.commission_per_unit" class="w-full px-2 py-1.5 text-xs font-bold bg-amber-50 border border-amber-200 text-amber-700 rounded-md">
                                </div>
                                <div class="pt-2">
                                    <p class="text-[9px] text-zinc-400 font-bold uppercase tracking-wider">Total Komisi</p>
                                    <p class="text-xs font-bold text-amber-600">Rp {{ number_format(($item['commission_per_unit'] ?? 0) * $item['qty_billed'], 0, ',', '.') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2 pt-2 border-t border-zinc-100 flex justify-between items-center">
                            <span class="text-[10px] text-zinc-400 font-bold uppercase">Subtotal</span>
                            <span class="text-sm font-black text-zinc-800 dark:text-zinc-100">Rp {{ number_format($item['price'] * $item['qty_billed'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center h-full text-zinc-400">
                        <x-heroicon-o-shopping-bag class="w-12 h-12 mb-2 opacity-30" />
                        <p class="text-sm font-medium">Keranjang masih kosong</p>
                    </div>
                @endforelse
            </div>

            <div class="p-4 border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50 rounded-b-xl shrink-0">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-zinc-500 font-bold uppercase tracking-wider text-xs">Grand Total</span>
                    <span class="text-2xl font-black text-[var(--theme-color)]">Rp {{ number_format($total, 0, ',', '.') }}</span>
                </div>
                <button wire:click="openCheckout" class="w-full bg-[var(--theme-color)] text-white font-bold py-3.5 rounded-xl hover:opacity-90 transition shadow-lg shadow-blue-500/20 flex justify-center items-center gap-2 disabled:opacity-50" {{ empty($cart) ? 'disabled' : '' }}>
                    <x-heroicon-o-check-circle class="w-6 h-6" /> Lanjut Pembayaran
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL CHECKOUT PENJUALAN -->
    @if($showCheckoutModal)
        <div class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center bg-zinc-900/70 backdrop-blur-sm transition-opacity p-0 sm:p-4">
            <div class="bg-zinc-50 dark:bg-zinc-900 rounded-t-3xl sm:rounded-3xl w-full max-w-lg shadow-2xl flex flex-col max-h-[90vh] animate-fade-in-up">
                
                <div class="p-5 border-b border-zinc-200 dark:border-zinc-700 flex justify-between items-center bg-white dark:bg-zinc-800 rounded-t-3xl sm:rounded-t-3xl shrink-0">
                    <div>
                        <h3 class="text-lg font-black text-zinc-800 dark:text-zinc-100 flex items-center gap-2">
                            <x-heroicon-o-banknotes class="w-6 h-6 text-[var(--theme-color)]" /> 
                            Selesaikan Pembayaran
                        </h3>
                        <p class="text-xs text-zinc-500 font-medium">Konfirmasi pesanan dan atur diskon/komisi.</p>
                    </div>
                    <button wire:click="closeCheckout" class="p-2 bg-zinc-100 dark:bg-zinc-700 rounded-full text-zinc-500 hover:text-red-500 transition">
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-5 overflow-y-auto flex-1 flex flex-col gap-5">
                    
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-1 block">Tipe Pembayaran</label>
                            <select wire:model.live="paymentStatus" class="w-full text-sm font-bold bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-200 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-[var(--theme-color)]">
                                <option value="paid">Lunas</option>
                                <option value="partial">Bayar Sebagian</option>
                                <option value="unpaid">Catat Hutang</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-1 block">Hubungkan PO</label>
                            <select wire:model.defer="poBatchId" class="w-full text-sm font-bold bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-200 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-[var(--theme-color)]">
                                <option value="">-- Non-PO --</option>
                                @if(isset($poBatches))
                                    @foreach($poBatches as $po)
                                        <option value="{{ $po->id }}">{{ $po->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-[var(--theme-color)] uppercase tracking-wider mb-1 block">Tgl Kirim / Tgl Diambil</label>
                            <input type="date" wire:model.defer="deliveryDate" class="w-full text-sm font-bold bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/50 text-blue-700 dark:text-blue-400 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-[var(--theme-color)]">
                        </div>
                    </div>

                    <div class="bg-white dark:bg-zinc-800 p-4 rounded-2xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-bold text-zinc-500">Subtotal Belanja</span>
                            <span class="text-sm font-bold text-zinc-800 dark:text-zinc-200">Rp {{ number_format($total, 0, ',', '.') }}</span>
                        </div>
                        
                        <div class="flex justify-between items-center pb-3 border-b border-dashed border-zinc-300 dark:border-zinc-600 mb-3" x-data="{ raw: @entangle('discount').live }">
                            <span class="text-sm font-bold text-rose-500">Diskon Pelanggan</span>
                            <div class="w-1/2 relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-rose-400">Rp</span>
                                <input type="text" 
                                    :value="new Intl.NumberFormat('id-ID').format(raw || 0)"
                                    @input="
                                        let clean = $event.target.value.replace(/\D/g, '');
                                        raw = clean ? parseInt(clean) : 0;
                                        $el.value = new Intl.NumberFormat('id-ID').format(raw);
                                    "
                                    class="w-full pl-8 pr-3 py-1.5 text-right text-sm font-bold text-rose-600 bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800/50 rounded-lg outline-none focus:ring-2 focus:ring-rose-500">
                            </div>
                        </div>

                        <div class="flex justify-between items-end">
                            <span class="text-xs font-bold uppercase tracking-wider text-zinc-500">Tagihan Akhir (Grand Total)</span>
                            <span class="text-2xl font-black text-[var(--theme-color)]">Rp {{ number_format($this->finalTotal, 0, ',', '.') }}</span>
                            @if($deliveryType === 'delivery' && $shippingFeeBilled > 0)
                                <div class="flex justify-between items-center pb-3 border-b border-dashed border-zinc-300 dark:border-zinc-600 mb-3 animate-fade-in-up">
                                    <span class="text-sm font-bold text-blue-500">Ongkos Kirim</span>
                                    <span class="text-sm font-bold text-blue-600 dark:text-blue-400">+Rp {{ number_format($shippingFeeBilled, 0, ',', '.') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="bg-amber-50 dark:bg-amber-900/20 p-4 rounded-2xl border border-amber-200 dark:border-amber-800/50 transition-all">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model.live="applyCommission" class="w-5 h-5 text-amber-500 rounded border-amber-300 focus:ring-amber-500 dark:border-amber-700 dark:bg-zinc-800 transition">
                            <div>
                                <h4 class="text-sm font-bold text-amber-800 dark:text-amber-400 flex items-center gap-2">
                                    <x-heroicon-s-gift class="w-5 h-5 text-amber-500 shrink-0" />
                                    Berikan Komisi / Cashback
                                </h4>
                            </div>
                        </label>

                        @if($applyCommission)
                            <div class="space-y-3 pt-4 mt-3 border-t border-amber-200/50 dark:border-amber-800/50 animate-fade-in-up">
                                <div>
                                    <label class="text-[10px] font-bold text-amber-700 dark:text-amber-500 uppercase tracking-wider mb-1 block">Siapa Penerima Komisinya?</label>
                                    <select wire:model.defer="commissionRecipientId" class="w-full text-sm bg-white dark:bg-zinc-900 border border-amber-200 dark:border-amber-700/50 text-zinc-800 dark:text-zinc-200 rounded-lg px-3 py-2 outline-none focus:ring-1 focus:ring-amber-500 font-semibold">
                                        <option value="">-- Pilih Manual / Tanpa Referal --</option>
                                        @foreach($customers as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }} {{ $c->id == $commissionRecipientId ? '⭐' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div x-data="{ raw: @entangle('commission').live }">
                                        <label class="text-[10px] font-bold text-amber-700 dark:text-amber-500 uppercase tracking-wider mb-1 block">Total Komisi Keluar</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-amber-600/50">Rp</span>
                                            <input type="text" 
                                                :value="new Intl.NumberFormat('id-ID').format(raw || 0)"
                                                @input="
                                                    let clean = $event.target.value.replace(/\D/g, '');
                                                    raw = clean ? parseInt(clean) : 0;
                                                    $el.value = new Intl.NumberFormat('id-ID').format(raw);
                                                "
                                                class="w-full pl-8 pr-3 py-2 text-sm font-bold dark:text-white bg-white dark:bg-zinc-900 border border-amber-200 dark:border-amber-700/50 rounded-lg outline-none focus:ring-2 focus:ring-amber-500">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-bold text-amber-700 dark:text-amber-500 uppercase tracking-wider mb-1 block">Catatan Opsional</label>
                                        <input type="text" wire:model.defer="commissionNote" placeholder="Catatan internal..." class="w-full px-3 py-2 text-sm dark:text-white font-semibold bg-white dark:bg-zinc-900 border border-amber-200 dark:border-amber-700/50 rounded-lg outline-none focus:ring-2 focus:ring-amber-500">
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-2xl border border-blue-200 dark:border-blue-800/50 transition-all">
                        <div class="flex items-start gap-3 mb-3">
                            <x-heroicon-s-truck class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" />
                            <div class="w-full">
                                <h4 class="text-sm font-bold text-blue-800 dark:text-blue-400">Metode Pengiriman</h4>
                                <p class="text-[10px] text-blue-600 dark:text-blue-500/80 font-medium mb-3">Pilih apakah barang diambil atau dikirim via kurir.</p>
                                
                                <div class="flex gap-2">
                                    <button type="button" wire:click="$set('deliveryType', 'pickup')" class="flex-1 py-2 rounded-xl text-xs font-bold border transition-colors shadow-sm {{ $deliveryType === 'pickup' ? 'bg-blue-500 text-white border-blue-600' : 'bg-white dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50' }}">Ambil di Toko</button>
                                    <button type="button" wire:click="$set('deliveryType', 'delivery')" class="flex-1 py-2 rounded-xl text-xs font-bold border transition-colors shadow-sm {{ $deliveryType === 'delivery' ? 'bg-blue-500 text-white border-blue-600' : 'bg-white dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50' }}">Kirim Kurir</button>
                                </div>
                            </div>
                        </div>

                        @if($deliveryType === 'delivery')
                            <div class="space-y-3 pt-4 mt-3 border-t border-blue-200/50 dark:border-blue-800/50 animate-fade-in-up">
                                <div>
                                    <label class="text-[10px] font-bold text-blue-700 dark:text-blue-500 uppercase tracking-wider mb-1 block">Pilih Armada / Ekspedisi</label>
                                    <select wire:model.defer="courierId" class="w-full px-3 py-2 text-sm font-semibold bg-white dark:bg-zinc-900 border border-blue-200 dark:border-blue-700/50 text-zinc-800 dark:text-zinc-200 rounded-lg outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">-- Pilih Kurir / Pihak Ketiga --</option>
                                        @if(isset($couriers))
                                            @foreach($couriers as $courier)
                                                <option value="{{ $courier->id }}">
                                                    {{ $courier->name }} 
                                                    {{ $courier->type === 'external' ? '(Eksternal)' : '' }}
                                                    {{ $courier->vehicle_plate ? ' - ' . $courier->vehicle_plate : '' }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div x-data="{ raw: @entangle('shippingFeeBilled').live }">
                                        <label class="text-[10px] font-bold text-blue-700 dark:text-blue-500 uppercase tracking-wider mb-1 block">Ongkir Ditagih (Ke Konsumen)</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-blue-600/50">Rp</span>
                                            <input type="text" 
                                                :value="new Intl.NumberFormat('id-ID').format(raw || 0)"
                                                @input="
                                                    let clean = $event.target.value.replace(/\D/g, '');
                                                    raw = clean ? parseInt(clean) : 0;
                                                    $el.value = new Intl.NumberFormat('id-ID').format(raw);
                                                "
                                                class="w-full pl-8 pr-3 py-2 text-sm font-bold bg-white dark:bg-zinc-900 border border-blue-200 dark:border-blue-700/50 rounded-lg outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>
                                    </div>
                                    
                                    <div x-data="{ raw: @entangle('shippingCostActual').live }">
                                        <label class="text-[10px] font-bold text-blue-700 dark:text-blue-500 uppercase tracking-wider mb-1 block">Biaya Riil (Keluar Dari Laci)</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-blue-600/50">Rp</span>
                                            <input type="text" 
                                                :value="new Intl.NumberFormat('id-ID').format(raw || 0)"
                                                @input="
                                                    let clean = $event.target.value.replace(/\D/g, '');
                                                    raw = clean ? parseInt(clean) : 0;
                                                    $el.value = new Intl.NumberFormat('id-ID').format(raw);
                                                "
                                                class="w-full pl-8 pr-3 py-2 text-sm font-bold bg-white dark:bg-zinc-900 border border-blue-200 dark:border-blue-700/50 rounded-lg outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>
                                    </div>
                                </div>
                                <p class="text-[9px] font-bold text-blue-600/80 italic leading-tight">*Catatan: Ongkir ditagih akan menambah Grand Total. Biaya riil akan langsung memotong dompet Kasir sbg operasional.</p>
                            </div>
                        @endif
                    </div>

                    <div class="bg-white dark:bg-zinc-800 p-4 rounded-2xl border border-zinc-200 dark:border-zinc-700 shadow-sm space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-1 block">Masuk ke Dompet</label>
                                <select wire:model.defer="walletId" class="w-full text-sm font-bold bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-200 rounded-lg px-3 py-2.5 outline-none focus:ring-2 focus:ring-[var(--theme-color)]" {{ $paymentStatus === 'unpaid' ? 'disabled' : '' }}>
                                    <option value="">-- Pilih Rekening --</option>
                                    @foreach($wallets as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div x-data="{ raw: @entangle('paymentAmount').live }">
                                <label class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-1 block">Uang Dibayar (Rp)</label>
                                <input type="text" 
                                    :value="new Intl.NumberFormat('id-ID').format(raw || 0)"
                                    @input="
                                        let clean = $event.target.value.replace(/\D/g, '');
                                        raw = clean ? parseInt(clean) : 0;
                                        $el.value = clean ? new Intl.NumberFormat('id-ID').format(clean) : '';
                                    "
                                    class="w-full px-3 py-2.5 text-base font-black bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg outline-none focus:ring-2 focus:ring-[var(--theme-color)] text-[var(--theme-color)]"
                                    {{ $paymentStatus === 'unpaid' ? 'disabled' : '' }}>
                            </div>
                        </div>

                        @php
                            $kembalian = (float)$paymentAmount - (float)$this->finalTotal;
                        @endphp
                        
                        <div class="flex justify-between items-center p-3 rounded-xl {{ $kembalian >= 0 ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' }}">
                            <span class="text-sm font-bold {{ $kembalian >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                                {{ $paymentStatus === 'unpaid' ? 'Piutang Full (Hutang)' : ($kembalian >= 0 ? 'Kembalian' : 'Sisa Hutang (Piutang)') }}
                            </span>
                            <span class="text-lg font-black {{ $kembalian >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                                Rp {{ number_format(abs($kembalian), 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="p-5 border-t border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 rounded-b-3xl sm:rounded-b-3xl shrink-0">
                    <button wire:click="submitOrder" type="button" class="w-full bg-[var(--theme-color)] text-white font-black py-4 rounded-2xl hover:opacity-90 transition shadow-xl shadow-blue-500/30 flex justify-center items-center gap-2">
                        <x-heroicon-o-printer class="w-6 h-6" />
                        Simpan Transaksi & Cetak Nota
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL SUCCESS PENJUALAN -->
    @if($showSuccessModal)
        <div class="fixed inset-0 z-[110] flex items-center justify-center bg-zinc-900/80 backdrop-blur-sm p-4 animate-fade-in">
            <div class="bg-white dark:bg-zinc-800 rounded-3xl w-full max-w-sm shadow-2xl border border-zinc-200 dark:border-zinc-700 p-6 flex flex-col items-center text-center animate-fade-in-up">
                
                <div class="w-16 h-16 bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-full flex items-center justify-center mb-4">
                    <x-heroicon-o-check-circle class="w-12 h-12" />
                </div>

                <h3 class="text-xl font-black text-zinc-800 dark:text-zinc-100 mb-1">Transaksi Berhasil!</h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-6">Nomor Nota: <span class="font-bold text-zinc-700 dark:text-zinc-300">{{ $latestOrderNumber }}</span></p>

                <div class="w-full flex flex-col gap-2.5 mb-6" x-data="{ copied: false }">
                    <a href="/invoice/{{ $latestOrderNumber }}/resi" target="_blank"
                       class="w-full py-3 px-4 bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 dark:hover:bg-blue-800/50 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800 rounded-xl text-sm font-bold flex items-center justify-center gap-2 transition shadow-sm">
                        <x-heroicon-o-qr-code class="w-4 h-4" />
                        Cetak Resi Pengiriman (QR)
                    </a>
                    
                    <a href="{{ $waLink }}" target="_blank"
                       class="w-full py-3 px-4 bg-green-500 hover:bg-green-600 text-white rounded-xl text-sm font-bold flex items-center justify-center gap-2 transition shadow-md shadow-green-500/20">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        Kirim via WhatsApp
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
                            class="w-full py-3 px-4 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-xl text-sm font-bold flex items-center justify-center gap-2 transition">
                        <div x-show="!copied" class="flex items-center gap-2">
                            <x-heroicon-o-share class="w-4 h-4 text-[var(--theme-color)]" />
                            <span>Salin Link Invoice (WA)</span>
                        </div>
                        <div x-show="copied" class="flex items-center gap-2 text-green-600 dark:text-green-400" style="display: none;">
                            <x-heroicon-o-check class="w-4 h-4" />
                            <span>Tautan Berhasil Disalin!</span>
                        </div>
                    </button>

                    <a href="/invoice/{{ $latestOrderNumber }}/print" target="_blank"
                       class="w-full py-3 px-4 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-xl text-sm font-bold flex items-center justify-center gap-2 transition">
                        <x-heroicon-o-printer class="w-4 h-4 text-zinc-500" />
                        Cetak Nota Thermal
                    </a>

                    <a href="{{ route('invoice.print-batch', $latestOrderNumber) }}" 
                        target="_blank"
                        class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold flex items-center justify-center gap-2 transition mt-2">
                            <x-heroicon-o-printer class="w-4 h-4 text-white" />
                            Cetak SJ & Invoice
                    </a>

                    <a href="/invoice/{{ $latestOrderNumber }}/download" target="_blank"
                       class="w-full py-3 px-4 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-xl text-sm font-bold flex items-center justify-center gap-2 transition">
                        <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-zinc-500" />
                        Unduh Berkas PDF
                    </a>

                </div>

                <button type="button" wire:click="closeSuccessModal" 
                        class="w-full bg-[var(--theme-color)] text-white font-bold py-3.5 rounded-xl hover:opacity-90 transition shadow-lg shadow-blue-500/20 text-sm">
                    Mulai Transaksi Baru
                </button>

            </div>
        </div>
    @endif
</div>