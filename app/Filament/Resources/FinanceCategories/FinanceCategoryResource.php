<?php

namespace App\Filament\Resources\FinanceCategories;

use App\Filament\Resources\FinanceCategories\Pages\ManageFinanceCategories;
use App\Models\FinanceCategory;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FinanceCategoryResource extends Resource
{
    protected static ?string $model = FinanceCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAtSymbol;

    protected static string | UnitEnum | null $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Kategori Keuangan';
    protected static ?int $navigationSort = 3;
    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        
        return parent::getEloquentQuery()
            ->withoutGlobalScopes()
            ->where(function($q) {
                $q->whereNull('business_id')
                  ->orWhere('business_id', Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id);
            });
    }

    public static function form(Schema $schema): Schema
    {
        $businessId = Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id;

        return $schema
            ->schema([
                Section::make('Detail Kategori')
                    ->schema([
                        Select::make('parent_id')
                            ->label('Induk Kategori (Opsional)')
                            ->helperText('Pilih jika ini adalah sub-kategori dari kategori utama.')
                            ->options(
                                FinanceCategory::where(function ($query) use ($businessId) {
                                    $query->where('business_id', $businessId)
                                          ->orWhereNull('business_id'); // Ambil juga kategori global bawaan sistem
                                })->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload(),

                        TextInput::make('name')
                            ->label('Nama Kategori / Akun')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Biaya Gaji Supir'),

                        Select::make('type')
                            ->label('Tipe Arus Kas')
                            ->required()
                            ->options([
                                'in' => 'Pemasukan (Income)',
                                'out' => 'Pengeluaran (Expense)',
                                'equity' => 'Pendanaan / Modal (Equity)',
                            ]),

                        Textarea::make('description')
                            ->label('Keterangan')
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Kategori')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('parent.name')
                    ->label('Induk Kategori')
                    ->sortable()
                    ->color('gray'),

                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                        'equity' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in' => 'Pemasukan',
                        'out' => 'Pengeluaran',
                        'equity' => 'Modal/Prive',
                        default => $state,
                    }),

                IconColumn::make('is_system')
                    ->label('Bawaan Sistem')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->trueColor('danger')
                    ->falseIcon('heroicon-o-user')
                    ->falseColor('success')
                    ->tooltip(fn ($state) => $state ? 'Tidak bisa dihapus/diedit' : 'Dibuat oleh user'),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (FinanceCategory $record): bool => !$record->is_system),
                
                DeleteAction::make()
                    ->visible(fn (FinanceCategory $record): bool => !$record->is_system),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each(function ($record) {
                                if (!$record->is_system) {
                                    $record->delete();
                                }
                            });
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFinanceCategories::route('/'),
        ];
    }
}
