<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TodayStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return ! auth()->user()->isCashier();
    }

    protected function getStats(): array
    {
        $today = Sale::whereDate('created_at', today())->where('status', 'completed');

        $total = (clone $today)->sum('total');
        $cash = (clone $today)->where('payment_method', 'cash')->sum('total');
        $transfer = (clone $today)->where('payment_method', 'transfer')->sum('total');
        $pos = (clone $today)->where('payment_method', 'pos')->sum('total');
        $receipts = (clone $today)->count();
        $voids = Sale::whereDate('created_at', today())->where('status', 'voided')->count();

        return [
            Stat::make('Total Sales Today', '₦'.number_format((float) $total))
                ->description($receipts.' receipts issued')
                ->color('primary'),
            Stat::make('Cash', '₦'.number_format((float) $cash))
                ->description('Must be in the drawer')
                ->color('success'),
            Stat::make('Transfer + POS Card', '₦'.number_format((float) $transfer + (float) $pos))
                ->description('Match against bank statement')
                ->color('info'),
            Stat::make('Voided Receipts Today', (string) $voids)
                ->description($voids > 0 ? 'Check the reasons!' : 'All clean')
                ->color($voids > 0 ? 'danger' : 'success'),
        ];
    }
}
