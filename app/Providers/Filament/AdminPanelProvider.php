<?php

namespace App\Providers\Filament;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use App\Models\Business;
use App\Filament\Pages\Tenancy\RegisterBusiness;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->favicon(asset('images/brand/icon-colour.png'))
            ->brandLogo(asset('images/brand/logo.png'))
            ->brandLogoHeight('2rem')
            ->login()
            ->tenant(Business::class)
            ->tenantRegistration(RegisterBusiness::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->globalSearch(false)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                // AccountWidget::class,
                // FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationGroup('Pengaturan'),
            ])
            ->navigationItems([
                NavigationItem::make('Mesin Kasir (POS)')
                    ->url(fn (): string => route('pos.penjualan'))
                    ->icon('heroicon-o-computer-desktop')
                    ->group('Transaksi')
                    ->sort(1)
                    ->openUrlInNewTab(),
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Transaksi')
                    ->collapsible(true),
                NavigationGroup::make()
                    ->label('Produk & Inventori')
                    ->collapsible(true),    
                NavigationGroup::make()
                    ->label('Logistik')
                    ->collapsible(true),
                NavigationGroup::make()
                    ->label('Keuangan')
                    ->collapsible(true),
                NavigationGroup::make()
                    ->label('Pengaturan')
                    ->collapsible(true),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->sidebarCollapsibleOnDesktop(true);
    }
}
