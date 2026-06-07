<?php

namespace App\Filament\Pages;

use App\Models\MonthlyClosing;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Purchase;
use App\Models\Ledger;
use App\Models\Wallet;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\FinanceCategory;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithHeaderActions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use UnitEnum;
use Filament\Pages\Page;
use Filament\Facades\Filament;
// Forms
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\DatePicker;
// Infolists (Untuk Laba Rugi)
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Components\TextEntry;
// Tables (Untuk Piutang)
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LaporanKeuangan extends Page implements HasForms, HasInfolists, HasTable, HasActions
{
    use InteractsWithForms, InteractsWithInfolists, InteractsWithTable, InteractsWithActions, InteractsWithHeaderActions, HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Keuangan';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Laporan Keuangan';
    protected static ?string $title = 'Laporan Keuangan Eksekutif';

    protected string $view = 'filament.pages.laporan-keuangan';

    public ?array $data = [];
    public string $activeTab = 'laba_rugi';

    public function mount(): void
    {
        $this->form->fill([
            'report_mode' => 'live', 
            'start_date' => now()->startOfMonth()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            
            Action::make('tutup_buku')
                ->label('Tutup Buku (Akhir Bulan)')
                ->icon('heroicon-o-lock-closed')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Tutup Buku')
                ->modalDescription('Tindakan ini akan mengunci/menyimpan kondisi Neraca, Piutang, Hutang, dan Laba/Rugi saat ini secara permanen ke dalam Arsip. Yakin ingin melanjutkan?')
                ->action(function () {
                    $businessId = Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id;
                    $liveData = $this->getLiveData(); // Jepret foto data sekarang

                    MonthlyClosing::create([
                        'business_id' => $businessId,
                        'period_name' => now()->translatedFormat('F Y'), // Contoh: "Juni 2024"
                        'closing_date' => now(),
                        'snapshot_data' => $liveData, // Simpan array Laba Rugi & Neraca ke JSON
                    ]);

                    Notification::make()
                        ->title('Tutup Buku Berhasil!')
                        ->body('Laporan bulan ini telah dikunci ke dalam arsip.')
                        ->success()
                        ->send();
                        
                    // Otomatis refresh opsi dropdown
                    $this->form->fill(['report_mode' => 'live']); 
                }),
        ];
    }

    public function form(Schema $form): Schema
    {
        $businessId = Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id;
        
        $arsipOptions = MonthlyClosing::where('business_id', $businessId)
            ->orderBy('created_at', 'desc')
            ->pluck('period_name', 'id')
            ->toArray();

        return $form
            ->schema([
                Select::make('report_mode')
                    ->label('Pilih Mode Laporan')
                    ->options(['live' => 'Periode Berjalan (Live)'] + $arsipOptions)
                    ->live()
                    ->required(),

                Grid::make(2)
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Dari Tanggal')
                            ->live()
                            ->required(),
                        DatePicker::make('end_date')
                            ->label('Sampai Tanggal')
                            ->live()
                            ->required(),
                    ])
                    ->visible(fn (Get $get) => $get('report_mode') === 'live'), // Sembunyikan tanggal kalau buka arsip
            ])
            ->statePath('data');
    }

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->state($this->getReportData())
            ->schema([
                Tabs::make('LaporanTabs')
                    ->tabs([
                        // TAB 1: LABA RUGI
                        Tab::make('Laba / Rugi')
                            ->icon('heroicon-o-document-currency-dollar')
                            ->schema([
                                Section::make('Pendapatan & Harga Pokok')
                                    ->schema([
                                        TextEntry::make('omzet_barang')->label('Pendapatan Penjualan')->money('IDR')->color('success'),
                                        TextEntry::make('omzet_ongkir')->label('Pendapatan Ongkir')->money('IDR')->color('success'),
                                        TextEntry::make('hpp')->label('Harga Pokok Penjualan (HPP)')->money('IDR')->color('danger'),
                                        TextEntry::make('laba_kotor')->label('Laba Kotor (Gross Profit)')->money('IDR')->weight('black')->size(TextSize::Large),
                                    ])->columns(2),

                                Section::make('Rincian Beban Operasional')
                                    ->schema($this->getBebanSchema())
                                    ->columns(2),

                                Section::make('Kesimpulan')
                                    ->schema([
                                        TextEntry::make('total_beban')->label('Total Beban Operasional')->money('IDR')->color('danger'),
                                        TextEntry::make('laba_bersih')
                                            ->label('LABA BERSIH (NET PROFIT)')
                                            ->money('IDR')
                                            ->weight('black')
                                            ->size(TextSize::Large)
                                            ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                                    ])->columns(2),
                            ]),

                        Tab::make('Arus Kas (Cashflow)')
                            ->icon('heroicon-o-arrow-path-rounded-square')
                            ->schema([
                                Section::make('Kas Masuk (Cash In)')
                                    ->icon('heroicon-o-arrow-down-left')
                                    ->schema($this->getSchemaKasMasuk())
                                    ->columns(2),
                                    
                                Section::make('Kas Keluar (Cash Out)')
                                    ->icon('heroicon-o-arrow-up-right')
                                    ->schema($this->getSchemaKasKeluar())
                                    ->columns(2),
                                    
                                Section::make('Ringkasan Arus Kas')
                                    ->schema([
                                        TextEntry::make('total_kas_masuk')->label('Total Kas Masuk')->money('IDR')->color('success'),
                                        TextEntry::make('total_kas_keluar')->label('Total Kas Keluar')->money('IDR')->color('danger'),
                                        TextEntry::make('net_cashflow')
                                            ->label('ARUS KAS BERSIH (NET CASHFLOW)')
                                            ->money('IDR')
                                            ->weight('black')
                                            ->size(TextSize::Large)
                                            ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                                    ])->columns(2),
                            ]),

                        // TAB 2: PIUTANG (MENGGUNAKAN REPEATABLE ENTRY)
                        Tab::make('Piutang Pelanggan')
                            ->icon('heroicon-o-arrow-right-end-on-rectangle')
                            ->schema([
                                TextEntry::make('total_piutang')
                                    ->label('TOTAL PIUTANG KESELURUHAN')
                                    ->money('IDR')
                                    ->weight('black')
                                    ->color('danger')
                                    ->size(TextSize::Large),
                                    
                                RepeatableEntry::make('piutang_list')
                                    ->label('Daftar Rincian Piutang')
                                    ->schema([
                                        TextEntry::make('date')->label('Tgl Nota'),
                                        TextEntry::make('order_number')->label('No. Nota')->weight('bold'),
                                        TextEntry::make('customer')->label('Pelanggan'),
                                        TextEntry::make('remaining_balance')->label('Sisa Tagihan')->money('IDR')->color('danger')->weight('bold'),
                                    ])
                                    ->columns(4) // Ditampilkan sejajar ke samping seperti baris
                            ]),

                        // TAB 3: HUTANG (MENGGUNAKAN REPEATABLE ENTRY)
                        Tab::make('Hutang Supplier')
                            ->icon('heroicon-o-arrow-left-start-on-rectangle')
                            ->schema([
                                TextEntry::make('total_hutang_usaha')
                                    ->label('TOTAL HUTANG KESELURUHAN')
                                    ->money('IDR')
                                    ->weight('black')
                                    ->color('danger')
                                    ->size(TextSize::Large),

                                RepeatableEntry::make('hutang_list')
                                    ->label('Daftar Rincian Hutang')
                                    ->schema([
                                        TextEntry::make('date')->label('Tgl PO'),
                                        TextEntry::make('invoice_number')->label('No. Invoice')->weight('bold'),
                                        TextEntry::make('supplier')->label('Supplier'),
                                        TextEntry::make('remaining_balance')->label('Sisa Hutang')->money('IDR')->color('danger')->weight('bold'),
                                    ])
                                    ->columns(4)
                            ]),

                        // TAB 4: NERACA
                        Tab::make('Neraca (Balance Sheet)')
                            ->icon('heroicon-o-scale')
                            ->schema([
                                Grid::make(2)->schema([
                                    Section::make('AKTIVA (Harta / Aset)')
                                        ->icon('heroicon-o-banknotes')
                                        ->schema([
                                            TextEntry::make('kas')->label('Kas & Saldo Bank')->money('IDR'),
                                            TextEntry::make('piutang_neraca')->label('Piutang Usaha (Pelanggan)')->money('IDR'),
                                            TextEntry::make('stok')->label('Persediaan Barang (Stok Gudang)')->money('IDR'),
                                            TextEntry::make('deposit_sup')->label('Deposit / Uang Muka di Supplier')->money('IDR'),
                                            TextEntry::make('total_aktiva')->label('TOTAL AKTIVA')->money('IDR')->weight('black')->color('primary')->size(TextSize::Large),
                                        ])->columnSpan(1),
                                        
                                    Section::make('PASIVA (Kewajiban & Ekuitas)')
                                        ->icon('heroicon-o-scale')
                                        ->schema([
                                            TextEntry::make('hutang_usaha_neraca')->label('Hutang Usaha (Ke Supplier)')->money('IDR')->color('danger'),
                                            TextEntry::make('deposit_pel')->label('Titipan Deposit Konsumen')->money('IDR')->color('danger'),
                                            TextEntry::make('hutang_komisi')->label('Hutang Komisi & Referral')->money('IDR')->color('danger'),
                                            TextEntry::make('ekuitas')->label('Kekayaan Bersih Usaha (Net Worth)')->money('IDR')->color('success'),
                                            TextEntry::make('total_pasiva')->label('TOTAL PASIVA')->money('IDR')->weight('black')->color('primary')->size(TextSize::Large),
                                        ])->columnSpan(1),
                                ])
                            ]),
                        Tab::make('Analisa Usaha')
                            ->icon('heroicon-o-presentation-chart-line')
                            ->schema([
                                Grid::make(3)->schema([
                                    Section::make('Margin Laba Bersih')
                                        ->description('Rasio laba terhadap total omzet.')
                                        ->schema([
                                            TextEntry::make('profit_margin')
                                                ->label('Persentase')
                                                ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                                                ->size(TextSize::Large)
                                                ->weight('black'),
                                            TextEntry::make('status_margin')
                                                ->label('Status Kesehatan')
                                                ->badge()
                                                ->color(fn ($state) => match ($state) {
                                                    'Sangat Sehat' => 'success',
                                                    'Kurang Ideal' => 'warning',
                                                    default => 'danger',
                                                }),
                                        ])->columnSpan(1),

                                    Section::make('Rasio Likuiditas (Current Ratio)')
                                        ->description('Kemampuan aset melunasi kewajiban.')
                                        ->schema([
                                            TextEntry::make('current_ratio')
                                                ->label('Skor Rasio')
                                                ->formatStateUsing(fn ($state) => number_format($state, 2) . ' x')
                                                ->size(TextSize::Large)
                                                ->weight('black'),
                                            TextEntry::make('status_likuiditas')
                                                ->label('Status Kesehatan')
                                                ->badge()
                                                ->color(fn ($state) => match ($state) {
                                                    'Sangat Aman' => 'success',
                                                    'Aman' => 'info',
                                                    default => 'danger',
                                                }),
                                        ])->columnSpan(1),

                                    Section::make('Rasio Hutang (Debt to Asset)')
                                        ->description('Persentase hutang dibanding harta.')
                                        ->schema([
                                            TextEntry::make('debt_ratio')
                                                ->label('Persentase')
                                                ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                                                ->size(TextSize::Large)
                                                ->weight('black'),
                                            TextEntry::make('status_hutang')
                                                ->label('Status Kesehatan')
                                                ->badge()
                                                ->color(fn ($state) => match ($state) {
                                                    'Rendah Risiko' => 'success',
                                                    'Risiko Sedang' => 'warning',
                                                    default => 'danger',
                                                }),
                                        ])->columnSpan(1),
                                ])
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    protected function getReportData(): array
    {
        $mode = $this->data['report_mode'] ?? 'live';

        if ($mode === 'live') {
            return $this->getLiveData();
        }

        // JIKA MODE ARSIP, AMBIL DARI DATABASE JSON!
        $arsip = MonthlyClosing::find($mode);
        return $arsip ? $arsip->snapshot_data : $this->getLiveData();
    }

    protected function getLiveData(): array
    {
        $businessId = Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id;
        $startDate = $this->data['start_date'] ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $this->data['end_date'] ?? now()->format('Y-m-d');

        // --- A. LABA RUGI ---
        $omzetBarang = Order::where('business_id', $businessId)->where('status', 'completed')->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])->sum(DB::raw('total_amount - shipping_fee_billed'));
        $omzetOngkir = Order::where('business_id', $businessId)->where('status', 'completed')->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])->sum('shipping_fee_billed');
        $hpp = OrderItem::whereHas('order', function($q) use ($businessId, $startDate, $endDate) {
            $q->where('business_id', $businessId)->where('status', 'completed')->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        })->sum(DB::raw('base_price * qty_billed'));

        $rincianBeban = FinanceCategory::where('type', 'out')->whereNotIn('code', ['EXP_PURCHASE', 'LIA_AP', 'ASSET_DEP_SUPPLIER'])
            ->withSum(['ledgers' => function($q) use ($businessId, $startDate, $endDate) {
                $q->where('business_id', $businessId)->whereBetween('transaction_date', [$startDate, $endDate]);
            }], 'amount')->get()->filter(fn($cat) => $cat->ledgers_sum_amount > 0);

        $bebanArray = [];
        $totalBeban = 0;
        foreach ($rincianBeban as $beban) {
            $bebanArray[$beban->name] = (float) $beban->ledgers_sum_amount;
            $totalBeban += $beban->ledgers_sum_amount;
        }

        $labaBersih = (($omzetBarang + $omzetOngkir) - $hpp) - $totalBeban;

        // --- B. ARUS KAS (CASHFLOW) ---
        $queryKasMasuk = Ledger::with('financeCategory')->where('business_id', $businessId)->where('type', 'in')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->select('finance_category_id', DB::raw('SUM(amount) as total'))->groupBy('finance_category_id')->get();
            
        $kasMasukArray = [];
        $totalKasMasuk = 0;
        foreach ($queryKasMasuk as $kas) {
            $name = $kas->financeCategory ? $kas->financeCategory->name : 'Uncategorized Income';
            $kasMasukArray[$name] = (float) $kas->total;
            $totalKasMasuk += $kas->total;
        }

        $queryKasKeluar = Ledger::with('financeCategory')->where('business_id', $businessId)->where('type', 'out')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->select('finance_category_id', DB::raw('SUM(amount) as total'))->groupBy('finance_category_id')->get();
            
        $kasKeluarArray = [];
        $totalKasKeluar = 0;
        foreach ($queryKasKeluar as $kas) {
            $name = $kas->financeCategory ? $kas->financeCategory->name : 'Uncategorized Expense';
            $kasKeluarArray[$name] = (float) $kas->total;
            $totalKasKeluar += $kas->total;
        }

        // --- C. PIUTANG & HUTANG ---
        $piutangQuery = Order::with('customer')->where('business_id', $businessId)->whereIn('payment_status', ['unpaid', 'partial'])->get();
        $piutangList = [];
        $totalPiutang = 0;
        foreach ($piutangQuery as $order) {
            if ($order->remaining_balance > 0) { 
                $piutangList[] = ['date' => \Carbon\Carbon::parse($order->created_at)->format('d M Y'), 'order_number' => $order->order_number, 'customer' => $order->customer->name ?? 'Umum', 'remaining_balance' => (float)$order->remaining_balance];
                $totalPiutang += $order->remaining_balance;
            }
        }

        $hutangQuery = Purchase::with('supplier')->where('business_id', $businessId)->whereIn('status', ['unpaid', 'partial'])->get();
        $hutangList = [];
        $totalHutang = 0;
        foreach ($hutangQuery as $purchase) {
            if ($purchase->remaining_balance > 0) {
                $hutangList[] = ['date' => \Carbon\Carbon::parse($purchase->purchase_date)->format('d M Y'), 'invoice_number' => $purchase->invoice_number, 'supplier' => $purchase->supplier->name ?? 'Umum', 'remaining_balance' => (float)$purchase->remaining_balance];
                $totalHutang += $purchase->remaining_balance;
            }
        }

        // --- D. NERACA ---
        $kas = Wallet::where('business_id', $businessId)->sum('balance');
        $stok = Product::where('business_id', $businessId)->sum(DB::raw('stock * base_price'));
        $depositSup = Supplier::where('business_id', $businessId)->sum('deposit_balance');
        $aktiva = $kas + $totalPiutang + $stok + $depositSup;

        $depositPel = Customer::where('business_id', $businessId)->sum('deposit_balance');
        $hutangKomisi = Customer::where('business_id', $businessId)->sum('commission_balance');
        $kewajiban = $totalHutang + $depositPel + $hutangKomisi;

        // --- E. ANALISA USAHA (RASIO) ---
        $totalOmzet = $omzetBarang + $omzetOngkir;
        $profitMargin = $totalOmzet > 0 ? ($labaBersih / $totalOmzet) * 100 : 0;
        $currentRatio = $kewajiban > 0 ? ($aktiva / $kewajiban) : ($aktiva > 0 ? 999 : 0);
        $debtRatio = $aktiva > 0 ? ($kewajiban / $aktiva) * 100 : 0;

        return [
            // Laba Rugi
            'omzet_barang' => (float)$omzetBarang, 'omzet_ongkir' => (float)$omzetOngkir, 'hpp' => (float)$hpp,
            'laba_kotor' => (float)(($omzetBarang + $omzetOngkir) - $hpp), 'rincian_beban' => $bebanArray, 'total_beban' => (float)$totalBeban, 'laba_bersih' => (float)$labaBersih,
            
            // Arus Kas
            'kas_masuk' => $kasMasukArray, 'total_kas_masuk' => (float)$totalKasMasuk,
            'kas_keluar' => $kasKeluarArray, 'total_kas_keluar' => (float)$totalKasKeluar,
            'net_cashflow' => (float)($totalKasMasuk - $totalKasKeluar),

            // Hutang Piutang
            'piutang_list' => $piutangList, 'total_piutang' => (float)$totalPiutang,
            'hutang_list' => $hutangList, 'total_hutang_usaha' => (float)$totalHutang,

            // Neraca
            'kas' => (float)$kas, 'piutang_neraca' => (float)$totalPiutang, 'stok' => (float)$stok, 'deposit_sup' => (float)$depositSup, 'total_aktiva' => (float)$aktiva,
            'hutang_usaha_neraca' => (float)$totalHutang, 'deposit_pel' => (float)$depositPel, 'hutang_komisi' => (float)$hutangKomisi, 'total_pasiva' => (float)($kewajiban + ($aktiva - $kewajiban)), 'ekuitas' => (float)($aktiva - $kewajiban),

            // Analisa Usaha
            'profit_margin' => $profitMargin,
            'status_margin' => $profitMargin >= 10 ? 'Sangat Sehat' : ($profitMargin > 0 ? 'Kurang Ideal' : 'Rugi / Bahaya'),
            'current_ratio' => $currentRatio,
            'status_likuiditas' => $currentRatio >= 1.5 ? 'Sangat Aman' : ($currentRatio >= 1 ? 'Aman' : 'Bahaya Gagal Bayar'),
            'debt_ratio' => $debtRatio,
            'status_hutang' => $debtRatio <= 40 ? 'Rendah Risiko' : ($debtRatio <= 60 ? 'Risiko Sedang' : 'Risiko Sangat Tinggi'),
        ];
    }

    protected function getBebanSchema(): array
    {
        $data = $this->getReportData();
        $schema = [];
        
        if (!empty($data['rincian_beban'])) {
            foreach ($data['rincian_beban'] as $key => $value) {
                // Men-generate skema Infolist dinamis berdasarkan akun yang terpakai
                $schema[] = TextEntry::make('rincian_beban.' . $key)->label($key)->money('IDR')->color('danger');
            }
        } else {
             $schema[] = TextEntry::make('no_beban')->label('')->default('Tidak ada beban operasional pada periode ini.');
        }

        return $schema;
    }

    protected function getSchemaKasMasuk(): array
    {
        $data = $this->getReportData();
        $schema = [];
        if (!empty($data['kas_masuk'])) {
            foreach ($data['kas_masuk'] as $key => $val) {
                $schema[] = TextEntry::make('kas_masuk.' . $key)->label($key)->money('IDR')->color('success');
            }
        } else {
             $schema[] = TextEntry::make('no_data_in')->label('')->default('Tidak ada uang masuk.');
        }
        return $schema;
    }

    protected function getSchemaKasKeluar(): array
    {
        $data = $this->getReportData();
        $schema = [];
        if (!empty($data['kas_keluar'])) {
            foreach ($data['kas_keluar'] as $key => $val) {
                $schema[] = TextEntry::make('kas_keluar.' . $key)->label($key)->money('IDR')->color('danger');
            }
        } else {
             $schema[] = TextEntry::make('no_data_out')->label('')->default('Tidak ada uang keluar.');
        }
        return $schema;
    }
}