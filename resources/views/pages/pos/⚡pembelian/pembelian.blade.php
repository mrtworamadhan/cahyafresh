<div class="h-full flex flex-col p-4 animate-fade-in text-zinc-800 dark:text-zinc-200">
    
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-black text-teal-600 dark:text-teal-400 flex items-center gap-2">
            <x-heroicon-o-truck class="w-7 h-7" /> POS Pembelian (Restock)
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
        <div class="p-4 mb-4 bg-red-100 dark:bg-red-950 text-red-700 rounded-xl flex items-center gap-3 border border-red-200">
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
                <input wire:model.live.debounce.300ms="searchProduct" type="text" placeholder="Cari barang yang baru datang..." 
                       class="w-full pl-10 pr-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 focus:ring-2 focus:ring-teal-500 outline-none transition">
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 overflow-y-auto pb-4 pr-1">
                @forelse($products as $product)
                    <button wire:click="addToCart({{ $product->id }})" class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 p-3 rounded-xl flex flex-col items-center text-center hover:border-teal-500 dark:hover:border-teal-500 transition group shadow-sm">
                        <div class="w-16 h-16 bg-teal-50 dark:bg-teal-900/20 rounded-lg flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                            <x-heroicon-o-archive-box-arrow-down class="w-8 h-8 text-teal-500" />
                        </div>
                        <h3 class="text-sm font-semibold line-clamp-2">{{ $product->name }}</h3>
                    </button>
                @empty
                    <div class="col-span-full py-8 text-center text-zinc-500 dark:text-zinc-400">
                        <x-heroicon-o-inbox class="w-12 h-12 mx-auto mb-2 opacity-50" />
                        <p>Produk tidak ditemukan.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl flex flex-col h-full shadow-sm">
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 bg-teal-50 dark:bg-teal-900/10 rounded-t-xl shrink-0 flex flex-col gap-3">
                <div class="flex justify-between items-center">
                    <h2 class="font-bold flex items-center gap-2 text-teal-700 dark:text-teal-400">
                        <x-heroicon-o-truck class="w-5 h-5" /> Surat Terima Barang
                    </h2>
                    <span class="bg-teal-200 dark:bg-teal-800 text-teal-800 dark:text-teal-200 text-xs py-1 px-2 rounded-lg font-bold">
                        {{ count($cart) }} Jenis
                    </span>
                </div>
                
                <select wire:model.live="supplierId" class="w-full text-sm bg-white dark:bg-zinc-900 border border-teal-200 dark:border-teal-700/50 text-zinc-800 dark:text-zinc-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none transition font-semibold">
                    <option value="">-- Pilih Supplier / Pabrik --</option>
                    @if(isset($suppliers))
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            <div class="flex-1 overflow-y-auto p-4 flex flex-col gap-3">
                @forelse($cart as $index => $item)
                    <div class="flex flex-col gap-2 p-3 border border-zinc-200 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800/80 shadow-sm relative group">
                        <div class="flex justify-between items-start pr-6">
                            <h4 class="text-sm font-bold text-zinc-800 dark:text-zinc-100">{{ $item['name'] }}</h4>
                            <button wire:click="removeItem({{ $index }})" class="absolute right-3 top-3 text-zinc-400 hover:text-red-500 transition">
                                <x-heroicon-o-trash class="w-4 h-4" />
                            </button>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-1">
                            <div>
                                <label class="text-[10px] text-teal-600 font-bold uppercase tracking-wider mb-1 block">Satuan Belanja</label>
                                @if(count($item['available_units']) > 0)
                                    <select wire:change="changeCartUnit({{ $index }}, $event.target.value)" class="w-full px-2 py-1.5 text-xs font-bold bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-md focus:border-teal-500 outline-none text-zinc-800 dark:text-zinc-100">
                                        <option value="">Pilih Satuan</option>
                                        @foreach($item['available_units'] as $unit)
                                            <option value="{{ $unit['id'] }}" {{ $item['unit_id'] == $unit['id'] ? 'selected' : '' }}>{{ $unit['unit_name'] }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <div class="w-full text-xs bg-zinc-100 dark:bg-zinc-700 text-zinc-500 px-2 py-1.5 rounded-md font-semibold">Satuan Dasar</div>
                                @endif
                            </div>

                            <div>
                                <label class="text-[10px] text-teal-600 font-bold uppercase tracking-wider mb-1 block">Harga Beli / Satuan</label>
                                <input type="number" wire:model.live.debounce.500ms="cart.{{ $index }}.price" class="w-full px-2 py-1.5 text-xs font-bold bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-md focus:border-teal-500 outline-none text-zinc-800 dark:text-zinc-100">
                            </div>
                            <div>
                                <label class="text-[10px] text-teal-600 font-bold uppercase tracking-wider mb-1 block">Qty Masuk</label>
                                <input type="number" wire:model.live="cart.{{ $index }}.qty" class="w-full px-2 py-1.5 text-xs font-bold text-center bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-md focus:border-teal-500 outline-none">
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center h-full text-zinc-400">
                        <x-heroicon-o-archive-box class="w-12 h-12 mb-2 opacity-30" />
                        <p class="text-sm font-medium">Belum ada barang masuk</p>
                    </div>
                @endforelse
            </div>

            <div class="p-4 border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50 rounded-b-xl shrink-0">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-zinc-500 font-bold uppercase tracking-wider text-xs">Total Modal Keluar</span>
                    <span class="text-2xl font-black text-teal-600 dark:text-teal-400">Rp {{ number_format($total, 0, ',', '.') }}</span>
                </div>
                
                <button wire:click="openCheckout" class="w-full bg-teal-600 text-white font-bold py-3.5 rounded-xl hover:bg-teal-700 transition shadow-lg shadow-teal-500/20 flex justify-center items-center gap-2 disabled:opacity-50" {{ empty($cart) ? 'disabled' : '' }}>
                    <x-heroicon-o-clipboard-document-check class="w-6 h-6" />
                    Selesaikan Terima Barang
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL CHECKOUT PEMBELIAN -->
    @if($showCheckoutModal)
        <div class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center bg-zinc-900/70 backdrop-blur-sm transition-opacity p-0 sm:p-4">
            <div class="bg-zinc-50 dark:bg-zinc-900 rounded-t-3xl sm:rounded-3xl w-full max-w-lg shadow-2xl flex flex-col max-h-[90vh] animate-fade-in-up">
                
                <div class="p-5 border-b border-zinc-200 dark:border-zinc-700 flex justify-between items-center bg-white dark:bg-zinc-800 rounded-t-3xl sm:rounded-t-3xl shrink-0">
                    <div>
                        <h3 class="text-lg font-black text-zinc-800 dark:text-zinc-100 flex items-center gap-2">
                            <x-heroicon-o-truck class="w-6 h-6 text-teal-600" /> 
                            Konfirmasi Terima Barang
                        </h3>
                    </div>
                    <button wire:click="closeCheckout" class="p-2 bg-zinc-100 dark:bg-zinc-700 rounded-full text-zinc-500 hover:text-red-500 transition">
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-5 overflow-y-auto flex-1 flex flex-col gap-5">
                    
                    <div class="bg-teal-50 dark:bg-teal-900/20 p-4 rounded-2xl border border-teal-200 dark:border-teal-800/50">
                        <label class="text-[10px] font-bold text-teal-700 uppercase tracking-wider mb-1 block">Nomor Nota / Surat Jalan Supplier</label>
                        <input type="text" wire:model.defer="invoiceNumber" placeholder="Contoh: INV-SUP-001" class="w-full px-3 py-2 text-sm font-bold bg-white dark:bg-zinc-900 border border-teal-200 rounded-lg outline-none focus:ring-2 focus:ring-teal-500">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-1 block">Tipe Pembayaran</label>
                            <select wire:model.live="paymentStatus" class="w-full text-sm font-bold bg-zinc-50 border border-zinc-200 rounded-lg px-3 py-2 outline-none">
                                <option value="paid">Lunas Kasir (Tunai/TF)</option>
                                <option value="unpaid">Tempo / Hutang Pabrik</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-1 block">Ambil Uang Dari</label>
                            <select wire:model.defer="walletId" class="w-full text-sm font-bold bg-zinc-50 border border-zinc-200 rounded-lg px-3 py-2 outline-none" {{ $paymentStatus === 'unpaid' ? 'disabled' : '' }}>
                                <option value="">-- Pilih Rekening --</option>
                                @if(isset($wallets))
                                    @foreach($wallets as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-zinc-800 p-4 rounded-2xl border border-zinc-200 shadow-sm">
                        <div class="flex justify-between items-end">
                            <span class="text-xs font-bold uppercase tracking-wider text-zinc-500">Total Modal / Hutang</span>
                            <span class="text-2xl font-black text-teal-600">Rp {{ number_format($this->finalTotal, 0, ',', '.') }}</span>
                        </div>
                    </div>

                </div>

                <div class="p-5 border-t border-zinc-200 bg-white rounded-b-3xl shrink-0">
                    <button wire:click="submitPurchase" type="button" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-black py-4 rounded-2xl transition shadow-xl shadow-teal-500/30 flex justify-center items-center gap-2">
                        <x-heroicon-o-check-circle class="w-6 h-6" />
                        Simpan & Masukkan ke Stok Gudang
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($showSuccessModal)
        <div class="fixed inset-0 z-[110] flex items-center justify-center bg-zinc-900/80 backdrop-blur-sm p-4 animate-fade-in">
            <div class="bg-white dark:bg-zinc-800 rounded-3xl w-full max-w-sm shadow-2xl border border-zinc-200 dark:border-zinc-700 p-6 flex flex-col items-center text-center">
                
                <div class="w-16 h-16 bg-teal-50 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400 rounded-full flex items-center justify-center mb-4">
                    <x-heroicon-o-check-circle class="w-12 h-12" />
                </div>

                <h3 class="text-xl font-black text-zinc-800 dark:text-zinc-100 mb-1">Stok Berhasil Diterima!</h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-6">Barang telah masuk ke gudang dan rata-rata harga modal (HPP) telah diperbarui otomatis oleh sistem.</p>

                <button type="button" wire:click="closeSuccessModal" 
                        class="w-full bg-teal-600 text-white font-bold py-3.5 rounded-xl hover:bg-teal-700 transition shadow-lg shadow-teal-500/20 text-sm">
                    Tutup & Kembali
                </button>
            </div>
        </div>
    @endif
</div>