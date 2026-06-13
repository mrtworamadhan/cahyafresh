<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Ledger;
use App\Models\Customer;
use App\Models\FinanceCategory;
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

    public string $shareLink = '';
    public string $waLink = '';

    public ?string $selectedDate = null;

    public function mount()
    {
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
                DB::transaction(function () use ($order) {
                    
                    if (in_array($order->payment_status, ['paid', 'partial']) && $order->customer_id) {
                        
                        $totalPaid = Ledger::where('reference_type', Order::class)
                            ->where('reference_id', $order->id)
                            ->where('type', 'in')
                            ->sum('amount');

                        if ($totalPaid > 0) {
                            $kategoriSales = FinanceCategory::withoutGlobalScopes()->where('code', 'INC_SALES')->first();
                            $kategoriDeposit = FinanceCategory::withoutGlobalScopes()->where('code', 'LIA_DEP_CUSTOMER')->first();

                            Ledger::create([
                                'business_id' => $order->business_id,
                                'wallet_id' => null, 
                                'finance_category_id' => $kategoriSales?->id,
                                'transaction_date' => now(),
                                'description' => "Pembatalan Nota {$order->order_number}: Pembalikan Omzet Penjualan",
                                'type' => 'out',
                                'amount' => $totalPaid,
                                'contact_type' => Customer::class,
                                'contact_id' => $order->customer_id,
                                'reference_type' => Order::class,
                                'reference_id' => $order->id,
                            ]);

                            Ledger::create([
                                'business_id' => $order->business_id,
                                'wallet_id' => null,
                                'finance_category_id' => $kategoriDeposit?->id,
                                'transaction_date' => now(),
                                'description' => "Pengalihan Dana Pembatalan Nota {$order->order_number} ke Saldo Deposit",
                                'type' => 'in', 
                                'amount' => $totalPaid,
                                'contact_type' => Customer::class,
                                'contact_id' => $order->customer_id,
                                'reference_type' => Order::class,
                                'reference_id' => $order->id,
                            ]);

                            Customer::find($order->customer_id)?->increment('deposit_balance', $totalPaid);
                        }
                    }

                    $order->update(['status' => 'canceled']);
                });

                $this->successMessage = "Pesanan {$order->order_number} berhasil dibatalkan." . 
                    (in_array($this->cancelPaymentStatus, ['paid', 'partial']) ? " Dana pembayaran otomatis dialihkan menjadi saldo Deposit Pelanggan!" : "");
                
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
            $order = Order::with('orderItems')->find($this->completeOrderId);
            
            if ($order && $order->status === 'draft') {
                DB::transaction(function () use ($order) {
                    foreach ($order->orderItems as $item) {
                        $productModel = Product::find($item->product_id);
                        if ($productModel) {
                            $hppDasar = (float) $productModel->base_price;
                            $conversionRate = 1;
                            if (!empty($item->product_unit_id)) {
                                $unitModel = \App\Models\ProductUnit::find($item->product_unit_id);
                                if ($unitModel) { $conversionRate = (float) ($unitModel->conversion_value ?? 1); }
                            }
                            $hppFinal = $hppDasar * $conversionRate;
                            $item->update(['base_price' => $hppFinal]);
                        }
                    }

                    if ($order->delivery_type === 'delivery' && (float)$order->shipping_cost_actual > 0) {
                        $kategoriBebanOngkir = FinanceCategory::withoutGlobalScopes()->where('code', 'OP_SHIPPING')->first();
                        if ($kategoriBebanOngkir) {
                            Ledger::create([
                                'business_id' => $order->business_id,
                                'wallet_id' => null,
                                'finance_category_id' => $kategoriBebanOngkir->id,
                                'transaction_date' => now(),
                                'description' => "Pengakuan Beban Pengiriman/Kurir Nota: {$order->order_number} (Penyelesaian PO)",
                                'type' => 'out',
                                'amount' => (float)$order->shipping_cost_actual,
                                'reference_type' => Order::class,
                                'reference_id' => $order->id,
                            ]);
                        }
                    }

                    $order->update(['status' => 'completed']);
                    if ($order->delivery_type === 'delivery' && $order->delivery) {
                        $order->delivery->update(['status' => 'delivered']);
                    }
                });

                $this->shareLink = url('/invoice/' . $order->order_number);
                $tenant = Filament::getTenant();
                $businessName = $tenant ? $tenant->name : (auth()->user()->businesses()->first()?->name ?? 'Toko Kami');

                $customer = $order->customer_id ? Customer::find($order->customer_id) : null;
                $customerName = $customer ? $customer->name : 'Pelanggan';

                $customerPhone = $customer && $customer->phone ? preg_replace('/[^0-9]/', '', $customer->phone) : '';
                if (str_starts_with($customerPhone, '0')) {
                    $customerPhone = '62' . substr($customerPhone, 1);
                }

                $pesan = "Halo *{$customerName}*,\n\n";
                $pesan .= "Pesanan Anda dengan nomor Nota *#{$order->order_number}* telah selesai diproses dan sukses dikirim/diserahkan.\n\n";
                $pesan .= "Berikut adalah tautan Invoice digital Anda:\n" . $this->shareLink . "\n\n";

                if ($customer && $customer->slug) {
                    $portalLink = url('/portal/' . $customer->slug);
                    $pesan .= "Seluruh update riwayat transaksi & catatan komisi Anda bisa dipantau langsung di portal pelanggan:\n{$portalLink}\n\n";
                }

                $pesan .= "Terima kasih telah berbelanja di *{$businessName}*!";
                $waText = urlencode($pesan);
                $this->waLink = $customerPhone ? "https://wa.me/{$customerPhone}?text={$waText}" : "https://wa.me/?text={$waText}";

                $this->successMessage = "Pesanan {$order->order_number} berhasil diselesaikan. Siklus stok dan kurir telah diperbarui!";
                $this->showCompleteModal = false;
                $this->showSuccessModal = true;
            }
        }
    }

    public function closeSuccessModal()
    {
        $this->showSuccessModal = false;
        $this->shareLink = '';
        $this->waLink = '';
    }

    public function with(): array
    {
        $businessId = Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id;
        
        $availableDates = Order::where('business_id', $businessId)
            ->where('status', 'draft')
            ->whereNotNull('delivery_date')
            ->orderBy('delivery_date', 'asc')
            ->pluck('delivery_date')
            ->map(fn($date) => \Carbon\Carbon::parse($date)->format('Y-m-d'))
            ->unique()
            ->toArray();

        $todayStr = now()->format('Y-m-d');
        if (!in_array($todayStr, $availableDates)) {
            $availableDates[] = $todayStr;
            sort($availableDates);
        }

        $today = $this->selectedDate ?? $todayStr;
        $tomorrow = \Carbon\Carbon::parse($today)->addDay()->format('Y-m-d');

        $todayDeliveries = Order::with(['customer', 'orderItems.product'])
            ->where('business_id', $businessId)
            ->whereDate('delivery_date', $today)
            ->where('status', 'draft')
            ->orderBy('created_at', 'asc')
            ->get();

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
            'availableDates' => $availableDates, 
            'todayDate' => \Carbon\Carbon::parse($today)->translatedFormat('l, d F Y'),
            'tomorrowDate' => \Carbon\Carbon::parse($tomorrow)->translatedFormat('l, d F Y'),
        ];
    }
};