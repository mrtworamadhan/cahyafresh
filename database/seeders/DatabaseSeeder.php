<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Business;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Buat Bisnis / Toko Default Pertama
        $business = Business::firstOrCreate(
            ['name' => 'Cahya Fresh'],
            [
                'phone' => '081277761133',
                'address' => 'Jl. Bougenvill, Blok H15 No.6, Alam Tirta, Pagelaran, Kec. Ciomas, Kab Bogor, Jawa Barat 16610',
                'theme_color' => '#03681c',
            ]
        );

        // 2. Buat Akun Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@cahyafresh.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('cahyaFresh123'), // Password default: cahyaFresh123
            ]
        );

        // 3. Kaitkan Super Admin dengan Bisnis Utama (Tabel Pivot business_user)
        if (!$superAdmin->businesses()->where('business_id', $business->id)->exists()) {
            $superAdmin->businesses()->attach($business->id);
        }

        // 4. Buat Role 'super_admin' khusus untuk toko ini (Syarat dari Filament Shield)
        $roleSuperAdmin = Role::firstOrCreate([
            'name' => config('filament-shield.super_admin.name', 'super_admin'),
            'guard_name' => config('filament.auth.guard', 'web'),
            'business_id' => $business->id,
        ]);

        // 5. Berikan jabatan super_admin ke akun tersebut
        if (!$superAdmin->hasRole($roleSuperAdmin->name)) {
            $superAdmin->assignRole($roleSuperAdmin);
        }

        // 6. Jalankan Seeder Kategori Keuangan (Tulang punggung akuntansi kita)
        $this->call([
            FinanceCategorySeeder::class,
        ]);
        
        $this->command->info('Aplikasi siap! Login dengan email: admin@cahyafresh.com | Password: cahyaFresh123');
    }
}