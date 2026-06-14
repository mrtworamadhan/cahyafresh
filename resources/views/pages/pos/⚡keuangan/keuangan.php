<?php

use App\Models\Ledger;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\Wallet;
use App\Models\Product;
use App\Models\Customer;
use App\Models\FinanceCategory;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts::pos')] class extends Component
{
    public $expenseAmount = 0;
    public $expenseWalletId = '';
    public string $expenseNote = '';

    public $receivableOrderId = '';
    public $receivableAmount = 0;
    public $receivableWalletId = '';

    public $payablePurchaseId = '';
    public $payableAmount = 0;
    public $payableWalletId = '';

    public bool $showSuccessModal = false;
    public string $successMessage = '';

    public $expenseCategoryId = '';

    public string $searchReceivable = '';
    public string $searchPayable = '';
    public string $searchExpenseCategory = '';

    public function updatedReceivableOrderId($value)
    {
        if ($value) {
            $order = Order::with('ledgers')->find($value);
            $this->receivableAmount = $order ? $order->remaining_balance : 0;
        } else {
            $this->receivableAmount = 0;
        }
    }

    public function updatedPayablePurchaseId($value)
    {
        if ($value) {
            $purchase = Purchase::find($value);
            $this->payableAmount = $purchase ? $purchase->remaining_balance : 0;
        } else {
            $this->payableAmount = 0;
        }
    }

    public function submitExpense()
    {
        $this->validate([
            'expenseAmount' => 'required|numeric|min:1',
            'expenseWalletId' => 'required',
            'expenseCategoryId' => 'required',
            'expenseNote' => 'required|string|max:255',
        ]);

        $businessId = Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id;

        DB::transaction(function () use ($businessId) {
            Ledger::create([
                'business_id' => $businessId,
                'wallet_id' => $this->expenseWalletId,
                'finance_category_id' => $this->expenseCategoryId, 
                'transaction_date' => now(),
                'description' => "Biaya Operasional: " . $this->expenseNote,
                'type' => 'out',
                'amount' => (float)$this->expenseAmount,
            ]);

            Wallet::find($this->expenseWalletId)?->decrement('balance', (float)$this->expenseAmount);
        });

        $this->reset(['expenseAmount', 'expenseWalletId', 'expenseCategoryId', 'expenseNote']);
        $this->successMessage = 'Pengeluaran operasional berhasil dicatat!';
        $this->showSuccessModal = true;
    }

    public function submitReceivable()
    {
        $this->validate([
            'receivableOrderId' => 'required',
            'receivableAmount' => 'required|numeric|min:1',
            'receivableWalletId' => 'required',
        ]);

        $businessId = Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id;
        $order = Order::with('orderItems')->find($this->receivableOrderId);

        if (!$order) return;

        // ==============================================================
        // 1. HITUNG HISTORIS PEMBAYARAN LAMA & SISA PIUTANG RIIL NOTA
        // ==============================================================
        $totalSudahDibayar = Ledger::where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->where('type', 'in')
            ->sum('amount');

        $sisaPiutangNota = max(0, (float)$order->total_amount - $totalSudahDibayar);
        $uangMasuk = (float)$this->receivableAmount;

        // Validasi Keamanan: Cegah kasir input uang cicilan kelebihan
        if ($uangMasuk > $sisaPiutangNota) {
            session()->flash('error', 'Gagal! Nominal input (Rp ' . number_format($uangMasuk, 0, ',', '.') . ') melebihi sisa tagihan nota saat ini (Rp ' . number_format($sisaPiutangNota, 0, ',', '.') . ').');
            return;
        }

        // ==============================================================
        // 2. PENENTUAN STATUS PEMBAYARAN BARU SECARA OTOMATIS
        // ==============================================================
        $isLunas = ($totalSudahDibayar + $uangMasuk >= (float)$order->total_amount);
        $newPaymentStatus = $isLunas ? 'paid' : 'partial';
        $suffixDesc = $isLunas ? '(Pelunasan Lunas)' : '(Cicilan/Sebagian)';

        DB::transaction(function () use ($businessId, $order, $uangMasuk, $newPaymentStatus, $suffixDesc, $isLunas) {
            $kategoriPenjualan = FinanceCategory::withoutGlobalScopes()->where('code', 'INC_AR')->first(); 
            $kategoriOngkir = FinanceCategory::withoutGlobalScopes()->where('code', 'INC_SHIPPING')->first();

            // Split pembayaran ongkir vs barang secara proporsional
            $ongkirSudahDibayar = Ledger::where('reference_type', Order::class)
                ->where('reference_id', $order->id)
                ->where('finance_category_id', $kategoriOngkir?->id)
                ->sum('amount');

            $sisaTagihanOngkir = max(0, (float)$order->shipping_fee_billed - $ongkirSudahDibayar);
            
            $ongkirDibayar = min($uangMasuk, $sisaTagihanOngkir);
            $barangDibayar = $uangMasuk - $ongkirDibayar;

            // 3. Jurnal Arus Uang Tunai Masuk ke Dompet Kasir (Dilengkapi Suffix Status)
            if ($barangDibayar > 0) {
                Ledger::create([
                    'business_id' => $businessId,
                    'wallet_id' => $this->receivableWalletId,
                    'finance_category_id' => $kategoriPenjualan?->id,
                    'transaction_date' => now(),
                    'description' => "Penerimaan Piutang Barang Nota: {$order->order_number} {$suffixDesc}",
                    'type' => 'in', 
                    'amount' => $barangDibayar,
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                ]);
            }

            if ($ongkirDibayar > 0) {
                Ledger::create([
                    'business_id' => $businessId,
                    'wallet_id' => $this->receivableWalletId,
                    'finance_category_id' => $kategoriOngkir?->id,
                    'transaction_date' => now(),
                    'description' => "Penerimaan Piutang Ongkir Nota: {$order->order_number} {$suffixDesc}",
                    'type' => 'in', 
                    'amount' => $ongkirDibayar,
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                ]);
            }

            // ==============================================================
            // 4. JURNAL BEBAN KOMISI REFERRAL (HANYA CAIR JIKA SUDAH LUNAS TOTAL)
            // ==============================================================
            if ($isLunas && $order->commission_amount > 0 && $order->commission_recipient_id) {
                $kategoriKomisi = FinanceCategory::withoutGlobalScopes()->where('code', 'OP_COMMISSION')->first();
                
                if ($kategoriKomisi) {
                    $hasRecordedCommission = Ledger::where('reference_type', Order::class)
                        ->where('reference_id', $order->id)
                        ->where('finance_category_id', $kategoriKomisi->id)
                        ->exists();

                    if (!$hasRecordedCommission) {
                        Ledger::create([
                            'business_id' => $businessId,
                            'wallet_id' => null, // Gantung Kewajiban Neraca
                            'finance_category_id' => $kategoriKomisi->id,
                            'transaction_date' => now(),
                            'description' => "Pengakuan Beban Komisi Nota: {$order->order_number} (Pelunasan Piutang Final)",
                            'type' => 'out',
                            'amount' => (float)$order->commission_amount,
                            'contact_type' => Customer::class,
                            'contact_id' => $order->commission_recipient_id,
                            'reference_type' => Order::class,
                            'reference_id' => $order->id,
                        ]);

                        Customer::find($order->commission_recipient_id)?->increment('commission_balance', (float)$order->commission_amount);
                    }
                }
            }

            // ==============================================================
            // 5. LIFECYCLE BARANG (Hanya tereksekusi 1x saat pertama kali beralih dari draft)
            // ==============================================================
            $oldStatus = $order->status;
            if ($oldStatus !== 'completed') {
                // Di-handle otomatis oleh Observer bawaan core sistem lu (Bebas Decrement Manual)
                if ($order->delivery_type === 'delivery' && (float)$order->shipping_cost_actual > 0) {
                    $kategoriBebanOngkir = FinanceCategory::withoutGlobalScopes()->where('code', 'OP_SHIPPING')->first();
                    if ($kategoriBebanOngkir) {
                        Ledger::create([
                            'business_id' => $businessId,
                            'wallet_id' => null, 
                            'finance_category_id' => $kategoriBebanOngkir->id,
                            'transaction_date' => now(),
                            'description' => "Pengakuan Beban Pengiriman/Kurir Nota: {$order->order_number}",
                            'type' => 'out',
                            'amount' => (float)$order->shipping_cost_actual,
                            'reference_type' => Order::class,
                            'reference_id' => $order->id,
                        ]);
                    }
                }
            }

            // Tambahkan saldo fisik uang masuk ke rekening/dompet kasir tujuan
            Wallet::find($this->receivableWalletId)?->increment('balance', $uangMasuk);
            
            // Simpan pembaruan status cicilan nota
            $order->update([
                'status' => 'completed',
                'payment_status' => $newPaymentStatus
            ]);
        });

        $this->reset(['receivableOrderId', 'receivableAmount', 'receivableWalletId']);
        $this->successMessage = ($isLunas) 
            ? 'Pelunasan piutang penuh berhasil dicatat! Seluruh komisi referral telah dilepaskan.' 
            : 'Pembayaran cicilan berhasil dicatat! Sisa tagihan nota otomatis diperbarui.';
        
        $this->showSuccessModal = true;
    }

    public function submitPayable()
    {
        $this->validate([
            'payablePurchaseId' => 'required',
            'payableAmount' => 'required|numeric|min:1',
            'payableWalletId' => 'required',
        ]);

        $businessId = Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id;
        $purchase = Purchase::find($this->payablePurchaseId);

        if (!$purchase) return;

        DB::transaction(function () use ($businessId, $purchase) {
            $kategoriHutang = FinanceCategory::withoutGlobalScopes()->where('code', 'LIA_AP')->first();

            Ledger::create([
                'business_id' => $businessId,
                'wallet_id' => $this->payableWalletId,
                'finance_category_id' => $kategoriHutang?->id,
                'transaction_date' => now(),
                'description' => "Pelunasan Hutang Supplier Nota: {$purchase->invoice_number}",
                'type' => 'out', 
                'amount' => (float)$this->payableAmount,
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
            ]);

            Wallet::find($this->payableWalletId)?->decrement('balance', (float)$this->payableAmount);
            
            $purchase->update(['status' => 'paid']);
        });

        $this->reset(['payablePurchaseId', 'payableAmount', 'payableWalletId']);
        $this->successMessage = 'Pelunasan hutang supplier berhasil dicatat! Saldo dompet berkurang.';
        $this->showSuccessModal = true;
    }

    public function closeSuccessModal()
    {
        $this->showSuccessModal = false;
        $this->successMessage = '';
    }

    public function with(): array
    {
        $businessId = Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id;
        $today = now()->toDateString();

        $todaySales = Order::where('business_id', $businessId)
            ->whereDate('order_date', $today)
            ->where('status', 'completed')
            ->sum('total_amount');

        $todayExpenses = Ledger::where('business_id', $businessId)
            ->whereDate('transaction_date', $today)
            ->where('type', 'out')
            ->whereHas('financeCategory', function($query) {
                $query->where('type', 'out');
            })
            ->sum('amount');

        $unpaidOrdersQuery = Order::with(['customer', 'ledgers'])
            ->where('business_id', $businessId)
            ->where('status', 'completed') 
            ->whereIn('payment_status', ['unpaid', 'partial']);

        if (!empty($this->searchReceivable)) {
            $unpaidOrdersQuery->where(function($query) {
                $query->where('order_number', 'like', '%' . $this->searchReceivable . '%')
                      ->orWhereHas('customer', function($q) {
                          $q->where('name', 'like', '%' . $this->searchReceivable . '%');
                      });
            });
        }

        $unpaidOrders = $unpaidOrdersQuery->latest()->get();

        $unpaidPurchasesQuery = Purchase::with('supplier')
            ->where('business_id', $businessId)
            ->whereIn('status', ['unpaid', 'partial']); 

        if (!empty($this->searchPayable)) {
            $unpaidPurchasesQuery->where(function($query) {
                $query->where('invoice_number', 'like', '%' . $this->searchPayable . '%')
                      ->orWhereHas('supplier', function($q) {
                          $q->where('name', 'like', '%' . $this->searchPayable . '%');
                      });
            });
        }

        $unpaidPurchases = $unpaidPurchasesQuery->latest()->get();

        $expenseCategoriesQuery = FinanceCategory::withoutGlobalScopes()
            ->where('type', 'out')
            ->whereNotIn('code', ['EXP_PURCHASE', 'LIA_AP', 'OP_SHIPPING', 'LIA_COMMISSION_PAID', 'LIA_SHIPPING_PAID', 'LIA_CSR_ZAKAT_PAID']) // Saring agar COA pelunasan/gudang tidak muncul di pos biaya operasional biasa
            ->where(function($query) use ($businessId) {
                $query->where('business_id', $businessId)
                      ->orWhereNull('business_id');
            });

        if (!empty($this->searchExpenseCategory)) {
            $expenseCategoriesQuery->where('name', 'like', '%' . $this->searchExpenseCategory . '%');
        }

        $expenseCategories = $expenseCategoriesQuery->orderBy('name')->get();
        return [
            'wallets' => Wallet::where('business_id', $businessId)->where('is_active', true)->get(),
            'unpaidOrders' => $unpaidOrders,
            'unpaidPurchases' => $unpaidPurchases,
            'todaySales' => $todaySales,
            'todayExpenses' => $todayExpenses,
            'expenseCategories' => $expenseCategories,
        ];
    }
};