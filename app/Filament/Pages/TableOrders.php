<?php

namespace App\Filament\Pages;

use App\Models\CustomerOrder;
use App\Models\DiningTable;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class TableOrders extends Page
{
    protected string $view = 'filament.pages.table-orders';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static string|UnitEnum|null $navigationGroup = 'Sales Control';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Table Orders';

    public ?int $rejectingOrderId = null;

    public string $rejectReason = '';

    public static function canAccess(): bool
    {
        return ! auth()->user()->isAccountant();
    }

    public static function getNavigationBadge(): ?string
    {
        $count = CustomerOrder::where('status', CustomerOrder::STATUS_PENDING)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    protected function getViewData(): array
    {
        return [
            'tables' => DiningTable::where('is_active', true)
                ->withCount(['customerOrders as pending_count' => fn ($q) => $q->where('status', CustomerOrder::STATUS_PENDING)])
                ->with(['customerOrders' => fn ($q) => $q->where('status', CustomerOrder::STATUS_PENDING)->with('items')->oldest()])
                ->orderBy('name')
                ->get(),
        ];
    }

    public function accept(int $customerOrderId): void
    {
        $order = CustomerOrder::where('status', CustomerOrder::STATUS_PENDING)->findOrFail($customerOrderId);

        $order->update([
            'status' => CustomerOrder::STATUS_ACCEPTED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $this->dispatch('open-ticket', url: route('table.order.ticket', $order));

        Notification::make()->title('Order accepted — sent to kitchen · An aika ga dafa abinci')->success()->send();
    }

    public function startReject(int $customerOrderId): void
    {
        $this->rejectingOrderId = $customerOrderId;
        $this->rejectReason = '';
    }

    public function cancelReject(): void
    {
        $this->rejectingOrderId = null;
        $this->rejectReason = '';
    }

    public function confirmReject(): void
    {
        $this->validate(['rejectReason' => 'required|string'], [
            'rejectReason.required' => 'Tell the customer why — e.g. item out of stock.',
        ]);

        $order = CustomerOrder::where('status', CustomerOrder::STATUS_PENDING)->findOrFail($this->rejectingOrderId);

        $order->update([
            'status' => CustomerOrder::STATUS_REJECTED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'reject_reason' => $this->rejectReason,
        ]);

        $this->cancelReject();

        Notification::make()->title('Order rejected · An ki oda')->warning()->send();
    }
}
