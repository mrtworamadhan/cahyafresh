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
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use UnitEnum;
use Filament\Pages\Page;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
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
                    $liveData = $this->getLiveData(); 

                    MonthlyClosing::create([
                        'business_id' => $businessId,
                        'period_name' => now()->translatedFormat('F Y'), 
                        'closing_date' => now(),
                        'snapshot_data' => $liveData, 
                    ]);

                    Notification::make()
                        ->title('Tutup Buku Berhasil!')
                        ->body('Laporan bulan ini telah dikunci ke dalam arsip.')
                        ->success()
                        ->send();
                        
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
                    ->visible(fn (Get $get) => $get('report_mode') === 'live'), 
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
                        // ==========================================
                        // TAB 1: LABA RUGI
                        // ==========================================
                        Tab::make('Laba / Rugi')
                            ->icon('heroicon-o-document-currency-dollar')
                            ->schema([
                                Section::make('Pendapatan & Harga Pokok')
                                    ->schema([
                                        TextEntry::make('omzet_barang')->label('Pendapatan Penjualan')->money('IDR')->color('success'),
                                        TextEntry::make('omzet_ongkir')->label('Pendapatan Ongkir')->money('IDR')->color('success'),
                                        TextEntry::make('hpp')->label('Harga Pokok Penjualan (HPP)')->money('IDR')->color('danger')->helperText('Termasuk modal dari barang bonus/gratis.'),
                                        TextEntry::make('laba_kotor')->label('Laba Kotor (Gross Profit)')->money('IDR')->weight('black')->size(TextSize::Large),
                                    ])->columns(2),

                                Section::make('Rincian Beban Operasional')
                                    ->schema([
                                        RepeatableEntry::make('rincian_beban')
                                            ->label('')
                                            ->schema([
                                                TextEntry::make('name')->label('Kategori Beban')->weight('bold'),
                                                TextEntry::make('amount')->label('Nominal Beban')->money('IDR')->color('danger')->weight('bold'),
                                            ])->columns(2)
                                    ]),

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

                        // ==========================================
                        // TAB 2: ARUS KAS
                        // ==========================================
                        Tab::make('Arus Kas (Cashflow)')
                            ->icon('heroicon-o-arrow-path-rounded-square')
                            ->schema([
                                Section::make('Kas Masuk (Cash In)')
                                    ->icon('heroicon-o-arrow-down-left')
                                    ->schema([
                                        RepeatableEntry::make('kas_masuk')
                                            ->label('')
                                            ->schema([
                                                TextEntry::make('name')->label('Sumber Pemasukan')->weight('bold'),
                                                TextEntry::make('amount')->label('Nominal')->money('IDR')->color('success')->weight('bold'),
                                            ])->columns(2)
                                    ]),
                                    
                                Section::make('Kas Keluar (Cash Out)')
                                    ->icon('heroicon-o-arrow-up-right')
                                    ->schema([
                                        RepeatableEntry::make('kas_keluar')
                                            ->label('')
                                            ->schema([
                                                TextEntry::make('name')->label('Tujuan Pengeluaran')->weight('bold'),
                                                TextEntry::make('amount')->label('Nominal')->money('IDR')->color('danger')->weight('bold'),
                                            ])->columns(2)
                                    ]),
                                    
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

                        // ==========================================
                        // TAB 3: PIUTANG
                        // ==========================================
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
                                    ])->columns(4) 
                            ]),

                        // ==========================================
                        // TAB 4: HUTANG
                        // ==========================================
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
                                    ])->columns(4)
                            ]),

                        // ==========================================
                        // TAB 5: NERACA
                        // ==========================================
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
                            
                        // ==========================================
                        // TAB 6: ANALISA
                        // ==========================================
                        Tab::make('Analisa Usaha')
                            ->icon('heroicon-o-presentation-chart-line')
                            ->schema([
                                Grid::make(3)->schema([
                                    Section::make('Margin Laba Bersih')
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

                                    Section::make('Rasio Likuiditas')
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

                                    Section::make('Rasio Hutang')
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

        $arsip = MonthlyClosing::find($mode);
        return $arsip ? $arsip->snapshot_data : $this->getLiveData();
    }

    protected function getLiveData(): array
    {
        $businessId = Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id;
        
        $startDate = ($this->data['start_date'] ?? now()->startOfMonth()->format('Y-m-d')) . ' 00:00:00';
        $endDate = ($this->data['end_date'] ?? now()->format('Y-m-d')) . ' 23:59:59';

        // ==============================================================
        // --- A. LABA RUGI ---
        // ==============================================================
        $omzetBarang = Order::where('business_id', $businessId)->where('status', 'completed')->whereBetween('created_at', [$startDate, $endDate])->sum(DB::raw('total_amount - shipping_fee_billed'));
        $omzetOngkir = Order::where('business_id', $businessId)->where('status', 'completed')->whereBetween('created_at', [$startDate, $endDate])->sum('shipping_fee_billed');
        $bebanOngkir = Order::where('business_id', $businessId)->where('status', 'completed')->whereBetween('created_at', [$startDate, $endDate])->sum('shipping_cost_actual');

        $hpp = OrderItem::whereHas('order', function($q) use ($businessId, $startDate, $endDate) {
            $q->where('business_id', $businessId)->where('status', 'completed')->whereBetween('created_at', [$startDate, $endDate]);
        })->sum(DB::raw('base_price * (qty_billed + qty_bonus)'));

        $queryBeban = Ledger::query()
            ->join('finance_categories', 'ledgers.finance_category_id', '=', 'finance_categories.id')
            ->where('ledgers.business_id', $businessId)
            ->where('ledgers.type', 'out')
            ->whereNotIn('finance_categories.code', ['EXP_PURCHASE', 'LIA_AP', 'ASSET_DEP_SUPPLIER', 'OP_SHIPPING'])
            ->whereBetween('ledgers.transaction_date', [$startDate, $endDate])
            ->select(
                'finance_categories.name as category_name', 
                DB::raw('SUM(ledgers.amount) as total')
            )
            ->groupBy('finance_categories.id', 'finance_categories.name')
            ->get();

        $bebanList = [];
        $totalBeban = 0;

        if ($bebanOngkir > 0) {
            $bebanList[] = [
                'name' => 'Beban Pengiriman & Ekspedisi (Riil)',
                'amount' => (float) $bebanOngkir
            ];
            $totalBeban += $bebanOngkir;
        }

        foreach ($queryBeban as $beban) {
            $bebanList[] = [
                'name' => $beban->category_name ?? 'Beban Lainnya', 
                'amount' => (float) $beban->total
            ];
            $totalBeban += $beban->total;
        }

        $labaKotor = ($omzetBarang + $omzetOngkir) - $hpp;
        $labaBersih = $labaKotor - $totalBeban;

        // ==============================================================
        // --- B. PERBAIKAN TOTAL: ARUS KAS (CASHFLOW MURNI COA JOIN) ---
        // KUNCI SAKTI: Mengganti Eloquent ORM dengan SQL INNER JOIN 
        // Agar tembus Global Scope Multi-Tenant untuk Kategori System (Null)
        // ==============================================================
        $queryKasMasuk = Ledger::query()
            ->join('finance_categories', 'ledgers.finance_category_id', '=', 'finance_categories.id')
            ->where('ledgers.business_id', $businessId)
            ->where('ledgers.type', 'in')
            ->whereNotNull('ledgers.wallet_id') // Filter murni mutasi keuangan tunai/bank
            ->whereBetween('ledgers.transaction_date', [$startDate, $endDate])
            ->select('finance_categories.name as category_name', DB::raw('SUM(ledgers.amount) as total'))
            ->groupBy('finance_categories.id', 'finance_categories.name')
            ->get();
            
        $kasMasukList = [];
        $totalKasMasuk = 0;
        foreach ($queryKasMasuk as $kasMas) {
            $kasMasukList[] = [
                'name' => $kasMas->category_name ?? 'Pemasukan Lainnya', 
                'amount' => (float) $kasMas->total
            ];
            $totalKasMasuk += $kasMas->total;
        }

        $queryKasKeluar = Ledger::query()
            ->join('finance_categories', 'ledgers.finance_category_id', '=', 'finance_categories.id')
            ->where('ledgers.business_id', $businessId)
            ->where('ledgers.type', 'out')
            ->whereNotNull('ledgers.wallet_id') // Filter murni mutasi keuangan tunai/bank
            ->whereBetween('ledgers.transaction_date', [$startDate, $endDate])
            ->select('finance_categories.name as category_name', DB::raw('SUM(ledgers.amount) as total'))
            ->groupBy('finance_categories.id', 'finance_categories.name')
            ->get();
            
        $kasKeluarList = [];
        $totalKasKeluar = 0;
        foreach ($queryKasKeluar as $kasKel) {
            $kasKeluarList[] = [
                'name' => $kasKel->category_name ?? 'Pengeluaran Lainnya', 
                'amount' => (float) $kasKel->total
            ];
            $totalKasKeluar += $kasKel->total;
        }

        $netCashflow = $totalKasMasuk - $totalKasKeluar;

        // ==============================================================
        // --- C. PIUTANG & HUTANG ---
        // ==============================================================
        $piutangQuery = Order::with('customer')
            ->where('business_id', $businessId)
            ->where('status', 'completed')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->get();

        $piutangList = [];
        $totalPiutang = 0;

        foreach ($piutangQuery as $order) {
            if ($order->remaining_balance > 0) { 
                $piutangList[] = [
                    'date' => \Carbon\Carbon::parse($order->delivery_date)->format('d M Y'), 
                    'order_number' => $order->order_number, 
                    'customer' => $order->customer->name ?? 'Umum', 
                    'remaining_balance' => (float) $order->remaining_balance
                ];
                $totalPiutang += $order->remaining_balance;
            }
        }

        $hutangQuery = Purchase::with('supplier')->where('business_id', $businessId)->whereIn('status', ['unpaid', 'partial'])->get();
        $hutangList = [];
        $totalHutang = 0;
        foreach ($hutangQuery as $purchase) {
            if ($purchase->remaining_balance > 0) {
                $hutangList[] = [
                    'date' => \Carbon\Carbon::parse($purchase->purchase_date)->format('d M Y'), 
                    'invoice_number' => $purchase->invoice_number, 
                    'supplier' => $purchase->supplier->name ?? 'Umum', 
                    'remaining_balance' => (float) $purchase->remaining_balance
                ];
                $totalHutang += $purchase->remaining_balance;
            }
        }

        // ==============================================================
        // --- D. NERACA ---
        // ==============================================================
        $kas = Wallet::where('business_id', $businessId)->sum('balance');
        $stok = Product::where('business_id', $businessId)->sum(DB::raw('stock * base_price'));
        $depositSup = Supplier::where('business_id', $businessId)->sum('deposit_balance');
        $aktiva = $kas + $totalPiutang + $stok + $depositSup;

        $depositPel = Customer::where('business_id', $businessId)->sum('deposit_balance');
        $hutangKomisi = Customer::where('business_id', $businessId)->sum('commission_balance');
        $kewajiban = $totalHutang + $depositPel + $hutangKomisi;
        $ekuitas = $aktiva - $kewajiban;

        // ==============================================================
        // --- E. ANALISA USAHA (RASIO) ---
        // ==============================================================
        $totalOmzet = $omzetBarang + $omzetOngkir;
        $profitMargin = $totalOmzet > 0 ? ($labaBersih / $totalOmzet) * 100 : 0;
        $currentRatio = $kewajiban > 0 ? ($aktiva / $kewajiban) : ($aktiva > 0 ? 999 : 0);
        $debtRatio = $aktiva > 0 ? ($kewajiban / $aktiva) * 100 : 0;

        return [
            'omzet_barang' => (float)$omzetBarang, 
            'omzet_ongkir' => (float)$omzetOngkir, 
            'hpp' => (float)$hpp,
            'laba_kotor' => (float)$labaKotor, 
            'rincian_beban' => $bebanList, 
            'total_beban' => (float)$totalBeban, 
            'laba_bersih' => (float)$labaBersih,
            
            'kas_masuk' => $kasMasukList, 
            'total_kas_masuk' => (float)$totalKasMasuk,
            'kas_keluar' => $kasKeluarList, 
            'total_kas_keluar' => (float)$totalKasKeluar,
            'net_cashflow' => (float)$netCashflow,

            'piutang_list' => $piutangList, 
            'total_piutang' => (float)$totalPiutang,
            'hutang_list' => $hutangList, 
            'total_hutang_usaha' => (float)$totalHutang,

            'kas' => (float)$kas, 
            'piutang_neraca' => (float)$totalPiutang, 
            'stok' => (float)$stok, 
            'deposit_sup' => (float)$depositSup, 
            'total_aktiva' => (float)$aktiva,
            
            'hutang_usaha_neraca' => (float)$totalHutang, 
            'deposit_pel' => (float)$depositPel, 
            'hutang_comm' => (float)$hutangKomisi,
            'hutang_komisi' => (float)$hutangKomisi, 
            'total_pasiva' => (float)$aktiva, 
            'ekuitas' => (float)$ekuitas,

            'profit_margin' => $profitMargin,
            'status_margin' => $profitMargin >= 10 ? 'Sangat Sehat' : ($profitMargin > 0 ? 'Kurang Ideal' : 'Rugi / Bahaya'),
            'current_ratio' => $currentRatio,
            'status_likuiditas' => $currentRatio >= 1.5 ? 'Sangat Aman' : ($currentRatio >= 1 ? 'Aman' : 'Bahaya Gagal Bayar'),
            'debt_ratio' => $debtRatio,
            'status_hutang' => $debtRatio <= 40 ? 'Rendah Risiko' : ($debtRatio <= 60 ? 'Risiko Sedang' : 'Risiko Sangat Tinggi'),
        ];
    }
}