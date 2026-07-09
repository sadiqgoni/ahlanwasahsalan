<?php

namespace App\Filament\Pages;

use App\Support\DailyReport as Report;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class DailyReport extends Page
{
    protected string $view = 'filament.pages.daily-report';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Sales Control';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Daily Report';

    public string $date = '';

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    /** Owner and accountant only — this is the reconciliation screen. */
    public static function canAccess(): bool
    {
        return ! auth()->user()->isCashier();
    }

    protected function getViewData(): array
    {
        return [
            'report' => Report::build($this->date),
        ];
    }
}
