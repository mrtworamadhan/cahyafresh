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

    public function updatedReceivableOrderId($value)
    {
        if ($value) {
            $order = Order::find($value);
            $this->receivableAmount = $order ? $order->total_amount : 0;
        } else {
            $this->receivableAmount = 0;
        }
    }

    public function updatedPayablePurchaseId($value)
    {
        if ($value) {
            $purchase = Purchase::find($value);
            $this->payableAmount = $purchase ? $purchase->total_amount : 0;
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

        DB::transaction(function () use ($businessId, $order) {
            $kategoriPenjualan = FinanceCategory::withoutGlobalScopes()->where('code', 'INC_AR')->first(); 
            $kategoriOngkir = FinanceCategory::withoutGlobalScopes()->where('code', 'INC_SHIPPING')->first();

            $ongkirSudahDibayar = Ledger::where('reference_type', Order::class)
                ->where('reference_id', $order->id)
                ->where('finance_category_id', $kategoriOngkir?->id)
                ->sum('amount');

            $sisaTagihanOngkir = max(0, (float)$order->shipping_fee_billed - $ongkirSudahDibayar);
            
            $uangMasuk = (float)$this->receivableAmount;
            $ongkirDibayar = min($uangMasuk, $sisaTagihanOngkir);
            $barangDibayar = $uangMasuk - $ongkirDibayar;

            // 1. Jurnal Arus Uang Tunai Masuk ke Dompet Kasir
            if ($barangDibayar > 0) {
                Ledger::create([
                    'business_id' => $businessId,
                    'wallet_id' => $this->receivableWalletId,
                    'finance_category_id' => $kategoriPenjualan?->id,
                    'transaction_date' => now(),
                    'description' => "Pelunasan Piutang Barang Nota: {$order->order_number}",
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
                    'description' => "Pelunasan Ongkir Nota: {$order->order_number}",
                    'type' => 'in', 
                    'amount' => $ongkirDibayar,
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                ]);
            }

            // ==============================================================
            // PERBAIKAN FATAL: JURNAL BEBAN KOMISI DIKONTROL OLEH ALIRAN PEMBAYARAN
            // Dilepas dari gembok status agar tetap cair saat pelunasan piutang terjadi
            // ==============================================================
            if ($order->commission_amount > 0 && $order->commission_recipient_id) {
                $kategoriKomisi = FinanceCategory::withoutGlobalScopes()->where('code', 'OP_COMMISSION')->first();
                
                if ($kategoriKomisi) {
                    // Proteksi Sakral: Cek database apakah komisi nota ini sudah pernah lahir (Cegah Double Entry)
                    $hasRecordedCommission = Ledger::where('reference_type', Order::class)
                        ->where('reference_id', $order->id)
                        ->where('finance_category_id', $kategoriKomisi->id)
                        ->exists();

                    if (!$hasRecordedCommission) {
                        // A. Buat Slip Pengakuan Beban Komisi Gantung (wallet_id => null)
                        Ledger::create([
                            'business_id' => $businessId,
                            'wallet_id' => null, // Gantung Non-Tunai (Kewajiban)
                            'finance_category_id' => $kategoriKomisi->id,
                            'transaction_date' => now(),
                            'description' => "Pengakuan Beban Komisi Nota: {$order->order_number} (Pelunasan Piutang)",
                            'type' => 'out',
                            'amount' => (float)$order->commission_amount,
                            'contact_type' => Customer::class,
                            'contact_id' => $order->commission_recipient_id,
                            'reference_type' => Order::class,
                            'reference_id' => $order->id,
                        ]);

                        // B. Naikkan saldo hutang komisi milik agen di Sisi Pasiva Neraca
                        $penerimaKomisi = Customer::find($order->commission_recipient_id);
                        if ($penerimaKomisi) {
                            $penerimaKomisi->increment('commission_balance', (float)$order->commission_amount);
                        }
                    }
                }
            }

            // ==============================================================
            // LIFECYCLE BARANG (HANYA DI-EKSEKUSI JIKA TRANSISI DARI DRAFT KE COMPLETED)
            // ==============================================================
            $oldStatus = $order->status;

            if ($oldStatus !== 'completed') {
                
                // A. Refresh HPP Mengikuti Harga Modal Terbaru Master Produk & Kurangi Stok Gudang
                

                // C. Jurnal Pengakuan Beban Ongkir Gantung
                if ($order->delivery_type === 'delivery' && (float)$order->shipping_cost_actual > 0) {
                    $kategoriBebanOngkir = FinanceCategory::withoutGlobalScopes()->where('code', 'OP_SHIPPING')->first();
                    if ($kategoriBebanOngkir) {
                        Ledger::create([
                            'business_id' => $businessId,
                            'wallet_id' => null, 
                            'finance_category_id' => $kategoriBebanOngkir->id,
                            'transaction_date' => now(),
                            'description' => "Pengakuan Beban Pengiriman/Kurir Nota: {$order->order_number} (Pelunasan)",
                            'type' => 'out',
                            'amount' => (float)$order->shipping_cost_actual,
                            'reference_type' => Order::class,
                            'reference_id' => $order->id,
                        ]);
                    }
                }
            }

            // Tambahkan saldo fisik uang ke rekening/dompet kasir
            Wallet::find($this->receivableWalletId)?->increment('balance', $uangMasuk);
            
            // Kunci perubahan status nota
            $order->update([
                'status' => 'completed',
                'payment_status' => 'paid'
            ]);
        });

        $this->reset(['receivableOrderId', 'receivableAmount', 'receivableWalletId']);
        $this->successMessage = 'Pelunasan piutang berhasil dicatat! Siklus keuangan & komisi referral telah disinkronkan.';
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

        return [
            'wallets' => Wallet::where('business_id', $businessId)->where('is_active', true)->get(),
            'unpaidOrders' => Order::with('customer')
                ->where('business_id', $businessId)
                ->where('status', ['completed']) 
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->latest()
                ->get(),
            'unpaidPurchases' => Purchase::with('supplier')->where('business_id', $businessId)->where('status', 'unpaid')->latest()->get(),
            'todaySales' => $todaySales,
            'todayExpenses' => $todayExpenses,
            
            'expenseCategories' => FinanceCategory::withoutGlobalScopes()
                ->where('type', 'out')
                ->where(function($query) use ($businessId) {
                    $query->where('business_id', $businessId)
                          ->orWhereNull('business_id');
                })->orderBy('name')->get(),
        ];
    }
};