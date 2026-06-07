<?php

use App\Models\Product;
use App\Models\Wallet;
use App\Models\Ledger;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts::pos')] class extends Component
{
    public string $searchProduct = '';
    
    // State Pembelian
    public array $cart = [];
    public $supplierId = '';
    public bool $showCheckoutModal = false;
    public string $paymentStatus = 'paid';
    public $paymentAmount = 0;
    public $walletId = null;
    public string $invoiceNumber = '';
    
    // Modal Sukses
    public bool $showSuccessModal = false;

    public function addToCart($productId)
    {
        $product = Product::with('units')->find($productId);
        if (!$product) return;

        // Cek jika produk dan satuannya sama
        $existingIndex = collect($this->cart)->search(fn($item) => $item['product_id'] === $productId && $item['unit_id'] === null);

        if ($existingIndex !== false) {
            $this->cart[$existingIndex]['qty']++;
        } else {
            $this->cart[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->cost_price ?? 0, 
                'qty' => 1,
                // --- Tambahan Baru ---
                'unit_id' => null, 
                'unit_name' => 'Satuan Dasar',
                'base_cost' => $product->cost_price ?? 0,
                'available_units' => $product->units->toArray(),
            ];
        }
    }

    public function changeCartUnit($index, $unitId)
    {
        if (empty($unitId)) {
            $this->cart[$index]['unit_id'] = null;
            $this->cart[$index]['unit_name'] = 'Satuan Dasar';
            // Kembalikan ke harga modal dasar jika ada, atau biarkan kasir input
        } else {
            $unit = collect($this->cart[$index]['available_units'])->firstWhere('id', (int)$unitId);
            if ($unit) {
                $this->cart[$index]['unit_id'] = $unit['id'];
                $this->cart[$index]['unit_name'] = $unit['unit_name'];
                // Jika di tabel satuan ada kolom modal per karung, panggil di sini (opsional)
                // $this->cart[$index]['price'] = $unit['unit_cost_price'] ?? 0; 
            }
        }
    }

    public function removeItem($index)
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart); 
    }

    public function openCheckout()
    {
        if (empty($this->cart)) return;
        
        $this->paymentStatus = 'paid';
        $this->walletId = null;
        $this->invoiceNumber = '';
        
        $baseTotal = collect($this->cart)->sum(fn($item) => (float)$item['price'] * (int)($item['qty'] ?? 1));
        $this->paymentAmount = $baseTotal; 
        
        $this->showCheckoutModal = true;
    }

    public function closeCheckout() { $this->showCheckoutModal = false; }

    public function getFinalTotalProperty()
    {
        return collect($this->cart)->sum(fn($item) => (float)$item['price'] * (int)($item['qty'] ?? 1));
    }

    public function submitPurchase()
    {
        if (empty($this->cart)) { session()->flash('error', 'Keranjang pembelian kosong.'); return; }

        $tenant = Filament::getTenant();
        $businessId = $tenant ? $tenant->id : auth()->user()->businesses()->first()?->id;

        if ($this->paymentStatus !== 'unpaid' && !$this->walletId) {
            session()->flash('error', 'Silakan pilih rekening sumber dana.'); return;
        }

        $finalTotal = $this->finalTotal;

        DB::transaction(function () use ($businessId, $finalTotal) {
            
            // 1. CREATE DENGAN FALSE (Agar tidak memicu mutasi stok prematur di Observer)
            $purchase = \App\Models\Purchase::create([
                'business_id' => $businessId,
                'supplier_id' => $this->supplierId ?: null,
                'invoice_number' => $this->invoiceNumber ?: 'PO-' . date('YmdHis'), 
                'purchase_date' => now(),
                'total_amount' => $finalTotal,
                'status' => $this->paymentStatus, 
                'is_stock_received' => false, // <--- SANGAT PENTING!
            ]);

            // 2. Simpan Detail Item
            foreach ($this->cart as $item) {
                \App\Models\PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'product_unit_id' => $item['unit_id'] ?: null, // <--- SIMPAN SATUANNYA
                    'quantity' => (int)$item['qty'],
                    'unit_price' => (float)$item['price'], 
                    'subtotal' => (float)$item['price'] * (int)$item['qty'],
                ]);
            }

            // 3. Jurnal Keuangan
            $paidMoney = $this->paymentStatus === 'unpaid' ? 0 : (float)$this->paymentAmount;
            if ($paidMoney > 0 && $this->walletId) {
                // TARIK KATEGORI PEMBELIAN STOK
                $kategoriBeli = \App\Models\FinanceCategory::where('code', 'EXP_PURCHASE')->first();

                Ledger::create([
                    'business_id' => $businessId, 
                    'wallet_id' => $this->walletId, 
                    'finance_category_id' => $kategoriBeli?->id, // <--- TAMBAHKAN INI
                    'transaction_date' => now(),
                    'description' => "Pembayaran Restock ke Supplier Nota: {$purchase->invoice_number}",
                    'type' => 'out', 
                    'amount' => $paidMoney,
                    'reference_type' => \App\Models\Purchase::class, 
                    'reference_id' => $purchase->id,
                ]);
                Wallet::find($this->walletId)?->decrement('balance', $paidMoney);
            }

            // 4. TRIGGER OBSERVER SECARA PAKSA!
            // Update jadi true. Ini akan memanggil fungsi updated() di PurchaseObserver untuk merata-ratakan HPP & Tambah Stok
            $purchase->update(['is_stock_received' => true]);
        });

        $this->reset(['cart', 'supplierId', 'paymentStatus', 'paymentAmount', 'walletId', 'invoiceNumber']);
        $this->showCheckoutModal = false;
        $this->showSuccessModal = true;
    }

    public function closeSuccessModal() { $this->showSuccessModal = false; }

    public function with(): array
    {
        $tenant = Filament::getTenant();
        $businessId = $tenant ? $tenant->id : auth()->user()->businesses()->first()?->id;

        $products = Product::with('units')->where('business_id', $businessId) 
            ->where('name', 'like', '%' . $this->searchProduct . '%')->limit(20)->get();

        $total = collect($this->cart)->sum(fn($item) => (float)$item['price'] * (int)($item['qty'] ?? 1));
        
        return [
            'products' => $products,
            'wallets' => Wallet::where('business_id', $businessId)->where('is_active', true)->get(),
            'suppliers' => \App\Models\Supplier::where('business_id', $businessId)->orderBy('name')->get(),
            'total' => $total,
        ];
    }
};