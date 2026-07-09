<?php

namespace Tests\Feature;

use App\Filament\Pages\PointOfSale;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\MenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_pos_flow_from_shift_open_to_z_report(): void
    {
        $this->seed(MenuSeeder::class);

        $cashier = User::where('role', 'cashier')->first();
        $this->actingAs($cashier);

        // Open shift with ₦5,000 float
        Livewire::test(PointOfSale::class)
            ->set('openingFloat', '5000')
            ->call('openShift');

        $shift = Shift::currentFor($cashier->id);
        $this->assertNotNull($shift);
        $this->assertEquals(5000.0, (float) $shift->opening_float);

        // Sell: Rice & Beans (₦1,000) + Salad (₦200) = ₦1,200, paid cash ₦2,000
        $riceBeans = Product::where('name', 'Rice & Beans with Oil & Pepper')->firstOrFail();
        $salad = $riceBeans->options()->where('name', 'Salad')->firstOrFail();

        Livewire::test(PointOfSale::class)
            ->call('selectProduct', $riceBeans->id)
            ->call('toggleOption', $salad->id)
            ->call('confirmOptions')
            ->call('startPayment')
            ->set('amountPaid', '2000')
            ->call('completeSale');

        $sale = Sale::first();
        $this->assertNotNull($sale);
        $this->assertEquals(1200.0, (float) $sale->total);
        $this->assertEquals(800.0, (float) $sale->change_due);
        $this->assertEquals('cash', $sale->payment_method);
        $this->assertCount(1, $sale->items);
        $this->assertEquals('Rice', $sale->items->first()->section);

        // Transfer sale without reference must be refused
        $tea = Product::where('name', 'Zobo')->firstOrFail(); // no options — adds directly
        $component = Livewire::test(PointOfSale::class)
            ->call('selectProduct', $tea->id)
            ->call('startPayment')
            ->set('paymentMethod', 'transfer')
            ->call('completeSale');
        $component->assertHasErrors(['paymentReference']);
        $this->assertEquals(1, Sale::count());

        // With reference it goes through
        $component->set('paymentReference', 'MP-12345')->call('completeSale');
        $this->assertEquals(2, Sale::count());

        // Receipt prints
        $this->get(route('receipt.print', $sale))
            ->assertOk()
            ->assertSee($sale->receipt_no)
            ->assertSee('CUSTOMER COPY')
            ->assertSee('RICE SECTION')
            ->assertSee('Serve ONLY against this ticket');

        // Close shift: expected cash = 5000 + 1200 = 6200; cashier counts 6000 → short ₦200
        Livewire::test(PointOfSale::class)
            ->set('countedCash', '6000')
            ->call('closeShift');

        $shift->refresh();
        $this->assertNotNull($shift->closed_at);
        $this->assertEquals(6200.0, (float) $shift->expected_cash);
        $this->assertEquals(-200.0, (float) $shift->variance);

        // Z-report renders
        $this->get(route('shift.report', $shift))
            ->assertOk()
            ->assertSee('Z-REPORT')
            ->assertSee('SHORT');
    }

    public function test_owner_dashboard_and_permissions(): void
    {
        $this->seed(MenuSeeder::class);

        // Cashier cannot see menu setup, staff, or reports
        $this->actingAs(User::where('role', 'cashier')->first());
        $this->get('/admin/products')->assertForbidden();
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/daily-report')->assertForbidden();
        $this->get(route('daily.report.print', ['date' => today()->toDateString()]))->assertForbidden();

        // Owner sees everything
        $this->flushSession();
        $this->actingAs(User::where('role', 'owner')->first());
        $this->get('/admin')->assertOk();
        $this->get('/admin/products')->assertOk();
        $this->get('/admin/sales')->assertOk();
        $this->get('/admin/point-of-sale')->assertOk();
        $this->get('/admin/daily-report')->assertOk();
        $this->get(route('daily.report.print', ['date' => today()->toDateString()]))
            ->assertOk()
            ->assertSee('Daily Sales Report');
    }
}
