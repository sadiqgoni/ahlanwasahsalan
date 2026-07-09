<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\ChartWidget;

class SalesByHourChart extends ChartWidget
{
    protected ?string $heading = 'Sales Today (by hour)';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return ! auth()->user()->isCashier();
    }

    protected function getData(): array
    {
        $sales = Sale::whereDate('created_at', today())
            ->where('status', 'completed')
            ->get()
            ->groupBy(fn (Sale $sale) => $sale->created_at->format('G'));

        $labels = [];
        $data = [];

        foreach (range(6, 23) as $hour) {
            $labels[] = sprintf('%02d:00', $hour);
            $data[] = (float) ($sales->get((string) $hour)?->sum('total') ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Sales (₦)',
                    'data' => $data,
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
