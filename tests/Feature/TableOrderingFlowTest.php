<?php

namespace Tests\Feature;

use App\Filament\Pages\PointOfSale;
use App\Filament\Pages\TableOrders;
use App\Livewire\CustomerMenu;
use App\Models\CustomerOrder;
use App\Models\DiningTable;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\MenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TableOrderingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_orders_via_qr_then_staff_accepts_then_cashier_charges_it(): void
    {
        $this->seed(MenuSeeder::class);
        $table = DiningTable::create(['name' => 'Table 7']);

        // A guest with no account scans the QR and lands on the public menu.
        $this->get(route('table.menu', $table->qr_token))->assertOk()->assertSee('Table 7');

        $riceBeans = Product::where('name', 'Rice & Beans with Oil & Pepper')->firstOrFail();
        $salad = $riceBeans->options()->where('name', 'Salad')->firstOrFail();

        $guest = Livewire::test(CustomerMenu::class, ['token' => $table->qr_token])
            ->call('selectProduct', $riceBeans->id)
            ->call('toggleOption', $salad->id)
            ->call('confirmOptions')
            ->set('customerName', 'Musa')
            ->call('submitOrder');

        $order = CustomerOrder::first();
        $this->assertNotNull($order);
        $this->assertEquals(CustomerOrder::STATUS_PENDING, $order->status);
        $this->assertEquals(1200.0, $order->total());
        $this->assertEquals('Musa', $order->customer_name);

        // Cashier reviews the queue and accepts it — this sends it to the kitchen.
        $cashier = User::where('role', 'cashier')->first();
        $this->actingAs($cashier);

        Livewire::test(TableOrders::class)
            ->call('accept', $order->id)
            ->assertDispatched('open-ticket', url: route('table.order.ticket', $order));

        $order->refresh();
        $this->assertEquals(CustomerOrder::STATUS_ACCEPTED, $order->status);
        $this->assertEquals($cashier->id, $order->reviewed_by);

        $this->assertEquals(1200.0, $table->fresh()->openTabTotal());

        // The kitchen ticket route works for staff.
        $this->get(route('table.order.ticket', $order))
            ->assertOk()
            ->assertSee('RICE SECTION')
            ->assertSee('Table 7');

        // Cashier opens a shift and charges the table from the accepted tab.
        Livewire::test(PointOfSale::class)->set('openingFloat', '0')->call('openShift');

        Livewire::test(PointOfSale::class)
            ->call('loadTableTab', $table->id)
            ->call('startPayment')
            ->set('amountPaid', '1200')
            ->call('completeSale');

        $sale = Sale::first();
        $this->assertNotNull($sale);
        $this->assertEquals(1200.0, (float) $sale->total);
        $this->assertEquals($table->id, $sale->dining_table_id);

        // The pre-order item is now marked charged and drops off the open tab.
        $this->assertEquals(0.0, $table->fresh()->openTabTotal());
        $this->assertNotNull($order->items->first()->fresh()->sale_item_id);
    }

    public function test_staff_can_reject_an_order_with_a_reason(): void
    {
        $this->seed(MenuSeeder::class);
        $table = DiningTable::create(['name' => 'Table 2']);
        $zobo = Product::where('name', 'Zobo')->firstOrFail();

        Livewire::test(CustomerMenu::class, ['token' => $table->qr_token])
            ->call('selectProduct', $zobo->id)
            ->call('submitOrder');

        $order = CustomerOrder::first();

        $this->actingAs(User::where('role', 'owner')->first());

        Livewire::test(TableOrders::class)
            ->call('confirmReject')
            ->assertHasErrors(['rejectReason']);

        Livewire::test(TableOrders::class)
            ->call('startReject', $order->id)
            ->set('rejectReason', 'Out of stock')
            ->call('confirmReject');

        $order->refresh();
        $this->assertEquals(CustomerOrder::STATUS_REJECTED, $order->status);
        $this->assertEquals('Out of stock', $order->reject_reason);
        $this->assertEquals(0.0, $table->fresh()->openTabTotal());
    }

    public function test_qr_print_page_is_owner_only(): void
    {
        $table = DiningTable::create(['name' => 'Table 9']);

        $this->seed(MenuSeeder::class);

        $this->actingAs(User::where('role', 'cashier')->first());
        $this->get(route('table.qr.print', $table))->assertForbidden();

        $this->actingAs(User::where('role', 'owner')->first());
        $this->get(route('table.qr.print', $table))->assertOk()->assertSee('Table 9');
    }
}
