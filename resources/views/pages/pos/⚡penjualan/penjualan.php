<?php

use App\Models\Customer;
use App\Models\Ledger;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Wallet;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Filament\Facades\Filament;

new #[Layout('layouts::pos')] class extends Component {
    public string $themeColor = '#1e9750';
    public string $searchProduct = '';

    // State Penjualan
    public array $cart = [];
    public $customerId = '';
    public bool $showCheckoutModal = false;
    public $poBatchId = null;
    public $deliveryDate = null;
    public string $paymentStatus = 'paid';
    public bool $applyCommission = false;
    public $discount = 0;
    public $commission = 0;
    public string $commissionNote = '';
    public $commissionRecipientId = null;
    public $paymentAmount = 0;
    public $walletId = null;
    public string $orderNote = '';

    // Pengiriman
    public string $deliveryType = 'pickup';
    public $shippingFeeBilled = 0;
    public $shippingCostActual = 0;
    public $courierId = null;

    // Modal Sukses
    public bool $showSuccessModal = false;
    public string $latestOrderNumber = '';
    public string $shareLink = '';
    public string $waLink = '';

    public $editOrderId = null;

    public function mount()
    {
        if (request()->has('edit_order')) {
            $this->loadOrderForEdit(request()->query('edit_order'));
        }
    }

    public function loadOrderForEdit($orderId)
    {
        $order = Order::with(['orderItems.product.units', 'delivery'])->find($orderId);

        if (!$order || $order->status !== 'draft') {
            session()->flash('error', 'Pesanan tidak ditemukan atau tidak dapat diedit karena sudah diproses.');
            return;
        }

        $this->editOrderId = $order->id;
        $this->customerId = $order->customer_id;
        $this->poBatchId = $order->po_batch_id;
        $this->deliveryDate = $order->delivery_date;
        $this->deliveryType = $order->delivery_type;
        $this->shippingFeeBilled = $order->shipping_fee_billed;
        $this->shippingCostActual = $order->shipping_cost_actual;
        $this->discount = $order->discount_amount;
        $this->paymentStatus = $order->payment_status;

        $this->orderNote = $order->notes;
        $this->courierId = $order->delivery?->courier_id;

        $this->cart = [];
        foreach ($order->orderItems as $item) {
            $unitName = 'Satuan Dasar';
            if ($item->product_unit_id) {
                $unit = collect($item->product->units)->firstWhere('id', $item->product_unit_id);
                $unitName = $unit ? $unit->unit_name : 'Satuan Dasar';
            }

            $this->cart[] = [
                'product_id' => $item->product_id,
                'name' => $item->product->name,
                'price' => $item->unit_price,
                'qty_billed' => $item->qty_billed,
                'qty_bonus' => $item->qty_bonus,
                'commission_per_unit' => $item->commission_per_unit,
                'unit_id' => $item->product_unit_id,
                'unit_name' => $unitName,
                'base_price' => $item->product->selling_price,
                'available_units' => $item->product->units->toArray(),
            ];
        }

        if ($order->commission_amount > 0) {
            $this->applyCommission = true;
            $this->commission = $order->commission_amount;
            $this->commissionRecipientId = $order->commission_recipient_id;
            $this->commissionNote = $order->commission_note;
        } else {
            $this->applyCommission = false;
            $this->commission = 0;
            $this->commissionRecipientId = null;
            $this->commissionNote = '';
        }

        session()->flash('success', 'Berhasil memuat Nota ' . $order->order_number . ' untuk diubah.');
    }

    public function addToCart($productId)
    {
        $product = Product::with('units')->find($productId);
        if (!$product)
            return;

        $existingIndex = collect($this->cart)->search(fn($item) => $item['product_id'] === $productId && $item['unit_id'] === null);

        if ($existingIndex !== false) {
            $this->cart[$existingIndex]['qty_billed']++;
        } else {
            $this->cart[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->selling_price,
                'qty_billed' => 1,
                'qty_bonus' => 0,
                'commission_per_unit' => 0,
                'unit_id' => null,
                'unit_name' => $product->base_unit ?? 'Satuan',
                'base_price' => $product->selling_price,
                'available_units' => $product->units->toArray(),
            ];
        }
    }

    public function changeCartUnit($index, $unitId)
    {
        if (empty($unitId)) {
            $this->cart[$index]['unit_id'] = null;
            $this->cart[$index]['unit_name'] = $this->cart[$index]['base_unit'];
            $this->cart[$index]['price'] = $this->cart[$index]['base_price'];
        } else {
            $unit = collect($this->cart[$index]['available_units'])->firstWhere('id', (int) $unitId);
            if ($unit) {
                $this->cart[$index]['unit_id'] = $unit['id'];
                $this->cart[$index]['unit_name'] = $unit['unit_name'];
                $this->cart[$index]['price'] = $unit['unit_selling_price'];
            }
        }
    }

    public function validateQty($index) { $this->validateCart($index); }
    public function increaseQty($index) { $this->cart[$index]['qty_billed']++; $this->validateCart($index); }
    public function decreaseQty($index) { if ($this->cart[$index]['qty_billed'] > 1) { $this->cart[$index]['qty_billed']--; $this->validateCart($index); } else { $this->removeItem($index); } }
    public function removeItem($index) { unset($this->cart[$index]); $this->cart = array_values($this->cart); }
    
    public function validateCart($index)
    {
        $this->cart[$index]['qty_billed'] = max(1, (int) ($this->cart[$index]['qty_billed'] ?? 1));
        $this->cart[$index]['qty_bonus'] = max(0, (int) ($this->cart[$index]['qty_bonus'] ?? 0));
        $this->cart[$index]['price'] = max(0, (float) ($this->cart[$index]['price'] ?? 0));
    }

    public function openCheckout()
    {
        $totalCommissionFromCart = collect($this->cart)->sum(function($item) {
            return (float)($item['commission_per_unit'] ?? 0) * (int)($item['qty_billed'] ?? 1);
        });

        if (!$this->editOrderId) {
            if (empty($this->deliveryDate)) $this->deliveryDate = now()->format('Y-m-d');
            if (empty($this->paymentStatus)) $this->paymentStatus = 'paid';
            if (empty($this->deliveryType)) $this->deliveryType = 'pickup';

            if ($totalCommissionFromCart > 0) {
                $this->applyCommission = true;
                $this->commission = $totalCommissionFromCart;
                
                if (empty($this->commissionRecipientId) && $this->customerId) {
                    $customer = Customer::find($this->customerId);
                    if ($customer && $customer->referred_by_id) {
                        $this->commissionRecipientId = $customer->referred_by_id;
                        $this->commissionNote = 'Komisi Referral (Auto)';
                    }
                }
            } elseif ($totalCommissionFromCart == 0 && empty($this->commissionRecipientId)) {
                $this->applyCommission = false;
                $this->commission = 0;
                $this->commissionRecipientId = null;
                $this->commissionNote = '';
            }
        } else {
            if (empty($this->deliveryDate)) $this->deliveryDate = now()->format('Y-m-d');
            if (empty($this->deliveryType)) $this->deliveryType = 'pickup';
            if (empty($this->paymentStatus)) $this->paymentStatus = 'paid';

            if ($totalCommissionFromCart > 0) {
                $this->applyCommission = true;
                $this->commission = $totalCommissionFromCart;
                if (empty($this->commissionRecipientId) && $this->customerId) {
                    $customer = Customer::find($this->customerId);
                    if ($customer && $customer->referred_by_id) {
                        $this->commissionRecipientId = $customer->referred_by_id;
                        $this->commissionNote = 'Komisi Referral (Auto)';
                    }
                }
            }
        }

        $totalAmount = collect($this->cart)->sum(fn($item) => (float)$item['price'] * (int)$item['qty_billed']);
        $this->finalTotal = max(0, $totalAmount - (float)$this->discount) + (float)$this->shippingFeeBilled;
        $this->showCheckoutModal = true;
    }

    public function closeCheckout() { $this->showCheckoutModal = false; }
    public function updatedPaymentStatus($value) { $this->paymentAmount = $value === 'paid' ? $this->finalTotal : 0; }
    public function updatedDiscount() { if ($this->paymentStatus === 'paid') $this->paymentAmount = $this->finalTotal; }

    public function getFinalTotalProperty()
    {
        $baseTotal = collect($this->cart)->sum(fn($item) => (float) $item['price'] * (int) ($item['qty_billed'] ?? 1));
        return max(0, $baseTotal - (float) $this->discount) + (float) $this->shippingFeeBilled;
    }

    public function updatedCustomerId($value)
    {
        if ($value) {
            $customer = Customer::find($value);
            if ($customer && $customer->referred_by_id) {
                $this->commissionRecipientId = $customer->referred_by_id;
                $this->commissionNote = 'Komisi Referral (Auto)';
                $this->applyCommission = true;
            } else {
                $this->commissionRecipientId = null;
                $this->commissionNote = '';
                $this->applyCommission = false;
            }
        } else {
            $this->commissionRecipientId = null;
            $this->applyCommission = false;
        }
    }

    public function submitOrder()
    {
        if (empty($this->cart)) {
            session()->flash('error', 'Keranjang belanja kosong.');
            return;
        }

        $tenant = Filament::getTenant();
        $businessId = $tenant ? $tenant->id : auth()->user()->businesses()->first()?->id;

        if ($this->paymentStatus !== 'unpaid' && !$this->walletId) {
            session()->flash('error', 'Silakan pilih dompet/rekening pembayaran.');
            return;
        }

        $totalAmount = collect($this->cart)->sum(fn($item) => (float) $item['price'] * (int) $item['qty_billed']);
        $finalTotal = max(0, $totalAmount - (float) $this->discount) + (float) $this->shippingFeeBilled;

        $orderNumber = '';

        DB::transaction(function () use ($businessId, &$orderNumber, $finalTotal) {
            $oldStatus = 'draft';

            if ($this->editOrderId) {
                $order = Order::find($this->editOrderId);
                $orderNumber = $order->order_number;
                $oldStatus = $order->status;

                $order->update([
                    'customer_id' => $this->customerId ?: null,
                    'delivery_date' => $this->deliveryDate ?: null,
                    'total_amount' => $finalTotal,
                    'discount_amount' => (float) $this->discount,
                    'commission_amount' => $this->applyCommission ? (float) $this->commission : 0,
                    'commission_recipient_id' => $this->applyCommission ? $this->commissionRecipientId : null,
                    'commission_note' => $this->applyCommission ? $this->commissionNote : null,
                    'po_batch_id' => $this->poBatchId ?: null,
                    'payment_status' => $this->paymentStatus,
                    'delivery_type' => $this->deliveryType,
                    'shipping_fee_billed' => $this->deliveryType === 'delivery' ? (float) $this->shippingFeeBilled : 0,
                    'shipping_cost_actual' => $this->deliveryType === 'delivery' ? (float) $this->shippingCostActual : 0,
                    'notes' => $this->orderNote,
                ]);

                OrderItem::where('order_id', $order->id)->delete();
            } else {
                $lastOrder = Order::where('business_id', $businessId)->latest('id')->first();
                $orderNumber = (!$lastOrder || empty($lastOrder->order_number)) ? 'INV-0001' : 'INV-' . str_pad((int) substr($lastOrder->order_number, 4) + 1, 4, '0', STR_PAD_LEFT);

                $order = Order::create([
                    'business_id' => $businessId,
                    'customer_id' => $this->customerId ?: null,
                    'order_number' => $orderNumber,
                    'order_date' => now(),
                    'delivery_date' => $this->deliveryDate ?: null,
                    'total_amount' => $finalTotal,
                    'discount_amount' => (float) $this->discount,
                    'commission_amount' => $this->applyCommission ? (float) $this->commission : 0,
                    'commission_recipient_id' => $this->applyCommission ? $this->commissionRecipientId : null,
                    'commission_note' => $this->applyCommission ? $this->commissionNote : null,
                    'po_batch_id' => $this->poBatchId ?: null,
                    'status' => 'draft', 
                    'payment_status' => $this->paymentStatus,
                    'delivery_type' => $this->deliveryType,
                    'shipping_fee_billed' => $this->deliveryType === 'delivery' ? (float) $this->shippingFeeBilled : 0,
                    'shipping_cost_actual' => $this->deliveryType === 'delivery' ? (float) $this->shippingCostActual : 0,
                    'notes' => $this->orderNote,
                ]);
            }

            foreach ($this->cart as $item) {
                $productModel = Product::find($item['product_id']);
                
                $hppDasar = $productModel ? (float) $productModel->base_price : 0;
                $conversionRate = 1; 

                if (!empty($item['unit_id'])) {
                    $unitModel = \App\Models\ProductUnit::find($item['unit_id']);
                    if ($unitModel) {
                        $conversionRate = (float) ($unitModel->conversion_value ?? 1);
                    }
                }

                $hppFinal = $hppDasar * $conversionRate;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'qty_billed' => (int) $item['qty_billed'],
                    'qty_bonus' => (int) $item['qty_bonus'],
                    'unit_price' => (float) $item['price'],
                    'base_price' => $hppFinal, 
                    'product_unit_id' => $item['unit_id'] ?: null,
                    'commission_per_unit' => $this->applyCommission ? (float) ($item['commission_per_unit'] ?? 0) : 0,
                    'subtotal' => (float) $item['price'] * (int) $item['qty_billed'],
                ]);
            }

            if ($this->deliveryType === 'delivery') {
                $delivery = \App\Models\Delivery::where('order_id', $order->id)->first();
                if (!$delivery) {
                    \App\Models\Delivery::create([
                        'business_id' => $businessId,
                        'order_id' => $order->id,
                        'courier_id' => $this->courierId ?: null,
                        'shipping_fee_billed' => (float) $this->shippingFeeBilled,
                        'shipping_cost_actual' => (float) $this->shippingCostActual,
                        'tracking_code' => 'DLV-' . date('Ymd') . '-' . rand(1000, 9999),
                        'access_pin' => str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT),
                        'status' => 'pending'
                    ]);
                } else {
                    $delivery->update([
                        'courier_id' => $this->courierId ?: null,
                        'shipping_fee_billed' => (float) $this->shippingFeeBilled,
                        'shipping_cost_actual' => (float) $this->shippingCostActual,
                    ]);
                }
            }

            $isPoOrFutureDelivery = $this->poBatchId || ($this->deliveryDate && $this->deliveryDate !== now()->format('Y-m-d'));
            $newStatus = $isPoOrFutureDelivery ? 'draft' : 'completed';

            $order->update([
                'status' => $newStatus
            ]);

            // ==============================================================
            // LIKECYCLE AKUNTANSI & INVENTORY ACCRUAL (SAAT TRANSISI KE COMPLETED)
            // ==============================================================
            if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                

                // 2. Jurnal Pengakuan Beban Komisi Gantung (wallet_id => null)
                if ($this->applyCommission && $this->commissionRecipientId && $this->commission > 0) {
                    $kategoriKomisi = \App\Models\FinanceCategory::withoutGlobalScopes()->where('code', 'OP_COMMISSION')->first();
                    if ($kategoriKomisi) {
                        Ledger::create([
                            'business_id' => $businessId,
                            'wallet_id' => null, // Non-Tunai (Gantung Kewajiban)
                            'finance_category_id' => $kategoriKomisi->id,
                            'transaction_date' => now(),
                            'description' => "Pengakuan Beban Komisi Nota: {$orderNumber}",
                            'type' => 'out',
                            'amount' => (float) $this->commission,
                            'contact_type' => Customer::class,
                            'contact_id' => $this->commissionRecipientId,
                            'reference_type' => Order::class,
                            'reference_id' => $order->id,
                        ]);
                    }

                    // Naikkan Hutang Komisi Agen di Sisi Pasiva Neraca
                    Customer::find($this->commissionRecipientId)?->increment('commission_balance', (float) $this->commission);
                }

                // 3. Jurnal Pengakuan Beban Ongkir Gantung (wallet_id => null)
                if ($this->deliveryType === 'delivery' && (float)$this->shippingCostActual > 0) {
                    $kategoriBebanOngkir = \App\Models\FinanceCategory::withoutGlobalScopes()->where('code', 'OP_SHIPPING')->first();
                    if ($kategoriBebanOngkir) {
                        Ledger::create([
                            'business_id' => $businessId,
                            'wallet_id' => null, // Non-Tunai (Gantung Kewajiban Kurir)
                            'finance_category_id' => $kategoriBebanOngkir->id,
                            'transaction_date' => now(),
                            'description' => "Pengakuan Beban Pengiriman/Kurir Nota: {$orderNumber}",
                            'type' => 'out',
                            'amount' => (float) $this->shippingCostActual,
                            'reference_type' => Order::class,
                            'reference_id' => $order->id,
                        ]);
                    }
                }
            }

            // ==============================================================
            // PENCATATAN MUTASI TUNAI/BANK JIKA ADA UANG MASUK (KASIR)
            // ==============================================================
            $paidMoney = $this->paymentStatus === 'unpaid' ? 0 : (float) $this->paymentAmount;

            if ($paidMoney > 0 && $this->walletId) {
                
                // Gembok Keamanan: Cegah double saldo kas jika mengedit PO yang sudah ter-jurnal cash
                $hasExistingPayment = Ledger::where('reference_type', Order::class)
                    ->where('reference_id', $order->id)
                    ->where('type', 'in')
                    ->exists();

                if (!$hasExistingPayment) {
                    $kategoriPenjualan = \App\Models\FinanceCategory::withoutGlobalScopes()->where('code', 'INC_SALES')->first();
                    $kategoriOngkir = \App\Models\FinanceCategory::withoutGlobalScopes()->where('code', 'INC_SHIPPING')->first();

                    $tagihanOngkir = $this->deliveryType === 'delivery' ? (float) $this->shippingFeeBilled : 0;
                    $ongkirDibayar = min($paidMoney, $tagihanOngkir);
                    $barangDibayar = $paidMoney - $ongkirDibayar;

                    if ($barangDibayar > 0) {
                        Ledger::create([
                            'business_id' => $businessId,
                            'wallet_id' => $this->walletId,
                            'finance_category_id' => $kategoriPenjualan?->id,
                            'transaction_date' => now(),
                            'description' => "Pembayaran Barang Nota: {$orderNumber}",
                            'type' => 'in',
                            'amount' => $barangDibayar,
                            'contact_type' => Customer::class,
                            'contact_id' => $this->customerId ?: null,
                            'reference_type' => Order::class,
                            'reference_id' => $order->id,
                        ]);
                    }

                    if ($ongkirDibayar > 0) {
                        Ledger::create([
                            'business_id' => $businessId,
                            'wallet_id' => $this->walletId,
                            'finance_category_id' => $kategoriOngkir?->id,
                            'transaction_date' => now(),
                            'description' => "Pendapatan Ongkir Nota: {$orderNumber}",
                            'type' => 'in',
                            'amount' => $ongkirDibayar,
                            'contact_type' => Customer::class,
                            'contact_id' => $this->customerId ?: null,
                            'reference_type' => Order::class,
                            'reference_id' => $order->id,
                        ]);
                    }

                    Wallet::find($this->walletId)?->increment('balance', $paidMoney);
                }
            }
        });

        $this->latestOrderNumber = $orderNumber;
        $this->shareLink = url('/invoice/' . $orderNumber);
        $businessName = $tenant ? $tenant->name : (auth()->user()->businesses()->first()?->name ?? 'Toko Kami');

        $customer = $this->customerId ? Customer::find($this->customerId) : null;
        $customerName = $customer ? $customer->name : 'Pelanggan';

        $customerPhone = $customer && $customer->phone ? preg_replace('/[^0-9]/', '', $customer->phone) : '';
        if (str_starts_with($customerPhone, '0')) {
            $customerPhone = '62' . substr($customerPhone, 1);
        }

        $pesan = "Halo *{$customerName}*,\n\n";
        $pesan .= "Berikut adalah tautan Invoice untuk pembelanjaan Anda:\n" . $this->shareLink . "\n\n";

        if ($customer && $customer->slug) {
            $portalLink = url('/portal/' . $customer->slug);
            $pesan .= "Update pesanan dan riwayat tagihan Anda juga sudah tersedia di portal pelanggan, silakan cek di:\n{$portalLink}\n\n";
        }

        $pesan .= "Terima kasih telah berbelanja di *{$businessName}*!";
        $waText = urlencode($pesan);
        $this->waLink = $customerPhone ? "https://wa.me/{$customerPhone}?text={$waText}" : "https://wa.me/?text={$waText}";

        $this->reset(['cart', 'customerId', 'discount', 'commission', 'commissionRecipientId', 'commissionNote', 'paymentStatus', 'paymentAmount', 'walletId', 'orderNote', 'applyCommission', 'editOrderId']);

        $this->showCheckoutModal = false;
        $this->showSuccessModal = true;
    }

    public function closeSuccessModal()
    {
        $this->showSuccessModal = false;
        $this->latestOrderNumber = '';
        $this->shareLink = '';
        $this->waLink = '';
    }

    public function with(): array
    {
        $tenant = Filament::getTenant();
        $businessId = $tenant ? $tenant->id : auth()->user()->businesses()->first()?->id;

        $products = Product::with('units')->where('business_id', $businessId)
            ->where('name', 'like', '%' . $this->searchProduct . '%')->limit(20)->get();

        $total = collect($this->cart)->sum(fn($item) => (float) $item['price'] * (int) ($item['qty_billed'] ?? 1));

        return [
            'products' => $products,
            'customers' => Customer::where('business_id', $businessId)->orderBy('name')->get(),
            'wallets' => Wallet::where('business_id', $businessId)->where('is_active', true)->get(),
            'poBatches' => \App\Models\PoBatch::where('business_id', $businessId)->latest()->get(),
            'couriers' => \App\Models\Courier::where('business_id', $businessId)->where('is_active', true)->orderBy('name')->get(),
            'total' => $total,
        ];
    }
};