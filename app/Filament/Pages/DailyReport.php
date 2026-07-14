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

    protected static ?string $title = 'Reports';

    protected static ?string $navigationLabel = 'All Reports';

    public string $date = '';

    public string $from = '';

    public string $to = '';

    public function mount(): void
    {
        $this->date = now()->toDateString();
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
    }

    /** One-tap date ranges for the toolbar: today, yesterday, week, month. */
    public function setRange(string $preset): void
    {
        [$this->from, $this->to] = match ($preset) {
            'today' => [now()->toDateString(), now()->toDateString()],
            'yesterday' => [now()->subDay()->toDateString(), now()->subDay()->toDateString()],
            'week' => [now()->startOfWeek()->toDateString(), now()->toDateString()],
            'month' => [now()->startOfMonth()->toDateString(), now()->toDateString()],
            default => [$this->from, $this->to],
        };
    }

    /** Owner and accountant only — this is the reconciliation screen. */
    public static function canAccess(): bool
    {
        return ! auth()->user()->isCashier();
    }

    protected function getViewData(): array
    {
        return [
            'report' => Report::buildRange($this->from, $this->to),
        ];
    }
}
