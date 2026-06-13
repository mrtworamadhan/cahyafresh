<?php

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

        $combinedHistory = collect();

        // 1. Tarik Rincian Komisi Masuk dari Pesanan Sukses Orang Lain
        $commissionOrders = Order::with(['customer', 'orderItems.product']) // <--- Tambah 'customer'
            ->where('commission_recipient_id', $this->customer->id)
            ->where('status', 'completed')
            ->where('commission_amount', '>', 0)
            ->get();

        foreach ($commissionOrders as $order) {
            $combinedHistory->push([
                'date' => $order->order_date ?? $order->updated_at,
                'type' => 'in', 
                'title' => 'Komisi Masuk (Nota #' . $order->order_number . ')',
                'amount' => (float)$order->commission_amount,
                'note' => $order->commission_note ?? 'Bonus pencatatan pesanan',
                'orderItems' => $order->orderItems,
                'customer_name' => $order->customer?->name ?? 'Pelanggan Umum',
            ]);
        }

        // 2. Tarik Rincian Pencairan Uang Tunai Agen dari Tabel Ledger (Mutasi Keluar)
        $kategoriPencairan = \App\Models\FinanceCategory::withoutGlobalScopes()->where('code', 'LIA_COMMISSION_PAID')->first();

        $ledgerWithdrawals = Ledger::where('contact_id', $this->customer->id)
            ->where('contact_type', Customer::class)
            ->where('type', 'out') 
            ->when($kategoriPencairan, function($query) use ($kategoriPencairan) {
                return $query->where('finance_category_id', $kategoriPencairan->id);
            })
            ->get();

        foreach ($ledgerWithdrawals as $ledger) {
            $combinedHistory->push([
                'date' => $ledger->transaction_date,
                'type' => 'out', 
                'title' => 'Pencairan Komisi (Withdraw)',
                'amount' => (float)$ledger->amount,
                'note' => $ledger->description ?? 'Penarikan dana tunai dari dompet toko',
                'orderItems' => [], // Kosong karena bukan nota belanja
            ]);
        }

        // 3. Gabungkan dan Urutkan Kronologis Berdasarkan Tanggal Terbaru (Anti-Comment)
        $this->commissionHistory = $combinedHistory->sortByDesc('date')->values()->all();

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
        
        $this->totalUnpaid = collect($this->unpaidOrders)->sum('total_amount');
        $this->totalPiutang = collect($this->unpaidOrders)->sum('total_amount');
        $this->totalDeposit = $this->customer->deposit_balance ?? 0; 
        $this->totalKomisi = $this->customer->commission_balance ?? 0; 
        
        $this->wallets = Wallet::where('business_id', $this->business->id)
            ->where('is_active', true)
            ->where('type', 'bank') 
            ->get();
    }
};