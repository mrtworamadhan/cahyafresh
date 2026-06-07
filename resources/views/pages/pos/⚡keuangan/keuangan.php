<?php

use App\Models\Ledger;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\Wallet;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts::pos')] class extends Component
{
    public $expenseAmount = 0;
    public $expenseWalletId = '';
    public string $expenseNote = '';

    // --- STATE PELUNASAN PIUTANG (DARI KONSUMEN) ---
    public $receivableOrderId = '';
    public $receivableAmount = 0;
    public $receivableWalletId = '';

    // --- STATE PELUNASAN HUTANG (KE SUPPLIER) ---
    public $payablePurchaseId = '';
    public $payableAmount = 0;
    public $payableWalletId = '';

    // Modal Sukses Umum
    public bool $showSuccessModal = false;
    public string $successMessage = '';

    public $expenseCategoryId = '';

    // Auto-fill nominal piutang saat nota dipilih
    public function updatedReceivableOrderId($value)
    {
        if ($value) {
            $order = Order::find($value);
            // Asumsi: Pelunasan dilakukan full sesuai total nota. Jika ada sistem cicilan, logic bisa disesuaikan nanti.
            $this->receivableAmount = $order ? $order->total_amount : 0;
        } else {
            $this->receivableAmount = 0;
        }
    }

    // Auto-fill nominal hutang saat nota dipilih
    public function updatedPayablePurchaseId($value)
    {
        if ($value) {
            $purchase = Purchase::find($value);
            $this->payableAmount = $purchase ? $purchase->total_amount : 0;
        } else {
            $this->payableAmount = 0;
        }
    }

    // 1. FUNGSI CATAT PENGELUARAN
    public function submitExpense()
    {
        $this->validate([
            'expenseAmount' => 'required|numeric|min:1',
            'expenseWalletId' => 'required',
            'expenseCategoryId' => 'required', // <--- WAJIB DIPILIH
            'expenseNote' => 'required|string|max:255',
        ]);

        $businessId = Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id;

        DB::transaction(function () use ($businessId) {
            Ledger::create([
                'business_id' => $businessId,
                'wallet_id' => $this->expenseWalletId,
                'finance_category_id' => $this->expenseCategoryId, // <--- MASUKKAN KE SINI
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

    // 2. FUNGSI CATAT PELUNASAN PIUTANG (UANG MASUK)
    public function submitReceivable()
    {
        $this->validate([
            'receivableOrderId' => 'required',
            'receivableAmount' => 'required|numeric|min:1',
            'receivableWalletId' => 'required',
        ]);

        $businessId = Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id;
        $order = Order::find($this->receivableOrderId);

        if (!$order) return;

        DB::transaction(function () use ($businessId, $order) {
            $kategoriPenjualan = \App\Models\FinanceCategory::where('code', 'INC_AR')->first(); // AR = Piutang Barang
            $kategoriOngkir = \App\Models\FinanceCategory::where('code', 'INC_SHIPPING')->first();

            // Hitung berapa ongkir yang SUDAH dibayar di cicilan sebelumnya
            $ongkirSudahDibayar = Ledger::where('reference_type', Order::class)
                ->where('reference_id', $order->id)
                ->where('finance_category_id', $kategoriOngkir?->id)
                ->sum('amount');

            // Hitung sisa ongkir yang belum dibayar
            $sisaTagihanOngkir = max(0, (float)$order->shipping_fee_billed - $ongkirSudahDibayar);
            
            // Alokasi uang cicilan/pelunasan ini
            $uangMasuk = (float)$this->receivableAmount;
            $ongkirDibayar = min($uangMasuk, $sisaTagihanOngkir);
            $barangDibayar = $uangMasuk - $ongkirDibayar;

            // 1. Jurnal Pelunasan Piutang Barang
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

            // 2. Jurnal Pelunasan Ongkir (Jika masih ada sisa ongkir)
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

            if ($order->commission_amount > 0 && $order->commission_recipient_id) {
                

                $penerimaKomisi = \App\Models\Customer::find($order->commission_recipient_id);
                if ($penerimaKomisi) {
                    $penerimaKomisi->increment('commission_balance', (float)$order->commission_amount);
                }
            }

            Wallet::find($this->receivableWalletId)?->increment('balance', $uangMasuk);
            
            // Ubah status nota menjadi lunas
            $order->update(['status' => 'completed']);
            $order->update(['payment_status' => 'paid']);
        });

        $this->reset(['receivableOrderId', 'receivableAmount', 'receivableWalletId']);
        $this->successMessage = 'Pelunasan piutang berhasil dicatat! Saldo dompet bertambah.';
        $this->showSuccessModal = true;
    }

    // 3. FUNGSI CATAT PELUNASAN HUTANG (UANG KELUAR)
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
            Ledger::create([
                'business_id' => $businessId,
                'wallet_id' => $this->payableWalletId,
                'finance_category_id' => \App\Models\FinanceCategory::where('code', 'LIA_AP')->first()?->id,
                'transaction_date' => now(),
                'description' => "Pelunasan Hutang Supplier Nota: {$purchase->invoice_number}",
                'type' => 'out', 
                'amount' => (float)$this->payableAmount,
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
            ]);

            Wallet::find($this->payableWalletId)?->decrement('balance', (float)$this->payableAmount);
            
            // Ubah status nota menjadi lunas
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

        // 1. Hitung Total Omzet Penjualan Hari Ini (Dari Order yang Selesai)
        $todaySales = Order::where('business_id', $businessId)
            ->whereDate('order_date', $today)
            ->where('status', 'completed')
            ->sum('total_amount');

        // 2. Hitung Total Pengeluaran Hari Ini (Dari Ledger Uang Keluar Operasional)
        $todayExpenses = Ledger::where('business_id', $businessId)
            ->whereDate('transaction_date', $today)
            ->where('type', 'out')
            ->whereHas('financeCategory', function($query) {
                // Hanya hitung jika tipe COA-nya adalah 'out' (expense)
                // Mengabaikan 'equity' seperti Prive atau Komisi Referral
                $query->where('type', 'out');
            })
            ->sum('amount');

        return [
            'wallets' => Wallet::where('business_id', $businessId)->where('is_active', true)->get(),
            'unpaidOrders' => Order::with('customer')->where('business_id', $businessId)->whereIn('payment_status', ['unpaid', 'partial'])->latest()->get(),
            'unpaidPurchases' => Purchase::with('supplier')->where('business_id', $businessId)->where('status', 'unpaid')->latest()->get(),
            'todaySales' => $todaySales,
            'todayExpenses' => $todayExpenses,
            'expenseCategories' => \App\Models\FinanceCategory::where('type', 'out')->get(),
        ];
    }
};