<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Filament\Facades\Filament;
use App\Models\Customer;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Pelanggan')
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('Nomor HP')
                    ->tel()
                    ->maxLength(255),
                TextInput::make('referral_code')
                    ->label('Kode Referral (Otomatis)')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->default(fn () => 'REF-' . strtoupper(Str::random(6))), 

                TextInput::make('upline_code')
                    ->label('Kode Referral Pengundang (Opsional)')
                    ->placeholder('Ketik kode referral upline di sini')
                    ->live(debounce: 500)
                    ->exists('customers', 'referral_code')
                    ->validationMessages([
                        'exists' => 'Kode referral pengundang tidak ditemukan.',
                    ])
                    ->helperText(function (Get $get) {
                        $code = $get('upline_code');
                        
                        if (!$code) return null;
                        
                        $upline = Customer::where('referral_code', $code)
                            ->where('business_id', Filament::getTenant()->id)
                            ->first();
                            
                        if ($upline) {
                            return new HtmlString('✅ Upline ditemukan: ' . $upline->name . '');
                        }
                        
                        return new HtmlString('❌ Kode tidak valid / tidak ditemukan');
                    })

                    ->afterStateUpdated(function (Set $set, ?string $state) {
                        // Jika input kosong, reset pilihan Select
                        if (blank($state)) {
                            $set('referred_by_id', null);
                            return;
                        }

                        // Cari upline berdasarkan kode
                        $upline = Customer::where('referral_code', $state)
                            ->where('business_id', Filament::getTenant()->id)
                            ->first();

                        // Jika ketemu, set nilai pada komponen Select secara otomatis
                        if ($upline) {
                            $set('referred_by_id', $upline->id);
                        } else {
                            $set('referred_by_id', null);
                        }
                    }),

                Select::make('referred_by_id')
                    ->label('Diundang Oleh (Otomatis terisi jika kode valid)')
                    ->relationship('referrer', 'name')
                    ->searchable()
                    ->preload(),

                Textarea::make('address')
                    ->label('Alamat Lengkap')
                    ->columnSpanFull(),
            ]);
    }
}
