<?php

use App\Models\Sale;
use App\Models\Shift;
use App\Support\DailyReport;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/admin'));

Route::middleware('auth')->group(function () {
    Route::get('/receipt/{sale}', function (Sale $sale) {
        return view('receipt', ['sale' => $sale->load(['items', 'user'])]);
    })->name('receipt.print');

    Route::get('/shift-report/{shift}', function (Shift $shift) {
        return view('shift-report', ['shift' => $shift->load(['user', 'sales.items'])]);
    })->name('shift.report');

    Route::get('/daily-report/{date}', function (string $date) {
        abort_if(auth()->user()->isCashier(), 403);

        return view('daily-report-print', ['report' => DailyReport::build($date)]);
    })->name('daily.report.print');
});
