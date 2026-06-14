<?php

use App\Models\FinanceCategory;
use App\Models\Order;
use App\Models\Ledger;
use App\Models\Customer;
use App\Models\Wallet;
use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Customer Portal - Cahya Fresh')] class extends Component
{
    public Customer $customer;
    public $business;
    public $unpaidOrders = [];
    public $paidOrders = [];
    public $draftOrders = []; 
    public $commissionHistory = []; 
    public $commissionOrders = []; 
    public $wallets = [];
    public $totalUnpaid = 0;
    public array $transactionHistory = [];

    public $totalPiutang = 0;
    public $totalDeposit = 0;
    public $totalKomisi = 0;

    public function mount($slug)
    {
        $this->customer = Customer::with('business')->where('slug', $slug)->firstOrFail();
        $this->business = $this->customer->business;

        $allOrders = Order::with(['orderItems.product', 'delivery'])
            ->where('customer_id', $this->customer->id)
            ->get();

        $this->unpaidOrders = $allOrders->where('status', 'completed')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->sortByDesc(function ($order) {
                return $order->delivery_date ?? $order->order_date;
            })->values()->all();

        $this->draftOrders = $allOrders->whereIn('status', ['draft', 'processing'])
            ->sortBy(function ($order) {
                return $order->delivery_date ?? $order->order_date;
            })->values()->all();

        $this->paidOrders = $allOrders->where('status', 'completed')
            ->where('payment_status', 'paid')
            ->sortByDesc(function ($order) {
                return $order->delivery_date ?? $order->order_date;
            })->values()->all();

        $historyStream = collect();

        // 1. Ambil Semua Nota Belanja Completed (Menjadi Baris "TAGIHAN KELUAR")
        $customerOrders = Order::where('customer_id', $this->customer->id)
            ->where('status', 'completed')
            ->get();

        foreach ($customerOrders as $order) {
            $historyStream->push([
                // PERBAIKAN SAKRAL: Paksa format tanggal menjadi string datetime standard
                'created_at' => \Carbon\Carbon::parse($order->created_at)->format('Y-m-d H:i:s'),
                'type' => 'tagihan', 
                'reference' => $order->order_number,
                'title' => 'Tagihan Belanja (Nota #' . $order->order_number . ')',
                'amount' => (float)$order->total_amount,
                'badge_color' => 'rose',
                'note' => 'Pembelian produk dagangan gantung/piutang.',
            ]);
        }

        // 2. Ambil Semua Log Pembayaran Cicilan di Ledger (Menjadi Baris "PEMBAYARAN MASUK")
        $orderIds = $customerOrders->pluck('id')->toArray();
        
        $kategoriAr = FinanceCategory::withoutGlobalScopes()->where('code', 'INC_AR')->first()?->id;
        $kategoriShipping = FinanceCategory::withoutGlobalScopes()->where('code', 'INC_SHIPPING')->first()?->id;

        $paymentLedgers = Ledger::where('reference_type', Order::class)
            ->whereIn('reference_id', $orderIds)
            ->whereIn('finance_category_id', [$kategoriAr, $kategoriShipping])
            ->where('type', 'in') 
            ->get();

        foreach ($paymentLedgers as $ledger) {
            $historyStream->push([
                'created_at' => \Carbon\Carbon::parse($ledger->transaction_date)->format('Y-m-d H:i:s'),
                'type' => 'pembayaran',
                'reference' => $ledger->description,
                'title' => 'Pembayaran Diterima (Kasir)',
                'amount' => (float)$ledger->amount,
                'badge_color' => 'green',
                'note' => $ledger->description, 
            ]);
        }

        $this->transactionHistory = $historyStream->sortByDesc('created_at')->values()->all();

        $combinedHistory = collect();

        // 1. Ambil Data Komisi Masuk (In)
        $commissionOrders = Order::with(['customer', 'orderItems.product'])
            ->where('commission_recipient_id', $this->customer->id)
            ->where('status', 'completed')
            ->where('commission_amount', '>', 0)
            ->get();

        foreach ($commissionOrders as $order) {
            $combinedHistory->push([
                // PERBAIKAN: Paksa format tanggal menjadi string datetime standard agar apple-to-apple saat di-sort
                'created_at' => \Carbon\Carbon::parse($order->order_date ?? $order->updated_at)->format('Y-m-d H:i:s'),
                'type' => 'in', 
                'title' => 'Komisi Masuk (Nota #' . $order->order_number . ')',
                'amount' => (float)$order->commission_amount,
                'note' => $order->commission_note ?? 'Bonus pencatatan pesanan',
                'orderItems' => $order->orderItems,
                'customer_name' => $order->customer?->name ?? 'Pelanggan Umum',
            ]);
        }

        // 2. Ambil Data Pencairan Komisi / Withdraw (Out)
        $kategoriPencairan = FinanceCategory::withoutGlobalScopes()->where('code', 'LIA_COMMISSION_PAID')->first();

        $ledgerWithdrawals = Ledger::where('contact_id', $this->customer->id)
            ->where('contact_type', Customer::class)
            ->where('type', 'out') 
            ->when($kategoriPencairan, function($query) use ($kategoriPencairan) {
                return $query->where('finance_category_id', $kategoriPencairan->id);
            })
            ->get();

        foreach ($ledgerWithdrawals as $ledger) {
            $combinedHistory->push([
                // PERBAIKAN: Paksa format tanggal menjadi string datetime standard yang sama
                'created_at' => \Carbon\Carbon::parse($ledger->transaction_date)->format('Y-m-d H:i:s'),
                'type' => 'out', 
                'title' => 'Pencairan Komisi (Withdraw)',
                'amount' => (float)$ledger->amount,
                'note' => $ledger->description ?? 'Penarikan dana tunai dari dompet toko',
                'orderItems' => [], 
            ]);
        }

        $this->commissionHistory = $combinedHistory->sortByDesc('created_at')->values()->all();

        $this->commissionOrders = Order::with(['orderItems.product'])
            ->where('commission_recipient_id', $this->customer->id)
            ->where('status', 'completed') // Hanya ambil komisi dari orderan yang sudah sukses/selesai
            ->where('commission_amount', '>', 0)
            ->latest()
            ->get();

        foreach ($this->commissionOrders as $order) {
            $combinedHistory->push([
                'date' => $order->order_date,
                'type' => 'in', 
                'title' => 'Komisi Masuk (Nota #' . $order->order_number . ')',
                'amount' => $order->commission_amount, 
                'note' => $order->commission_note ?? 'Bonus pencatatan pesanan',
            ]);
        }
        
        $this->totalUnpaid  = collect($this->unpaidOrders)->sum('remaining_balance');
        $this->totalPiutang = collect($this->unpaidOrders)->sum('remaining_balance'); 
        
        $this->totalDeposit = $this->customer->deposit_balance ?? 0; 
        $this->totalKomisi  = $this->customer->commission_balance ?? 0;
        
        $this->wallets = Wallet::where('business_id', $this->business->id)
            ->where('is_active', true)
            ->where('type', 'bank') 
            ->get();
    }
};