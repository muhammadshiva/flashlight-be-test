<?php

namespace App\Providers\Filament;

use App\Filament\Cashier\Pages\Profile;
use App\Models\User;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class CashierPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('cashier')
            ->path('cashier')
            ->login()
            ->registration(false)
            ->passwordReset(false)
            ->emailVerification(false)
            ->profile(Profile::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Cashier/Resources'), for: 'App\\Filament\\Cashier\\Resources')
            ->discoverPages(in: app_path('Filament/Cashier/Pages'), for: 'App\\Filament\\Cashier\\Pages')
            ->pages([
                \App\Filament\Cashier\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Cashier/Widgets'), for: 'App\\Filament\\Cashier\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web')
            ->renderHook(
                'panels::auth.login.form.after',
                fn() => view('filament.custom.login-form-after')
            )
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                return $builder
                    ->items([
                        NavigationItem::make('Dashboard')
                            ->icon('heroicon-o-home')
                            ->isActiveWhen(fn(): bool => request()->routeIs('filament.cashier.pages.dashboard'))
                            ->url(fn(): string => route('filament.cashier.pages.dashboard')),

                        NavigationItem::make('Payment Processing')
                            ->icon('heroicon-o-credit-card')
                            ->isActiveWhen(fn(): bool => request()->routeIs('filament.cashier.resources.payments.*'))
                            ->url(fn(): string => route('filament.cashier.resources.payments.index'))
                            ->sort(1),

                        NavigationItem::make('QRIS Payments')
                            ->icon('heroicon-o-qr-code')
                            ->isActiveWhen(fn(): bool => request()->routeIs('filament.cashier.resources.q-r-i-s-payments.*'))
                            ->url(fn(): string => route('filament.cashier.resources.q-r-i-s-payments.index'))
                            ->sort(2),

                        NavigationItem::make('Wash Transactions')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->isActiveWhen(fn(): bool => request()->routeIs('filament.cashier.resources.wash-transactions.*'))
                            ->url(fn(): string => route('filament.cashier.resources.wash-transactions.index'))
                            ->sort(3),

                        NavigationItem::make('Wash Status')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->isActiveWhen(fn(): bool => request()->routeIs('filament.cashier.resources.wash-statuses.*'))
                            ->url(fn(): string => route('filament.cashier.resources.wash-statuses.index'))
                            ->sort(4),
                    ]);
            })
            ->databaseNotifications()
            ->unsavedChangesAlerts();
    }

    public function register(): void
    {
        parent::register();

        // Register directory for cashier resources/pages
        Blade::anonymousComponentPath(resource_path('views/filament/cashier'));
    }
}
