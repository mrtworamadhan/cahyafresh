<?php

namespace App\Observers;

use App\Models\Supplier;

class SupplierObserver
{
    /**
     * Handle the Supplier "created" event.
     */
    public function created(Supplier $supplier): void
    {
        //
    }

    /**
     * Handle the Supplier "updated" event.
     */
    public function updated(Supplier $supplier)
    {
        if ($supplier->wasChanged('deposit_balance')) {
            $old = $supplier->getOriginal('deposit_balance');
            $new = $supplier->deposit_balance;
            $diff = $new - $old;

            // Jika ada selisih, catat sebagai penyesuaian saldo (tanpa dompet/via wallet NULL)
            // Ini untuk audit jika admin merubah saldo tanpa lewat tombol Bayar
        }
    }

    /**
     * Handle the Supplier "deleted" event.
     */
    public function deleted(Supplier $supplier): void
    {
        //
    }

    /**
     * Handle the Supplier "restored" event.
     */
    public function restored(Supplier $supplier): void
    {
        //
    }

    /**
     * Handle the Supplier "force deleted" event.
     */
    public function forceDeleted(Supplier $supplier): void
    {
        //
    }
}
