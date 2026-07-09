<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->spa()
            ->colors([
                'primary' => Color::Blue,
                'gray' => Color::Slate,
            ])
            ->sidebarWidth('270px')
            ->sidebarCollapsibleOnDesktop()
            ->brandLogo(fn (): HtmlString => $this->getBrandHeader())
            ->darkModeBrandLogo(fn (): HtmlString => $this->getBrandHeader())
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString(
                    '<link rel="stylesheet" href="'.asset('css/filament/pos-theme.css').'">'
                ),
            )
            ->navigationGroups([
                NavigationGroup::make()->label('Sales Control'),
                NavigationGroup::make()->label('Menu Setup'),
                NavigationGroup::make()->label('Settings'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
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
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    protected function getBrandHeader(): HtmlString
    {
        $name = e(config('app.name', 'Ahlan wa Sahlan'));

        return new HtmlString(
            <<<HTML
            <div class="pos-brand">
                <div class="pos-brand__mark">🍛</div>
                <div class="pos-brand__text">
                    <span class="pos-brand__eyebrow">Restaurant Control</span>
                    <span class="pos-brand__name">{$name}</span>
                </div>
            </div>
            HTML
        );
    }
}
