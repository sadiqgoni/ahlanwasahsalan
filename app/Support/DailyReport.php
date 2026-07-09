<?php

namespace App\Support;

use App\Models\Sale;
use App\Models\Shift;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class DailyReport
{
    /** Build everything the owner needs to interrogate one business day. */
    public static function build(string $date): array
    {
        $day = Carbon::parse($date);

        $sales = Sale::with(['items', 'user', 'voidedBy'])
            ->whereDate('created_at', $day)
            ->get();

        $completed = $sales->where('status', 'completed');
        $voided = $sales->where('status', 'voided');

        $byMethod = [
            'cash' => (float) $completed->where('payment_method', 'cash')->sum('total'),
            'transfer' => (float) $completed->where('payment_method', 'transfer')->sum('total'),
            'pos' => (float) $completed->where('payment_method', 'pos')->sum('total'),
        ];

        $bySection = $completed
            ->flatMap->items
            ->groupBy('section')
            ->map(fn ($items) => (float) $items->sum('line_total'))
            ->sortDesc();

        $byCashier = $completed
            ->groupBy('user_id')
            ->map(fn ($group) => [
                'name' => $group->first()->user->name,
                'receipts' => $group->count(),
                'total' => (float) $group->sum('total'),
                'cash' => (float) $group->where('payment_method', 'cash')->sum('total'),
            ])
            ->values();

        $shifts = Shift::with('user')
            ->whereDate('opened_at', $day)
            ->orderBy('opened_at')
            ->get();

        $topItems = $completed
            ->flatMap->items
            ->groupBy('product_name')
            ->map(fn ($items) => [
                'qty' => (int) $items->sum('quantity'),
                'total' => (float) $items->sum('line_total'),
            ])
            ->sortByDesc('total')
            ->take(10);

        return [
            'day' => $day,
            'total' => array_sum($byMethod),
            'receipts' => $completed->count(),
            'byMethod' => $byMethod,
            'bySection' => $bySection,
            'byCashier' => $byCashier,
            'shifts' => $shifts,
            'voided' => $voided->values(),
            'topItems' => $topItems,
        ];
    }

    public static function isToday(CarbonInterface $day): bool
    {
        return $day->isToday();
    }
}
