<?php

use App\Models\Order;
use App\Models\OrderItem;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts::pos')] class extends Component
{
    public bool $showSuccessModal = false;
    public string $successMessage = '';

    public bool $showCancelModal = false;
    public ?int $cancelOrderId = null;
    public string $cancelOrderNumber = '';
    public $cancelOrderAmount = 0;
    public string $cancelPaymentStatus = '';

    // PERBAIKAN UTAMA: Properti penampung tanggal yang dipilih
    public ?string $selectedDate = null;

    public function mount()
    {
        // Set default awal ke hari ini saat komponen pertama kali dibuka
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function openCancelModal($orderId)
    {
        $order = Order::find($orderId);
        if ($order) {
            $this->cancelOrderId = $order->id;
            $this->cancelOrderNumber = $order->order_number;
            $this->cancelOrderAmount = $order->total_amount;
            $this->cancelPaymentStatus = $order->payment_status;
            $this->showCancelModal = true;
        }
    }

    public function closeCancelModal()
    {
        $this->showCancelModal = false;
        $this->cancelOrderId = null;
    }

    public function executeCancelOrder()
    {
        if ($this->cancelOrderId) {
            $order = Order::find($this->cancelOrderId);
            if ($order && $order->status === 'draft') {
                $order->update(['status' => 'canceled']);
                
                $this->successMessage = "Pesanan {$order->order_number} berhasil dibatalkan.";
                $this->showCancelModal = false;
                $this->showSuccessModal = true;
            }
        }
    }

    public bool $showCompleteModal = false;
    public ?int $completeOrderId = null;
    public string $completeOrderNumber = '';

    public function openCompleteModal($orderId)
    {
        $order = Order::find($orderId);
        if ($order) {
            $this->completeOrderId = $order->id;
            $this->completeOrderNumber = $order->order_number;
            $this->showCompleteModal = true;
        }
    }

    public function closeCompleteModal()
    {
        $this->showCompleteModal = false;
        $this->completeOrderId = null;
    }

    public function executeCompleteOrder()
    {
        if ($this->completeOrderId) {
            $order = Order::find($this->completeOrderId);
            
            if ($order && $order->status === 'draft') {
                DB::transaction(function () use ($order) {
                    // Ubah status ke completed (Otomatis potong stok via Observer)
                    $order->update(['status' => 'completed']);
                    
                    if ($order->delivery_type === 'delivery' && $order->delivery) {
                        $order->delivery->update(['status' => 'delivered']);
                    }
                });

                $this->successMessage = "Pesanan {$order->order_number} berhasil diselesaikan. Stok gudang otomatis terpotong!";
                $this->showCompleteModal = false;
                $this->showSuccessModal = true;
            }
        }
    }

    public function closeSuccessModal()
    {
        $this->showSuccessModal = false;
    }

    public function with(): array
    {
        $businessId = Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id;
        
        // 1. DYNAMIC DROPDOWN: Ambil semua tanggal unik yang punya PO aktif (status draft)
        $availableDates = Order::where('business_id', $businessId)
            ->where('status', 'draft')
            ->whereNotNull('delivery_date')
            ->orderBy('delivery_date', 'asc')
            ->pluck('delivery_date')
            ->map(fn($date) => \Carbon\Carbon::parse($date)->format('Y-m-d'))
            ->unique()
            ->toArray();

        // Pastikan hari ini selalu ada di dropdown walaupun daftarnya kosong
        $todayStr = now()->format('Y-m-d');
        if (!in_array($todayStr, $availableDates)) {
            $availableDates[] = $todayStr;
            sort($availableDates);
        }

        // 2. SET ATURAN WAKTU BERDASARKAN SELECTION DROPDOWN
        $today = $this->selectedDate ?? $todayStr;
        $tomorrow = \Carbon\Carbon::parse($today)->addDay()->format('Y-m-d');

        // Tarik Kiriman sesuai tanggal dropdown
        $todayDeliveries = Order::with(['customer', 'orderItems.product'])
            ->where('business_id', $businessId)
            ->whereDate('delivery_date', $today)
            ->where('status', 'draft')
            ->orderBy('created_at', 'asc')
            ->get();

        // Tarik Packing List sesuai tanggal dropdown
        $rawPackingItems = OrderItem::with(['product', 'productUnit']) 
            ->whereHas('order', function ($query) use ($businessId, $today) {
                $query->where('business_id', $businessId)
                      ->whereDate('delivery_date', $today)
                      ->where('status', 'draft');
            })
            ->get();

        $packingListToday = $rawPackingItems->groupBy('product_id')->map(function ($items) {
            $product = $items->first()->product;
            $totalBaseQty = $items->sum(function ($item) {
                $multiplier = $item->productUnit ? (float)$item->productUnit->conversion_value : 1; 
                return $item->qty_billed * $multiplier;
            });
            return (object)[
                'product_name' => $product->name,
                'base_unit' => $product->base_unit ?? 'Satuan Dasar', 
                'total_qty' => $totalBaseQty,
            ];
        })->values();

        // Tarik Shopping List (1 hari setelah tanggal dropdown)
        $rawShoppingItems = OrderItem::with(['product', 'productUnit']) 
            ->whereHas('order', function ($query) use ($businessId, $tomorrow) {
                $query->where('business_id', $businessId)
                      ->whereDate('delivery_date', $tomorrow)
                      ->where('status', 'draft');
            })
            ->get();

        $shoppingListTomorrow = $rawShoppingItems->groupBy('product_id')->map(function ($items) {
            $product = $items->first()->product;
            $totalBaseQty = $items->sum(function ($item) {
                $multiplier = $item->productUnit ? (float)$item->productUnit->conversion_value : 1;
                return $item->qty_billed * $multiplier;
            });
            return (object)[
                'product_name' => $product->name,
                'base_unit' => $product->base_unit ?? 'Satuan Dasar',
                'total_qty' => $totalBaseQty,
            ];
        })->values();

        // Tetap biarkan Semua PO Aktif global (tidak terikat dropdown) agar pantauan menyeluruh
        $activePoOrders = Order::with(['customer', 'poBatch', 'orderItems.product'])
            ->where('business_id', $businessId)
            ->where('status', 'draft')
            ->whereNotNull('po_batch_id') 
            ->orderBy('delivery_date', 'asc') 
            ->get();

        return [
            'todayDeliveries' => $todayDeliveries,
            'packingListToday' => $packingListToday,
            'shoppingListTomorrow' => $shoppingListTomorrow,
            'activePoOrders' => $activePoOrders,
            'availableDates' => $availableDates, // Dikirim ke Blade
            'todayDate' => \Carbon\Carbon::parse($today)->translatedFormat('l, d F Y'),
            'tomorrowDate' => \Carbon\Carbon::parse($tomorrow)->translatedFormat('l, d F Y'),
        ];
    }
};