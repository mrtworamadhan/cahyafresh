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
use App\Models\Delivery;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
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
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LaporanKeuangan extends Page implements HasForms, HasInfolists, HasTable, HasActions
{
    use InteractsWithForms, InteractsWithInfolists, InteractsWithTable, InteractsWithActions, InteractsWithHeaderActions, HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Keuangan';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Laporan Keuangan';
    protected static ?string $title = 'Laporan Keuangan Executive';

    protected string $view = 'filament.pages.laporan-keuangan';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'report_mode' => 'live', 
            'start_date' => now()->startOfMonth()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]);
    }

    public function updateData(): void
    {
        $this->data = $this->getLiveData();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tutup_buku')
                ->label('Tutup Buku (Akhir Bulan)')
                ->icon('heroicon-o-lock-closed')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    $businessId = Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id;
                    $liveData = $this->getLiveData(); 

                    MonthlyClosing::create([
                        'business_id' => $businessId,
                        'period_name' => now()->translatedFormat('F Y'), 
                        'closing_date' => now(),
                        'snapshot_data' => $liveData, 
                    ]);

                    Notification::make()->title('Tutup Buku Berhasil!')->success()->send();
                    $this->form->fill(['report_mode' => 'live']); 
                }),
        ];
    }

    public function form(Schema $form): Schema
    {
        $businessId = Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id;
        $arsipOptions = MonthlyClosing::where('business_id', $businessId)->orderBy('created_at', 'desc')->pluck('period_name', 'id')->toArray();

        return $form
            ->schema([
                Select::make('report_mode')
                    ->label('Pilih Mode Laporan')
                    ->options(['live' => 'Periode Berjalan (Live)'] + $arsipOptions)
                    ->live()
                    ->required(),

                Grid::make(2)
                    ->schema([
                        DatePicker::make('start_date')->label('Dari Tanggal')->live()->required(),
                        DatePicker::make('end_date')->label('Sampai Tanggal')->live()->required(),
                    ])
                    ->visible(fn (Get $get) => $get('report_mode') === 'live'), 
            ])
            ->statePath('data');
    }

    protected function getLiveData(): array
    {
        $businessId = Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id;
        
        $startDate = ($this->data['start_date'] ?? now()->startOfMonth()->format('Y-m-d')) . ' 00:00:00';
        $endDate = ($this->data['end_date'] ?? now()->format('Y-m-d')) . ' 23:59:59';

        // ==============================================================
        // --- A. LABA RUGI PERIODIK ---
        // ==============================================================
        $omzetBarang = (float) Order::where('business_id', $businessId)->where('status', 'completed')->whereBetween('updated_at', [$startDate, $endDate])->sum(DB::raw('total_amount - shipping_fee_billed'));
        $omzetOngkir = (float) Order::where('business_id', $businessId)->where('status', 'completed')->whereBetween('updated_at', [$startDate, $endDate])->sum('shipping_fee_billed');
        
        $pendapatanLedgerPeriodik = (float) Ledger::query()
            ->join('finance_categories', 'ledgers.finance_category_id', '=', 'finance_categories.id')
            ->where('ledgers.business_id', $businessId)
            ->where('ledgers.type', 'in')
            ->whereIn('finance_categories.code', ['INC_GAIN', 'INC_OTHER'])
            ->whereBetween('ledgers.transaction_date', [$startDate, $endDate])
            ->sum('ledgers.amount');

        $shippingCategory = FinanceCategory::withoutGlobalScopes()->where('code', 'OP_SHIPPING')->first();
        $bebanOngkir = (float) Ledger::where('business_id', $businessId)
            ->where('finance_category_id', $shippingCategory?->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');

        $hpp = (float) OrderItem::whereHas('order', function($q) use ($businessId, $startDate, $endDate) {
            $q->where('business_id', $businessId)->where('status', 'completed')->whereBetween('updated_at', [$startDate, $endDate]);
        })->sum(DB::raw('base_price * (qty_billed + qty_bonus)'));

        $queryBeban = Ledger::query()
            ->join('finance_categories', 'ledgers.finance_category_id', '=', 'finance_categories.id')
            ->where('ledgers.business_id', $businessId)
            ->where('ledgers.type', 'out')
            ->whereNotIn('finance_categories.code', [
                'EXP_PURCHASE', 'LIA_AP', 'ASSET_DEP_SUPPLIER', 'OP_SHIPPING',
                'LIA_COMMISSION_PAID', 'LIA_SHIPPING_PAID', 'LIA_CSR_ZAKAT_PAID', 'EQ_MODAL', 'EQ_PRIVE'
            ])
            ->whereBetween('ledgers.transaction_date', [$startDate, $endDate])
            ->select('finance_categories.name as category_name', DB::raw('SUM(ledgers.amount) as total'))
            ->groupBy('finance_categories.id', 'finance_categories.name')
            ->get();

        $bebanList = []; $totalBeban = 0;
        if ($bebanOngkir > 0) {
            $bebanList[] = ['name' => 'Beban Pengiriman & Ekspedisi (Riil)', 'amount' => (float) $bebanOngkir];
            $totalBeban += $bebanOngkir;
        }
        foreach ($queryBeban as $beban) {
            $bebanList[] = ['name' => $beban->category_name ?? 'Beban Lainnya', 'amount' => (float) $beban->total];
            $totalBeban += $beban->total;
        }

        $labaKotor = ($omzetBarang + $omzetOngkir + $pendapatanLedgerPeriodik) - $hpp;
        $labaBersihPeriodik = $labaKotor - $totalBeban;

        // ==============================================================
        // --- B. ARUS KAS PERIODIK ---
        // ==============================================================
        $queryKasMasuk = Ledger::query()
            ->join('finance_categories', 'ledgers.finance_category_id', '=', 'finance_categories.id')
            ->where('ledgers.business_id', $businessId)->where('ledgers.type', 'in')->whereNotNull('ledgers.wallet_id') 
            ->whereBetween('ledgers.transaction_date', [$startDate, $endDate])
            ->select('finance_categories.name as category_name', DB::raw('SUM(ledgers.amount) as total'))
            ->groupBy('finance_categories.id', 'finance_categories.name')->get();
            
        $kasMasukList = []; $totalKasMasuk = 0;
        foreach ($queryKasMasuk as $kasMas) {
            $kasMasukList[] = ['name' => $kasMas->category_name ?? 'Pemasukan Lainnya', 'amount' => (float) $kasMas->total];
            $totalKasMasuk += $kasMas->total;
        }

        $queryKasKeluar = Ledger::query()
            ->join('finance_categories', 'ledgers.finance_category_id', '=', 'finance_categories.id')
            ->where('ledgers.business_id', $businessId)->where('ledgers.type', 'out')->whereNotNull('ledgers.wallet_id') 
            ->whereBetween('ledgers.transaction_date', [$startDate, $endDate])
            ->select('finance_categories.name as category_name', DB::raw('SUM(ledgers.amount) as total'))
            ->groupBy('finance_categories.id', 'finance_categories.name')->get();
            
        $kasKeluarList = []; $totalKasKeluar = 0;
        foreach ($queryKasKeluar as $kasKel) {
            $kasKeluarList[] = ['name' => $kasKel->category_name ?? 'Pengeluaran Lainnya', 'amount' => (float) $kasKel->total];
            $totalKasKeluar += $kasKel->total;
        }
        $netCashflow = $totalKasMasuk - $totalKasKeluar;

        // ==============================================================
        // --- C. PIUTANG & HUTANG LIST ---
        // ==============================================================
        $piutangQuery = Order::with('customer')->where('business_id', $businessId)->where('status', 'completed')->get();
        $piutangList = [];
        $piutangNeraca = 0; 
        
        foreach ($piutangQuery as $order) {
            if ($order->remaining_balance > 0) { 
                $piutangList[] = [
                    'date' => Carbon::parse($order->delivery_date)->format('d M Y'), 
                    'order_number' => $order->order_number, 'customer' => $order->customer->name ?? 'Umum', 
                    'remaining_balance' => (float) $order->remaining_balance
                ];
                $piutangNeraca += (float) $order->remaining_balance;
            }
        }

        $hutangQuery = Purchase::with('supplier')->where('business_id', $businessId)->get();
        $hutangList = [];
        $hutangUsahaNeraca = 0;
        
        foreach ($hutangQuery as $purchase) {
            if ($purchase->remaining_balance > 0) {
                $hutangList[] = [
                    'date' => Carbon::parse($purchase->purchase_date)->format('d M Y'), 
                    'invoice_number' => $purchase->invoice_number, 'supplier' => $purchase->supplier->name ?? 'Umum', 
                    'remaining_balance' => (float) $purchase->remaining_balance
                ];
                $hutangUsahaNeraca += (float) $purchase->remaining_balance;
            }
        }

        // ==============================================================
        // --- D. NERACA SAKRAL (VALUASI INVENTORY ADAPTIF) ---
        // ==============================================================
        $kas = (float) Wallet::where('business_id', $businessId)->sum('balance');
        $depositSup = (float) Supplier::where('business_id', $businessId)->sum('deposit_balance');
        
        $stokFisikRaw = (float) Product::where('business_id', $businessId)->sum(DB::raw('stock * base_price'));
        $stokFisik = max(0, $stokFisikRaw);

        $totalInvoicePurchase = (float) Purchase::where('business_id', $businessId)->sum('total_amount');
        $hppMurni = (float) OrderItem::whereHas('order', function($q) use ($businessId) { 
            $q->where('business_id', $businessId)->where('status', 'completed'); 
        })->sum(DB::raw('base_price * (qty_billed + qty_bonus)'));

        $catLossId = FinanceCategory::withoutGlobalScopes()->where('code', 'EXP_LOSS')->first()?->id;
        $catGainId = FinanceCategory::withoutGlobalScopes()->where('code', 'INC_GAIN')->first()?->id;
        $ledgerGain = (float) Ledger::where('business_id', $businessId)->where('finance_category_id', $catGainId)->where('type', 'in')->sum('amount');
        $ledgerLoss = (float) Ledger::where('business_id', $businessId)->where('finance_category_id', $catLossId)->where('type', 'out')->sum('amount');
        $netOpname = $ledgerGain - $ledgerLoss;

        $stokAkuntansi = max(0, $totalInvoicePurchase - $hppMurni + $netOpname);
        $penyesuaianStok = $stokAkuntansi - $stokFisik;
        
        $aktiva = $kas + $piutangNeraca + $stokFisik + $depositSup;

        $depositPel = (float) Customer::where('business_id', $businessId)->sum('deposit_balance');
        $hutangKomisi = (float) Customer::where('business_id', $businessId)->sum('commission_balance');
        $hutangOngkir = (float) Delivery::where('business_id', $businessId)->where('is_paid_to_courier', false)->whereHas('order', function($q) { $q->where('status', 'completed'); })->sum('shipping_cost_actual');

        $kewajiban = $hutangUsahaNeraca + $depositPel + $hutangKomisi + $hutangOngkir;
        
        $modalCategory = FinanceCategory::withoutGlobalScopes()->where('code', 'EQ_MODAL')->first();
        $modalAwal = (float) Ledger::where('business_id', $businessId)->where('finance_category_id', $modalCategory?->id)->sum('amount');

        $priveCategory = FinanceCategory::withoutGlobalScopes()->where('code', 'EQ_PRIVE')->first();
        $prive = (float) Ledger::where('business_id', $businessId)->where('finance_category_id', $priveCategory?->id)->sum('amount');

        $pendapatanLedgerMurni = (float) Ledger::query()
            ->join('finance_categories', 'ledgers.finance_category_id', '=', 'finance_categories.id')
            ->where('ledgers.business_id', $businessId)->where('ledgers.type', 'in')
            ->whereIn('finance_categories.code', ['INC_GAIN', 'INC_OTHER'])
            ->sum('ledgers.amount');

        $omzetBarangMurni = (float) Order::where('business_id', $businessId)->where('status', 'completed')->sum(DB::raw('total_amount - shipping_fee_billed'));
        $omzetOngkirMurni = (float) Order::where('business_id', $businessId)->where('status', 'completed')->sum('shipping_fee_billed');
        $bebanOngkirMurni = (float) Ledger::where('business_id', $businessId)->where('finance_category_id', $shippingCategory?->id)->sum('amount');
        
        $totalBebanMurni = (float) Ledger::query()
            ->join('finance_categories', 'ledgers.finance_category_id', '=', 'finance_categories.id')
            ->where('ledgers.business_id', $businessId)->where('ledgers.type', 'out')
            ->whereNotIn('finance_categories.code', ['EXP_PURCHASE', 'LIA_AP', 'ASSET_DEP_SUPPLIER', 'OP_SHIPPING', 'LIA_COMMISSION_PAID', 'LIA_SHIPPING_PAID', 'LIA_CSR_ZAKAT_PAID', 'EQ_MODAL', 'EQ_PRIVE'])
            ->sum('ledgers.amount') + $bebanOngkirMurni;

        $labaBersihSeumurHidup = ($omzetBarangMurni + $omzetOngkirMurni + $pendapatanLedgerMurni - $hppMurni) - $totalBebanMurni - $penyesuaianStok;
        
        $ekuitasBuku = $modalAwal + $labaBersihSeumurHidup - $prive;
        $penyesuaianNeraca = $aktiva - ($kewajiban + $ekuitasBuku);


        // ==============================================================
        // --- E. KONFIGURASI ANALISA USAHA & COMMON-SIZE ---
        // ==============================================================
        $totalOmzet = $omzetBarang + $omzetOngkir + $pendapatanLedgerPeriodik;
        
        // --- KONFIGURASI BATASAN (Bisa Diubah Sesuai Kebijakan) ---
        $batasHppMax = 80;         // Maksimal HPP 80% (Artinya margin kotor min 20%)
        $batasBebanWajar = 15;     // Wajar hingga 15% dari omzet
        $batasBebanMax = 20;       // Bahaya jika lebih dari 20%
        $batasPenyusutanWajar = 2; // Batas wajar kebocoran gudang/stok 2%
        $batasPenyusutanMax = 3;   // Bahaya jika di atas 3%
        $batasLabaMin = 5;         // Target minimal Laba Bersih 5%
        
        // --- KALKULASI PERSENTASE ---
        $pctHpp = $totalOmzet > 0 ? ($hpp / $totalOmzet) * 100 : 0;
        $pctBeban = $totalOmzet > 0 ? ($totalBeban / $totalOmzet) * 100 : 0;
        $pctPenyusutan = $totalOmzet > 0 ? (abs($penyesuaianStok) / $totalOmzet) * 100 : 0;
        $labaBersihPeriodikFinal = $labaBersihPeriodik - $penyesuaianStok;
        $pctLaba = $totalOmzet > 0 ? ($labaBersihPeriodikFinal / $totalOmzet) * 100 : 0;

        // --- PENILAIAN STATUS (KUALITATIF) ---
        $evalHpp = $pctHpp <= $batasHppMax ? 'Efisien' : 'Bahaya (Terlalu Tinggi)';
        $evalBeban = $pctBeban <= $batasBebanWajar ? 'Efisien' : ($pctBeban <= $batasBebanMax ? 'Wajar' : 'Boros / Overbudget');
        $evalPenyusutan = $pctPenyusutan <= $batasPenyusutanWajar ? 'Aman / Terkendali' : ($pctPenyusutan <= $batasPenyusutanMax ? 'Wajar / Toleransi' : 'Bahaya (Kebocoran Tinggi)');
        $evalLaba = $pctLaba >= $batasLabaMin ? 'Sehat' : ($pctLaba > 0 ? 'Kurang Ideal' : 'Rugi / Bahaya');

        $currentRatio = $kewajiban > 0 ? ($aktiva / $kewajiban) : ($aktiva > 0 ? 999 : 0);
        $debtRatio = $aktiva > 0 ? ($kewajiban / $aktiva) * 100 : 0;

        return [
            // Laba Rugi Data
            'omzet_barang' => (float)$omzetBarang, 'omzet_ongkir' => (float)$omzetOngkir, 'hpp' => (float)$hpp,
            'laba_kotor' => (float)$labaKotor, 'rincian_beban' => $bebanList, 'total_beban' => (float)$totalBeban, 'laba_chem' => (float)$labaBersihPeriodik, 
            'laba_bersih' => (float)$labaBersihPeriodikFinal,
            'pendapatan_lain' => (float)$pendapatanLedgerPeriodik,
            'penyesuaian_stok' => (float)$penyesuaianStok,
            
            // Common-Size Evaluasi (Dikirim ke View)
            'hpp_desc' => number_format($pctHpp, 1, ',', '.') . '% dari Omzet (' . $evalHpp . ')',
            'beban_desc' => number_format($pctBeban, 1, ',', '.') . '% dari Omzet (' . $evalBeban . ')',
            'penyusutan_desc' => number_format($pctPenyusutan, 1, ',', '.') . '% dari Omzet (' . $evalPenyusutan . ')',
            'laba_desc' => number_format($pctLaba, 1, ',', '.') . '% Net Profit Margin (' . $evalLaba . ')',
            'eval_hpp' => $evalHpp, 'eval_beban' => $evalBeban, 'eval_penyusutan' => $evalPenyusutan, 'eval_laba' => $evalLaba,
            'pct_hpp' => number_format($pctHpp, 1, ',', '.') . '%', 'pct_beban' => number_format($pctBeban, 1, ',', '.') . '%', 'pct_penyusutan' => number_format($pctPenyusutan, 1, ',', '.') . '%', 'pct_laba' => number_format($pctLaba, 1, ',', '.') . '%',
            
            // Arus Kas Data
            'kas_masuk' => $kasMasukList, 'total_kas_masuk' => (float)$totalKasMasuk,
            'kas_keluar' => $kasKeluarList, 'total_kas_keluar' => (float)$totalKasKeluar,
            'net_cashflow' => (float)$netCashflow,

            // Piutang Hutang Data
            'piutang_list' => $piutangList, 'total_piutang' => (float)$piutangNeraca,
            'hutang_list' => $hutangList, 'total_hutang_usaha' => (float)$hutangUsahaNeraca,

            // Neraca Data
            'kas' => (float)$kas, 'piutang_neraca' => (float)$piutangNeraca, 'stok' => (float)$stokFisik, 'deposit_sup' => (float)$depositSup, 'total_aktiva' => (float)$aktiva,
            'hutang_usaha_neraca' => (float)$hutangUsahaNeraca, 'deposit_pel' => (float)$depositPel, 'hutang_komisi' => (float)$hutangKomisi, 'hutang_ongkir' => (float)$hutangOngkir,
            'modal_awal' => (float)$modalAwal, 'laba_berjalan' => (float)$labaBersihSeumurHidup, 'prive' => (float)$prive, 'penyesuaian_neraca' => (float)$penyesuaianNeraca,
            'total_pasiva' => (float)($kewajiban + $ekuitasBuku), 'ekuitas' => (float)($aktiva - $kewajiban),

            // Analisa Finansial Lanjutan
            'current_ratio' => $currentRatio,
            'status_likuiditas' => $currentRatio >= 1.5 ? 'Sangat Aman' : ($currentRatio >= 1 ? 'Aman' : 'Bahaya Gagal Bayar'),
            'debt_ratio' => $debtRatio,
            'status_hutang' => $debtRatio <= 40 ? 'Rendah Risiko' : ($debtRatio <= 60 ? 'Risiko Sedang' : 'Risiko Sangat Tinggi'),
        ];
    }

    

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->state($this->data)
            ->schema([
                Tabs::make('Laporan Keuangan')
                    ->tabs([
                        Tab::make('Laba / Rugi')
                            ->icon('heroicon-o-document-currency-dollar')
                            ->schema([
                                Section::make('Pendapatan & Harga Pokok')
                                    ->schema([
                                        TextEntry::make('omzet_barang')->label('Pendapatan Penjualan')->money('IDR')->color('success'),
                                        TextEntry::make('pendapatan_lain')->label('Pendapatan Lain & Opname Stok')->money('IDR')->color('success'),
                                        TextEntry::make('hpp')
                                            ->label('Harga Pokok Penjualan (HPP)')
                                            ->money('IDR')
                                            ->color('danger')
                                            ->helperText(fn () => $this->data['hpp_desc'] ?? ''), // Menambahkan persentase Common-Size
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
                                        TextEntry::make('total_beban')
                                            ->label('Total Beban Operasional')
                                            ->money('IDR')
                                            ->color('danger')
                                            ->helperText(fn () => $this->data['beban_desc'] ?? ''),
                                            
                                        TextEntry::make('penyesuaian_stok')
                                            ->label('Penyesuaian Valuasi Persediaan')
                                            ->money('IDR')
                                            ->color('warning')
                                            ->helperText(fn () => $this->data['penyusutan_desc'] ?? ''),
                                            
                                        TextEntry::make('laba_bersih')
                                            ->label('LABA BERSIH (NET PROFIT)')
                                            ->money('IDR')
                                            ->weight('black')
                                            ->size(TextSize::Large)
                                            ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                                            ->helperText(fn () => $this->data['laba_desc'] ?? ''),
                                    ])->columns(2),
                            ]),

                        Tab::make('Arus Kas (Cashflow)')
                            ->icon('heroicon-o-arrows-right-left')
                            ->schema([
                                Grid::make(2)->schema([
                                    Section::make('Uang Masuk (Cash In)')
                                        ->icon('heroicon-o-arrow-down-circle')
                                        ->iconColor('success')
                                        ->schema([
                                            RepeatableEntry::make('kas_masuk')->label('')->schema([
                                                TextEntry::make('name')->label('Kategori'),
                                                TextEntry::make('amount')->label('Nominal')->money('IDR')->color('success')->weight('bold'),
                                            ])->columns(2),
                                            TextEntry::make('total_kas_masuk')->label('Total Kas Masuk')->money('IDR')->weight('black')->color('success')->size(TextSize::Large),
                                        ])->columnSpan(1),
                                        
                                    Section::make('Uang Keluar (Cash Out)')
                                        ->icon('heroicon-o-arrow-up-circle')
                                        ->iconColor('danger')
                                        ->schema([
                                            RepeatableEntry::make('kas_keluar')->label('')->schema([
                                                TextEntry::make('name')->label('Kategori'),
                                                TextEntry::make('amount')->label('Nominal')->money('IDR')->color('danger')->weight('bold'),
                                            ])->columns(2),
                                            TextEntry::make('total_kas_keluar')->label('Total Kas Keluar')->money('IDR')->weight('black')->color('danger')->size(TextSize::Large),
                                        ])->columnSpan(1),
                                        
                                    TextEntry::make('net_cashflow')->label('Net Cashflow (Selisih)')->money('IDR')->weight('black')->size(TextSize::Large)->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                                ])
                            ]),

                        Tab::make('Piutang & Hutang')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema([
                                Grid::make(2)->schema([
                                    Section::make('Piutang Konsumen (Uang Kita di Luar)')
                                        ->icon('heroicon-o-arrow-down-on-square')
                                        ->schema([
                                            RepeatableEntry::make('piutang_list')->label('Rincian Belum Lunas')->schema([
                                                TextEntry::make('date')->label('Tanggal'),
                                                TextEntry::make('order_number')->label('No Nota'),
                                                TextEntry::make('customer')->label('Konsumen'),
                                                TextEntry::make('remaining_balance')->label('Sisa Tagihan')->money('IDR')->color('warning')->weight('bold'),
                                            ])->columns(4),
                                            TextEntry::make('total_piutang')->label('Total Piutang Berjalan')->money('IDR')->weight('black')->color('primary')->size(TextSize::Large),
                                        ])->columnSpan(1),
                                        
                                    Section::make('Hutang Supplier (Kewajiban Kita)')
                                        ->icon('heroicon-o-arrow-up-on-square')
                                        ->schema([
                                            RepeatableEntry::make('hutang_list')->label('Rincian Belum Dibayar')->schema([
                                                TextEntry::make('date')->label('Tanggal'),
                                                TextEntry::make('invoice_number')->label('No Invoice'),
                                                TextEntry::make('supplier')->label('Supplier'),
                                                TextEntry::make('remaining_balance')->label('Sisa Hutang')->money('IDR')->color('danger')->weight('bold'),
                                            ])->columns(4),
                                            TextEntry::make('total_hutang_usaha')->label('Total Hutang Berjalan')->money('IDR')->weight('black')->color('danger')->size(TextSize::Large),
                                        ])->columnSpan(1),
                                ])
                            ]),

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
                                        
                                    Section::make('PASIVA (Kewajiban & Struktur Ekuitas)')
                                        ->icon('heroicon-o-scale')
                                        ->schema([
                                            TextEntry::make('hutang_usaha_neraca')->label('Hutang Usaha (Ke Supplier)')->money('IDR')->color('danger'),
                                            TextEntry::make('deposit_pel')->label('Titipan Deposit Konsumen')->money('IDR')->color('danger'),
                                            TextEntry::make('hutang_komisi')->label('Hutang Komisi & Referral')->money('IDR')->color('danger'),
                                            TextEntry::make('hutang_ongkir')->label('Hutang Ongkir & Kurir (Belum Rilis)')->money('IDR')->color('danger'),
                                            
                                            TextEntry::make('modal_awal')->label('Modal Awal / Suntikan Disetor')->money('IDR')->color('info'),
                                            TextEntry::make('laba_berjalan')->label('Laba Berjalan (Periode Ini)')->money('IDR')->color('success'),
                                            TextEntry::make('penyesuaian_stok')->label('Penyesuaian Valuasi Persediaan')->money('IDR')->color('warning'),
                                            TextEntry::make('prive')->label('Prive (Tarik Modal Pribadi)')->money('IDR')->color('danger'),
                                            
                                            TextEntry::make('total_pasiva')->label('TOTAL PASIVA')->money('IDR')->weight('black')->color('primary')->size(TextSize::Large),
                                        ])->columnSpan(1),

                                    TextEntry::make('penyesuaian_neraca')->label('Selisih Penyesuaian Akuntansi')->money('IDR')->color('warning')->helperText('Selisih transaksi non-kas/backdate.'),
                                ])
                            ]),

                        Tab::make('Analisa Usaha (Executive Summary)')
                            ->icon('heroicon-o-presentation-chart-line')
                            ->schema([
                                Section::make('Kesehatan Profitabilitas (Common-Size Analysis)')
                                    ->helperText('Penilaian otomatis kualitas pengeluaran dibandingkan dengan batas ideal industri.')
                                    ->schema([
                                        TextEntry::make('pct_hpp')
                                            ->label('Rasio Harga Pokok (HPP)')
                                            ->badge()
                                            ->color(fn() => str_contains($this->data['eval_hpp'] ?? '', 'Efisien') ? 'success' : 'danger')
                                            ->helperText(fn() => 'Status: ' . ($this->data['eval_hpp'] ?? '')),
                                            
                                        TextEntry::make('pct_beban')
                                            ->label('Rasio Beban Operasional')
                                            ->badge()
                                            ->color(fn() => str_contains($this->data['eval_beban'] ?? '', 'Efisien') ? 'success' : (str_contains($this->data['eval_beban'] ?? '', 'Wajar') ? 'warning' : 'danger'))
                                            ->helperText(fn() => 'Status: ' . ($this->data['eval_beban'] ?? '')),
                                            
                                        TextEntry::make('pct_penyusutan')
                                            ->label('Tingkat Kebocoran / Susut Gudang')
                                            ->badge()
                                            ->color(fn() => str_contains($this->data['eval_penyusutan'] ?? '', 'Aman') ? 'success' : (str_contains($this->data['eval_penyusutan'] ?? '', 'Wajar') ? 'warning' : 'danger'))
                                            ->helperText(fn() => 'Status: ' . ($this->data['eval_penyusutan'] ?? '')),
                                            
                                        TextEntry::make('pct_laba')
                                            ->label('Net Profit Margin (Laba Bersih Akhir)')
                                            ->badge()
                                            ->color(fn() => str_contains($this->data['eval_laba'] ?? '', 'Sehat') ? 'success' : (str_contains($this->data['eval_laba'] ?? '', 'Ideal') ? 'warning' : 'danger'))
                                            ->helperText(fn() => 'Status: ' . ($this->data['eval_laba'] ?? '')),
                                    ])->columns(4),

                                Section::make('Kesehatan Finansial (Likuiditas & Solvabilitas)')
                                    ->helperText('Kemampuan bisnis dalam melunasi hutang dan kewajiban.')
                                    ->schema([
                                        TextEntry::make('current_ratio')->label('Rasio Lancar (Current Ratio)')->numeric(2)->suffix(' x')->color('primary'),
                                        TextEntry::make('status_likuiditas')->label('Status Likuiditas')->badge()->color(fn ($state) => $state === 'Sangat Aman' || $state === 'Aman' ? 'success' : 'danger'),
                                        TextEntry::make('debt_ratio')->label('Rasio Hutang (Debt to Asset)')->numeric(2)->suffix('%')->color('danger'),
                                        TextEntry::make('status_hutang')->label('Status Hutang')->badge()->color(fn ($state) => $state === 'Rendah Risiko' ? 'success' : ($state === 'Risiko Sedang' ? 'warning' : 'danger')),
                                    ])->columns(2),
                            ]),
                    ])->columnSpanFull()
            ]);
    }
}