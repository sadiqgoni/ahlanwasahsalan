<?php

use App\Livewire\CustomerMenu;
use App\Models\CustomerOrder;
use App\Models\DiningTable;
use App\Models\Sale;
use App\Models\Shift;
use App\Support\DailyReport;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/admin'));

// Public — a customer scans the table's QR code and lands here with no login.
Route::get('/order/{token}', CustomerMenu::class)->name('table.menu');

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

    Route::get('/reports/{from}/{to}', function (string $from, string $to) {
        abort_if(auth()->user()->isCashier(), 403);

        return view('daily-report-print', ['report' => DailyReport::buildRange($from, $to)]);
    })->name('reports.print');

    Route::get('/table-order/{customerOrder}/ticket', function (CustomerOrder $customerOrder) {
        abort_if(auth()->user()->isAccountant(), 403);

        return view('table-order-ticket', ['order' => $customerOrder->load(['items', 'diningTable'])]);
    })->name('table.order.ticket');

    Route::get('/table-qr/{diningTable}', function (DiningTable $diningTable) {
        abort_unless(auth()->user()->isOwner(), 403);

        return view('table-qr-print', ['table' => $diningTable]);
    })->name('table.qr.print');
});
