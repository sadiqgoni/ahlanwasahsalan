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

        return static::buildRange($day->toDateString(), $day->toDateString());
    }

    /** Build a consolidated report for any inclusive business-date range. */
    public static function buildRange(string $from, string $to): array
    {
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        $sales = Sale::with(['items', 'user', 'voidedBy'])
            ->whereBetween('created_at', [$start, $end])
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
            ->whereBetween('opened_at', [$start, $end])
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
            'day' => $start,
            'from' => $start,
            'to' => $end,
            'isSingleDay' => $start->isSameDay($end),
            'periodLabel' => $start->isSameDay($end)
                ? $start->format('l, d F Y')
                : $start->format('d M Y').' — '.$end->format('d M Y'),
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
