<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Business;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;

class RegisterBusiness extends RegisterTenant
{
    // Mengubah teks tombol/judul
    public static function getLabel(): string
    {
        return 'Tambah Bisnis Baru';
    }

    // Form isian untuk bisnis baru
    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nama Bisnis')
                    ->required(),
                TextInput::make('address')
                    ->label('Alamat'),
                TextInput::make('phone')
                    ->label('Nomor Telepon'),
            ]);
    }

    // Logika saat tombol simpan diklik
    protected function handleRegistration(array $data): Business
    {
        // 1. Simpan data bisnis ke database
        $business = Business::create($data);

        // 2. Hubungkan otomatis dengan user yang sedang login
        auth()->user()->businesses()->attach($business);

        return $business;
    }
}