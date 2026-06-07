<div class="h-full flex flex-col p-4 animate-fade-in text-zinc-800 dark:text-zinc-200">
    
    <div class="flex justify-between items-center mb-6 shrink-0">
        <div>
            <h2 class="text-2xl font-black text-amber-600 dark:text-amber-500 flex items-center gap-2">
                <x-heroicon-o-banknotes class="w-7 h-7" /> Keuangan & Operasional
            </h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 font-medium mt-1">Kelola arus kas keluar-masuk & rekapan shift kasir hari ini.</p>
        </div>
        
        <button x-data="{ isDark: document.documentElement.classList.contains('dark') }" 
            @click="isDark = !isDark; isDark ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark'); localStorage.setItem('pos-theme', isDark ? 'dark' : 'light');"
            class="p-2.5 bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 hidden sm:block">
            <x-heroicon-o-moon x-show="!isDark" class="w-5 h-5" />
            <x-heroicon-o-sun x-show="isDark" class="w-5 h-5 text-yellow-400" style="display: none;" />
        </button>
    </div>

    <div class="flex flex-col lg:flex-row gap-6 flex-1 overflow-hidden">
        
        <div class="flex-1 flex flex-col h-full overflow-hidden" x-data="{ activeTab: 'operasional' }">
            
            <div class="flex bg-white dark:bg-zinc-800 p-1.5 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 w-full mb-4 shrink-0">
                <button @click="activeTab = 'operasional'" :class="activeTab === 'operasional' ? 'bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-400 font-bold shadow-sm' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'" class="flex-1 py-2.5 px-2 sm:px-4 rounded-lg text-[11px] sm:text-sm transition-all flex justify-center items-center gap-1 sm:gap-2">
                    <x-heroicon-o-calculator class="w-4 h-4 sm:w-5 sm:h-5" /> Pengeluaran
                </button>
                <button @click="activeTab = 'piutang'" :class="activeTab === 'piutang' ? 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-400 font-bold shadow-sm' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'" class="flex-1 py-2.5 px-2 sm:px-4 rounded-lg text-[11px] sm:text-sm transition-all flex justify-center items-center gap-1 sm:gap-2">
                    <x-heroicon-o-arrow-down-on-square class="w-4 h-4 sm:w-5 sm:h-5" /> Terima Piutang
                </button>
                <button @click="activeTab = 'hutang'" :class="activeTab === 'hutang' ? 'bg-rose-100 dark:bg-rose-900/50 text-rose-700 dark:text-rose-400 font-bold shadow-sm' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'" class="flex-1 py-2.5 px-2 sm:px-4 rounded-lg text-[11px] sm:text-sm transition-all flex justify-center items-center gap-1 sm:gap-2">
                    <x-heroicon-o-arrow-up-on-square class="w-4 h-4 sm:w-5 sm:h-5" /> Bayar Hutang
                </button>
            </div>

            <div class="flex-1 overflow-y-auto pb-10 pr-1">
                
                <div x-show="activeTab === 'operasional'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div class="p-5 border-b border-zinc-200 dark:border-zinc-700 bg-amber-50 dark:bg-amber-900/10">
                        <h3 class="font-bold text-amber-700 dark:text-amber-400 flex items-center gap-2">
                            <x-heroicon-s-wallet class="w-5 h-5" /> Catat Pengeluaran (Uang Keluar)
                        </h3>
                    </div>
                    <form wire:submit.prevent="submitExpense" class="p-5 flex flex-col gap-4">
                        <div x-data="{ raw: @entangle('expenseAmount').live }">
                            <label class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2 block">Nominal Pengeluaran</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-lg font-bold text-zinc-400">Rp</span>
                                <input type="text" :value="new Intl.NumberFormat('id-ID').format(raw || 0)" @input="let clean = $event.target.value.replace(/\D/g, ''); raw = clean ? parseInt(clean) : 0; $el.value = new Intl.NumberFormat('id-ID').format(raw);" class="w-full pl-12 pr-4 py-3 text-lg font-black bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl outline-none focus:ring-2 focus:ring-amber-500 text-amber-600 dark:text-amber-500">
                            </div>
                            @error('expenseAmount') <span class="text-xs text-red-500 font-bold mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2 block">Ambil Dana Dari</label>
                            <select wire:model.defer="expenseWalletId" class="w-full text-sm font-bold bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-amber-500">
                                <option value="">-- Pilih Sumber Dana --</option>
                                @foreach($wallets as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                @endforeach
                            </select>
                            @error('expenseWalletId') <span class="text-xs text-red-500 font-bold mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2 block">Keperluan / Keterangan</label>
                            <input type="text" wire:model.defer="expenseNote" placeholder="Contoh: Bensin Operasional, Makan Siang, dll..." class="w-full text-sm font-semibold bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-amber-500">
                            @error('expenseNote') <span class="text-xs text-red-500 font-bold mt-1">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" wire:loading.attr="disabled" class="mt-2 w-full bg-amber-500 hover:bg-amber-600 text-white font-black py-4 rounded-xl shadow-lg shadow-amber-500/30 flex justify-center items-center gap-2 transition disabled:opacity-50">
                            <span wire:loading.remove wire:target="submitExpense">Simpan Pengeluaran</span>
                            <span wire:loading wire:target="submitExpense">Memproses...</span>
                        </button>
                    </form>
                </div>

                <div x-show="activeTab === 'piutang'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div class="p-5 border-b border-zinc-200 dark:border-zinc-700 bg-green-50 dark:bg-green-900/10">
                        <h3 class="font-bold text-green-700 dark:text-green-400 flex items-center gap-2">
                            <x-heroicon-s-arrow-down-circle class="w-5 h-5" /> Terima Pembayaran Hutang Konsumen
                        </h3>
                    </div>
                    <form wire:submit.prevent="submitReceivable" class="p-5 flex flex-col gap-4">
                        <div>
                            <label class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2 block">Pilih Tagihan Konsumen</label>
                            <select wire:model.live="receivableOrderId" class="w-full text-sm font-bold bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">-- Pilih Nota Yang Belum Lunas --</option>
                                @foreach($unpaidOrders as $order)
                                    <option value="{{ $order->id }}">{{ $order->order_number }} - {{ $order->customer?->name ?? 'Umum' }} (Rp {{ number_format($order->total_amount, 0, ',', '.') }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2 block">Masukkan Ke Dompet</label>
                                <select wire:model.defer="receivableWalletId" class="w-full text-sm font-bold bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-green-500">
                                    <option value="">-- Pilih Dompet Tujuan --</option>
                                    @foreach($wallets as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div x-data="{ raw: @entangle('receivableAmount').live }">
                                <label class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2 block">Uang Diterima</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-zinc-400">Rp</span>
                                    <input type="text" :value="new Intl.NumberFormat('id-ID').format(raw || 0)" @input="let clean = $event.target.value.replace(/\D/g, ''); raw = clean ? parseInt(clean) : 0; $el.value = new Intl.NumberFormat('id-ID').format(raw);" class="w-full pl-9 pr-3 py-3 text-base font-black bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl outline-none focus:ring-2 focus:ring-green-500 text-green-600 dark:text-green-500">
                                </div>
                            </div>
                        </div>

                        <button type="submit" wire:loading.attr="disabled" class="mt-2 w-full bg-green-500 hover:bg-green-600 text-white font-black py-4 rounded-xl shadow-lg shadow-green-500/30 flex justify-center items-center gap-2 transition disabled:opacity-50">
                            <span wire:loading.remove wire:target="submitReceivable">Lunasi Piutang</span>
                            <span wire:loading wire:target="submitReceivable">Memproses...</span>
                        </button>
                    </form>
                </div>

                <div x-show="activeTab === 'hutang'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div class="p-5 border-b border-zinc-200 dark:border-zinc-700 bg-rose-50 dark:bg-rose-900/10">
                        <h3 class="font-bold text-rose-700 dark:text-rose-400 flex items-center gap-2">
                            <x-heroicon-s-arrow-up-circle class="w-5 h-5" /> Bayar Tagihan Supplier / Pabrik
                        </h3>
                    </div>
                    <form wire:submit.prevent="submitPayable" class="p-5 flex flex-col gap-4">
                        <div>
                            <label class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2 block">Pilih Tagihan Supplier</label>
                            <select wire:model.live="payablePurchaseId" class="w-full text-sm font-bold bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-rose-500">
                                <option value="">-- Pilih Nota Pabrik Yang Belum Lunas --</option>
                                @foreach($unpaidPurchases as $purchase)
                                    <option value="{{ $purchase->id }}">{{ $purchase->invoice_number }} - {{ $purchase->supplier?->name ?? 'Supplier Umum' }} (Rp {{ number_format($purchase->total_amount, 0, ',', '.') }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2 block">Tarik Dana Dari</label>
                                <select wire:model.defer="payableWalletId" class="w-full text-sm font-bold bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-rose-500">
                                    <option value="">-- Pilih Dompet Penarikan --</option>
                                    @foreach($wallets as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div x-data="{ raw: @entangle('payableAmount').live }">
                                <label class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2 block">Uang Dibayarkan</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-zinc-400">Rp</span>
                                    <input type="text" :value="new Intl.NumberFormat('id-ID').format(raw || 0)" @input="let clean = $event.target.value.replace(/\D/g, ''); raw = clean ? parseInt(clean) : 0; $el.value = new Intl.NumberFormat('id-ID').format(raw);" class="w-full pl-9 pr-3 py-3 text-base font-black bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl outline-none focus:ring-2 focus:ring-rose-500 text-rose-600 dark:text-rose-500">
                                </div>
                            </div>
                        </div>

                        <button type="submit" wire:loading.attr="disabled" class="mt-2 w-full bg-rose-500 hover:bg-rose-600 text-white font-black py-4 rounded-xl shadow-lg shadow-rose-500/30 flex justify-center items-center gap-2 transition disabled:opacity-50">
                            <span wire:loading.remove wire:target="submitPayable">Lunasi Tagihan Pabrik</span>
                            <span wire:loading wire:target="submitPayable">Memproses...</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="w-full lg:w-1/3 flex flex-col gap-4 overflow-y-auto pb-10 pr-1">
            
            <div class="bg-blue-600 rounded-2xl p-5 shadow-lg shadow-blue-600/20 text-white relative overflow-hidden">
                <div class="relative z-10">
                    <p class="text-blue-100 text-[10px] font-bold uppercase tracking-widest mb-1">Omzet Hari Ini</p>
                    <h3 class="text-3xl font-black">Rp {{ number_format($todaySales, 0, ',', '.') }}</h3>
                    <p class="text-xs text-blue-200 mt-2 flex items-center gap-1">
                        <x-heroicon-s-arrow-trending-up class="w-4 h-4" /> Total penjualan terbayar kasir.
                    </p>
                </div>
                <x-heroicon-o-chart-bar class="absolute -right-4 -bottom-4 w-32 h-32 text-blue-500 opacity-30" />
            </div>

            <div class="bg-rose-50 dark:bg-rose-900/10 rounded-2xl p-5 border border-rose-200 dark:border-rose-800 shadow-sm relative overflow-hidden">
                <div class="relative z-10">
                    <p class="text-rose-500 dark:text-rose-400 text-[10px] font-bold uppercase tracking-widest mb-1">Pengeluaran Operasional</p>
                    <h3 class="text-2xl font-black text-rose-600 dark:text-rose-500">Rp {{ number_format($todayExpenses, 0, ',', '.') }}</h3>
                    <p class="text-xs text-rose-500/70 dark:text-rose-400/70 mt-1">Uang keluar hari ini.</p>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-5 mt-2">
                <h4 class="text-sm font-bold text-zinc-800 dark:text-zinc-200 mb-4 flex items-center gap-2">
                    <x-heroicon-o-wallet class="w-5 h-5 text-teal-500" />
                    Posisi Saldo Laci / Dompet
                </h4>
                
                <div class="flex flex-col gap-3 max-h-60 overflow-y-auto pr-2">
                    @foreach($wallets as $wallet)
                        <div class="flex justify-between items-center p-3 rounded-xl border border-zinc-100 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50">
                            <div>
                                <p class="text-xs font-black text-zinc-700 dark:text-zinc-300">{{ $wallet->name }}</p>
                                <p class="text-[10px] text-zinc-500 font-medium uppercase mt-0.5">{{ $wallet->type }}</p>
                            </div>
                            <span class="text-sm font-bold {{ $wallet->balance < 0 ? 'text-red-500' : 'text-teal-600 dark:text-teal-400' }}">
                                Rp {{ number_format($wallet->balance, 0, ',', '.') }}
                            </span>
                        </div>
                    @endforeach
                </div>
                
                <div class="mt-4 pt-4 border-t border-dashed border-zinc-200 dark:border-zinc-700 text-center">
                    <p class="text-[10px] text-zinc-400 font-medium">Pastikan uang fisik di laci sesuai dengan saldo tunai di atas sebelum tutup shift.</p>
                </div>
            </div>
            
        </div>
    </div>

    @if($showSuccessModal)
        <div class="fixed inset-0 z-[110] flex items-center justify-center bg-zinc-900/80 backdrop-blur-sm p-4 animate-fade-in">
            <div class="bg-white dark:bg-zinc-800 rounded-3xl w-full max-w-sm shadow-2xl border border-zinc-200 dark:border-zinc-700 p-6 flex flex-col items-center text-center animate-fade-in-up">
                <div class="w-16 h-16 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center mb-4">
                    <x-heroicon-o-check-circle class="w-12 h-12" />
                </div>
                <h3 class="text-xl font-black text-zinc-800 dark:text-zinc-100 mb-2">Berhasil!</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6 font-medium">{{ $successMessage }}</p>
                <button type="button" wire:click="closeSuccessModal" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-500/20 text-sm transition">
                    Tutup
                </button>
            </div>
        </div>
    @endif
</div>