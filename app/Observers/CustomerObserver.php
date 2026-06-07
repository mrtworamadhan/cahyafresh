<?php

namespace App\Observers;

use App\Models\Customer;

class CustomerObserver
{
    /**
     * Handle the Customer "created" event.
     */
    public function created(Customer $customer): void
    {
        //
    }

    /**
     * Handle the Customer "updated" event.
     */
    public function updated(Customer $customer)
    {
        if ($customer->wasChanged('deposit_balance')) {
            $old = $customer->getOriginal('deposit_balance');
            $new = $customer->deposit_balance;
            $diff = $new - $old;

            // Jika ada selisih, catat sebagai penyesuaian saldo (tanpa dompet/via wallet NULL)
            // Ini untuk audit jika admin merubah saldo tanpa lewat tombol Bayar
        }
    }

    /**
     * Handle the Customer "deleted" event.
     */
    public function deleted(Customer $customer): void
    {
        //
    }

    /**
     * Handle the Customer "restored" event.
     */
    public function restored(Customer $customer): void
    {
        //
    }

    /**
     * Handle the Customer "force deleted" event.
     */
    public function forceDeleted(Customer $customer): void
    {
        //
    }
}
