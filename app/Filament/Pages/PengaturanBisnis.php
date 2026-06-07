<?php

namespace App\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PengaturanBisnis extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Profil Bisnis';
    protected string $view = 'filament.pages.pengaturan-bisnis';
    public ?array $data = [];
    public function mount(): void
    {
        $business = auth()->user()->businesses()->first();

        if ($business) {
            $this->form->fill($business->toArray());
        }
    }
    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Dasar')
                    ->description('Atur identitas utama bisnis kamu di sini.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Bisnis / Toko')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Nomor Telepon')
                            ->tel()
                            ->maxLength(255),
                        Textarea::make('address')
                            ->label('Alamat Lengkap')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Tampilan & Branding')
                    ->description('Atur logo, tanda tangan, dan warna tema untuk aplikasi dan struk.')
                    ->schema([
                        FileUpload::make('logo')
                            ->label('Logo Bisnis')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('business-logos')
                            ->maxSize(2048),
                        ColorPicker::make('theme_color')
                            ->label('Warna Tema (Utama)')
                            ->default('#f59e0b'),
                        
                        // --- TAMBAHAN UPLOAD TTD & JABATAN SIGNER ---
                        FileUpload::make('signature')
                            ->label('Tanda Tangan (TTD) Invoice')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('business-signatures')
                            ->maxSize(1024)
                            ->imageCropAspectRatio('2:1') // Biar proporsional untuk TTD
                            ->helperText('Gunakan gambar berlatar transparan (PNG) untuk hasil terbaik.'),
                        TextInput::make('signer_name')
                            ->label('Jabatan Penandatangan')
                            ->placeholder('Contoh: Owner, Finance Manager, Direktur Utama')
                            ->maxLength(255),
                        // --------------------------------------------

                        Textarea::make('invoice_footer_text')
                            ->label('Teks Catatan Kaki (Footer) Invoice')
                            ->placeholder('Contoh: Terima kasih telah berbelanja! Barang yang sudah dibeli tidak dapat ditukar.')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),
                
                Select::make('invoice_template')
                    ->label('Desain Template Invoice')
                    ->options([
                        'default' => 'Default (Standar Profesional)',
                        'bold' => 'Bold (Tegas & Elegan)',
                        'classic' => 'Classic (Formal / Klasik)',
                        'modern' => 'Modern (Aksen Warna Kuat)',
                        'compact' => 'Compact (Hemat Kertas)',
                    ])
                    ->default('default')
                    ->required(),

                ColorPicker::make('invoice_color')
                    ->label('Warna Aksen Khusus Invoice/Nota')
                    ->placeholder('#2563eb')
                    ->default('#2563eb'),

                Section::make('Pengaturan Pajak')
                    ->description('Atur jika bisnis kamu menerapkan pajak pada penjualan.')
                    ->schema([
                        Toggle::make('is_tax_enabled')
                            ->label('Aktifkan Pajak (PPN)')
                            ->live(), 
                        TextInput::make('tax_rate')
                            ->label('Persentase Pajak (%)')
                            ->numeric()
                            ->default(0)
                            ->visible(fn (Get $get) => $get('is_tax_enabled'))
                            ->suffix('%'),
                    ])->columns(2),
                
                Action::make('save')
                    ->label('Simpan Pengaturan')
                    ->color('primary')
                    ->submit('simpanPengaturan'), 
            ])
            ->statePath('data'); 
    }

    protected function getSchemaActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Pengaturan')
                ->color('primary')
                ->submit('simpanPengaturan'), 
        ];
    }

    public function simpanPengaturan(): void
    {
        // 3. Tangkap lagi bisnis yang sedang aktif untuk di-update
        $business = Filament::getTenant() ?? auth()->user()->businesses()->first();

        if ($business) {
            $business->update($this->form->getState());

            Notification::make()
                ->title('Pengaturan berhasil disimpan!')
                ->success()
                ->send();
        }
    }
}
