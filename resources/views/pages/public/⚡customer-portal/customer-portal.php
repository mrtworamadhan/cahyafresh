<?php

use App\Models\Order;
use Livewire\Component;
use App\Models\Customer;
use App\Models\Wallet;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Title('Customer Portal - Cahya Fresh')] class extends Component
{
    public Customer $customer;
    public $business;
    public $unpaidOrders = [];
    public $paidOrders = [];
    public $wallets = [];
    public $totalUnpaid = 0;

    public $totalPiutang = 0;
    public $totalDeposit = 0;
    public $totalKomisi = 0;

    public function mount($slug)
    {
        $this->customer = Customer::with('business')->where('slug', $slug)->firstOrFail();
        $this->business = $this->customer->business;

        // Ambil semua pesanan milik pelanggan ini beserta detail dan pengirimannya
        $orders = Order::with(['orderItems.product', 'delivery'])
            ->where('customer_id', $this->customer->id)
            ->where('status', 'completed')
            ->orderBy('order_date', 'desc')
            ->get();

        $this->unpaidOrders = $orders->whereIn('payment_status', ['unpaid', 'partial'])->values();
        $this->paidOrders = $orders->where('payment_status', 'paid')->values();
        $this->totalUnpaid = $this->unpaidOrders->sum('total_amount');
        $this->totalPiutang = $this->unpaidOrders->sum('total_amount');
        $this->totalDeposit = $this->customer->deposit_balance ?? 0; // Pastikan kolom ini ada di database
        $this->totalKomisi = $this->customer->commission_balance ?? 0; // Pastikan kolom ini ada di database
        $this->wallets = Wallet::where('business_id', $this->business->id)
            ->where('is_active', true)
            ->where('type', 'bank') 
            ->get();
    }
};