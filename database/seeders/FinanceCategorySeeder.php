<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FinanceCategory;

class FinanceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $systemCategories = [
            // =================================================================
            // PENDAPATAN (INCOME) - Laba Rugi (Sisi Masuk)
            // =================================================================
            ['code' => 'INC_SALES', 'name' => 'Pendapatan Penjualan (Omzet Kasir)', 'type' => 'in', 'is_system' => true],
            ['code' => 'INC_AR', 'name' => 'Penerimaan Piutang Pelanggan', 'type' => 'in', 'is_system' => true],
            ['code' => 'INC_SHIPPING', 'name' => 'Pendapatan Ongkos Kirim (Dari Konsumen)', 'type' => 'in', 'is_system' => true],
            ['code' => 'INC_GAIN', 'name' => 'Pendapatan Selisih Lebih Stok (Opname)', 'type' => 'in', 'is_system' => true], // <--- BARU: Pasangan EXP_LOSS
            ['code' => 'INC_OTHER', 'name' => 'Pendapatan Lain-lain (Non-Penjualan)', 'type' => 'in', 'is_system' => true],

            // =================================================================
            // BEBAN OPERASIONAL (EXPENSE) - Laba Rugi (Sisi Keluar)
            // =================================================================
            ['code' => 'OP_COMMISSION', 'name' => 'Beban Komisi & Referral (Pengakuan)', 'type' => 'out', 'is_system' => true], 
            ['code' => 'OP_SHIPPING', 'name' => 'Beban Pengiriman & Ekspedisi (Pengakuan)', 'type' => 'out', 'is_system' => true], 
            ['code' => 'OP_SALARY', 'name' => 'Beban Gaji & Upah', 'type' => 'out', 'is_system' => true],
            ['code' => 'OP_RENT', 'name' => 'Beban Sewa Tempat', 'type' => 'out', 'is_system' => true],
            ['code' => 'OP_UTILITY', 'name' => 'Beban Listrik, Air & Internet', 'type' => 'out', 'is_system' => true],
            ['code' => 'OP_MARKETING', 'name' => 'Beban Iklan & Pemasaran', 'type' => 'out', 'is_system' => true],
            ['code' => 'OP_SUPPLIES', 'name' => 'Perlengkapan Toko (Plastik, ATK, dll)', 'type' => 'out', 'is_system' => true],
            ['code' => 'EXP_PURCHASE', 'name' => 'Pembelian Stok Barang (Restock)', 'type' => 'out', 'is_system' => true],
            ['code' => 'EXP_LOSS', 'name' => 'Beban Kerugian / Kehilangan Stok (Opname)', 'type' => 'out', 'is_system' => true],
            ['code' => 'OP_MISC', 'name' => 'Beban Operasional Lainnya', 'type' => 'out', 'is_system' => true],
            [
                'code' => 'OP_CSR_ZAKAT', 
                'name' => 'Beban Zakat, Infaq, Sedekah & CSR', 
                'type' => 'out', 
                'is_system' => true
            ],
            // =================================================================
            // AKTIVITAS PENDANAAN / EKUITAS - Neraca Sisi Modal
            // =================================================================
            ['code' => 'EQ_MODAL', 'name' => 'Modal Tambahan (Suntik Modal)', 'type' => 'equity', 'is_system' => true],
            ['code' => 'EQ_PRIVE', 'name' => 'Prive (Tarik Keuntungan Eksekutif)', 'type' => 'equity', 'is_system' => true],
            
            // =================================================================
            // LIABILITAS & MUTASI DOMPET - Neraca Sisi Hutang/Titipan (MUTASI MURNI)
            // Kunci Anti-Double Counting Laba Rugi
            // =================================================================
            ['code' => 'LIA_DEP_CUSTOMER', 'name' => 'Deposit / Titipan Uang Konsumen', 'type' => 'in', 'is_system' => true],
            ['code' => 'LIA_AP', 'name' => 'Pembayaran Hutang Usaha / Supplier', 'type' => 'out', 'is_system' => true],
            ['code' => 'ASSET_DEP_SUPPLIER', 'name' => 'Deposit / Titipan Uang ke Supplier', 'type' => 'out', 'is_system' => true],
            ['code' => 'LIA_COMMISSION_PAID', 'name' => 'Pencairan Komisi / Withdraw Agen (Pelunasan)', 'type' => 'out', 'is_system' => true], // <--- BARU: Fase 2 Komisi
            ['code' => 'LIA_SHIPPING_PAID', 'name' => 'Pelunasan Ongkir / Bayar Cash ke Kurir', 'type' => 'out', 'is_system' => true], // <--- BARU: Fase 2 Ongkir
            [
                'code' => 'LIA_CSR_ZAKAT_PAID', 
                'name' => 'Penyaluran Dana Zakat / CSR (Pelunasan Cadangan)', 
                'type' => 'out', 
                'is_system' => true
            ],
        ];

        foreach ($systemCategories as $cat) {
            FinanceCategory::firstOrCreate(
                ['code' => $cat['code']], // Cek agar tidak duplikat
                [
                    'business_id' => null, 
                    'name' => $cat['name'],
                    'type' => $cat['type'],
                    'is_system' => $cat['is_system'],
                    'is_active' => true,
                    'description' => 'Kategori sistem default (Tidak dapat dihapus)'
                ]
            );
        }
    }
}