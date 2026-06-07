<?php

use App\Models\Order;
use App\Models\OrderItem;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts::pos')] class extends Component
{
    // State Modal Sukses
    public bool $showSuccessModal = false;
    public string $successMessage = '';

    // ==========================================
    // STATE & FUNGSI MODAL BATAL (CANCEL)
    // ==========================================
    public bool $showCancelModal = false;
    public ?int $cancelOrderId = null;
    public string $cancelOrderNumber = '';
    public $cancelOrderAmount = 0;
    public string $cancelPaymentStatus = '';

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

    // ==========================================
    // STATE & FUNGSI MODAL SELESAI (COMPLETE)
    // ==========================================
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

    // ==========================================
    // FUNGSI TUTUP MODAL SUKSES
    // ==========================================
    public function closeSuccessModal()
    {
        $this->showSuccessModal = false;
    }

    // ==========================================
    // RENDER DATA KE VIEW (BLADE)
    // ==========================================
    public function with(): array
    {
        $businessId = Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id;
        
        $today = now()->format('Y-m-d');
        $tomorrow = now()->addDay()->format('Y-m-d');

        // 1. Data Kiriman Hari Ini (Status masih draft)
        $todayDeliveries = Order::with(['customer', 'orderItems.product'])
            ->where('business_id', $businessId)
            ->whereDate('delivery_date', $today)
            ->where('status', 'draft')
            ->orderBy('created_at', 'asc')
            ->get();

        // 2. PACKING LIST (KONVERSI KE SATUAN DASAR)
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

        // 3. SHOPPING LIST (KONVERSI KE SATUAN DASAR UNTUK BESOK)
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

        // 4. SEMUA PO AKTIF (Pemantauan Keseluruhan)
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
            'todayDate' => now()->translatedFormat('l, d F Y'),
            'tomorrowDate' => now()->addDay()->translatedFormat('l, d F Y'),
        ];
    }
};