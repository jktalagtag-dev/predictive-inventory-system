<?php

namespace Tests\Feature;

use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Models\UnitOfMeasure;
use App\Domains\Identity\Models\Branch;
use App\Domains\Identity\Models\Role;
use App\Domains\Identity\Models\User;
use App\Domains\Inventory\Models\InventoryBalance;
use App\Domains\Planning\Models\ForecastRun;
use App\Domains\Planning\Models\ForecastRunItem;
use App\Domains\Planning\Models\ReorderPolicy;
use App\Domains\Planning\Models\RestockingAlert;
use App\Domains\Procurement\Models\PurchaseOrder;
use App\Domains\Procurement\Models\Supplier;
use App\Domains\Sales\Models\Sale;
use App\Domains\Sales\Models\SaleLine;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Product $product;

    private UnitOfMeasure $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->branch = Branch::query()->create(['code' => 'MAIN', 'name' => 'Main Branch', 'country_code' => 'PH', 'row_version' => 1]);
        $category = Category::query()->create(['code' => 'FILT', 'name' => 'Filter Cartridges', 'row_version' => 1]);
        $this->unit = UnitOfMeasure::query()->create(['code' => 'EA', 'name' => 'Each', 'symbol' => 'ea', 'dimension' => 'count', 'is_active' => true, 'row_version' => 1]);
        $this->product = Product::query()->create([
            'category_id' => $category->id, 'stock_unit_id' => $this->unit->id, 'sku' => 'SHX-FLT-010',
            'name' => 'Filter', 'product_type' => 'stock', 'default_tax_rate' => '12.0000',
            'selling_price' => '100.0000', 'row_version' => 1,
        ]);
    }

    private function userWithRole(string $roleCode, bool $assignBranch = true): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('code', $roleCode)->firstOrFail();
        $user->roles()->attach($role->id, ['effective_from' => now(), 'created_at' => now()]);

        if ($assignBranch) {
            $user->branches()->attach($this->branch->id, ['is_default' => true, 'created_at' => now()]);
        }

        return $user;
    }

    /** @see InventoryAdjustmentTest::actAs() for why forgetGuards() is required. */
    private function actAs(User $user): void
    {
        $this->app['auth']->forgetGuards();
        $this->actingAs($user, 'web');
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/dashboard?branchId='.$this->branch->id)->assertStatus(401);
    }

    public function test_branch_id_is_required(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actAs($owner);

        $this->getJson('/api/v1/dashboard')->assertStatus(422);
    }

    public function test_user_without_branch_access_is_rejected(): void
    {
        $otherBranch = Branch::query()->create(['code' => 'ANNEX', 'name' => 'Annex Branch', 'country_code' => 'PH', 'row_version' => 1]);
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $this->getJson('/api/v1/dashboard?branchId='.$otherBranch->id)->assertStatus(403);
    }

    public function test_from_after_to_is_rejected(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actAs($owner);

        $response = $this->getJson('/api/v1/dashboard?branchId='.$this->branch->id.'&from=2026-07-10&to=2026-07-01');
        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'INVALID_DATE_RANGE');
    }

    public function test_date_range_over_ninety_days_is_rejected(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actAs($owner);

        $response = $this->getJson('/api/v1/dashboard?branchId='.$this->branch->id.'&from=2026-01-01&to=2026-07-01');
        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'INVALID_DATE_RANGE');
    }

    public function test_dashboard_aggregates_real_data_across_domains(): void
    {
        $owner = $this->userWithRole('owner');

        InventoryBalance::query()->create([
            'branch_id' => $this->branch->id, 'product_id' => $this->product->id,
            'on_hand_quantity' => '40.0000', 'reserved_quantity' => '0.0000',
            'available_quantity' => '6.0000', 'incoming_quantity' => '0.0000', 'row_version' => 1,
        ]);

        $policy = ReorderPolicy::query()->create([
            'branch_id' => $this->branch->id, 'product_id' => $this->product->id,
            'safety_stock_quantity' => '5.0000', 'reorder_point_quantity' => '20.0000',
            'is_active' => true, 'row_version' => 1,
        ]);

        RestockingAlert::query()->create([
            'reorder_policy_id' => $policy->id, 'status' => 'active', 'severity' => 'critical',
            'available_quantity_snapshot' => '6.0000', 'incoming_quantity_snapshot' => '0.0000',
            'reorder_point_snapshot' => '20.0000', 'recommended_order_quantity' => '30.0000',
            'first_triggered_at' => now(), 'last_evaluated_at' => now(), 'row_version' => 1,
        ]);

        $supplier = Supplier::query()->create([
            'code' => 'AQUAPH', 'legal_name' => 'AquaPure Philippines Inc.',
            'country_code' => 'PH', 'default_currency_code' => 'PHP', 'is_active' => true, 'row_version' => 1,
        ]);

        PurchaseOrder::query()->create([
            'branch_id' => $this->branch->id, 'supplier_id' => $supplier->id, 'po_number' => 'PO-DASH-1',
            'status' => 'submitted', 'currency_code' => 'PHP', 'subtotal_amount' => 5000, 'tax_amount' => 600,
            'discount_amount' => 0, 'total_amount' => 5600, 'row_version' => 1,
            'created_by_user_id' => $owner->id, 'updated_by_user_id' => $owner->id,
        ]);

        $sale = Sale::query()->create([
            'branch_id' => $this->branch->id, 'sale_number' => 'SALE-DASH-1', 'status' => 'completed',
            'currency_code' => 'PHP', 'sold_at' => now(), 'completed_at' => now(),
            'subtotal_amount' => 1250, 'discount_amount' => 0, 'tax_amount' => 150, 'total_amount' => 1400,
            'cashier_user_id' => $owner->id, 'idempotency_key' => 'dash-key-1',
            'correlation_id' => (string) Str::uuid(), 'row_version' => 1,
        ]);
        SaleLine::query()->create([
            'sale_id' => $sale->id, 'line_number' => 1, 'product_id' => $this->product->id, 'unit_id' => $this->unit->id,
            'product_sku_snapshot' => $this->product->sku, 'product_name_snapshot' => $this->product->name,
            'quantity' => 5, 'stock_quantity_delta' => -5, 'unit_price' => 250, 'discount_amount' => 0,
            'tax_rate' => 12, 'tax_amount' => 150, 'line_total_amount' => 1400,
        ]);

        $run = ForecastRun::query()->create([
            'branch_id' => $this->branch->id, 'model_code' => 'sma', 'model_version' => 'sma-v1',
            'period_grain' => 'daily', 'window_periods' => 14, 'history_start_date' => now()->subDays(14),
            'history_end_date' => now()->subDay(), 'data_cutoff_at' => now(), 'status' => 'completed',
            'completed_at' => now(), 'parameters_snapshot' => ['windowPeriods' => 14],
        ]);
        ForecastRunItem::query()->create([
            'forecast_run_id' => $run->id, 'product_id' => $this->product->id,
            'product_sku_snapshot' => $this->product->sku, 'product_name_snapshot' => $this->product->name,
            'history_period_count' => 14, 'demand_total' => '70.0000', 'forecast_quantity' => '5.0000',
            'cold_start_status' => 'sufficient_history', 'input_snapshot' => ['dailyDemand' => '5.0000'],
        ]);

        $this->actAs($owner);
        $response = $this->getJson('/api/v1/dashboard?branchId='.$this->branch->id);

        $response->assertOk();
        $response->assertJsonPath('data.kpis.inventoryOnHand.value', '40.0000');
        $response->assertJsonPath('data.kpis.criticalStockCount.value', '1');
        $response->assertJsonPath('data.lowStock.0.productSku', 'SHX-FLT-010');
        $response->assertJsonPath('data.pendingPurchaseOrders.count', 1);
        $response->assertJsonPath('data.pendingPurchaseOrders.items.0.poNumber', 'PO-DASH-1');
        $response->assertJsonPath('data.recentSales.0.saleNumber', 'SALE-DASH-1');
        $response->assertJsonPath('data.forecastSummary.totalProductCount', 1);
        $response->assertJsonPath('data.forecastSummary.sufficientHistoryCount', 1);
        $response->assertJsonPath('data.syncHealth.pendingCount', 0);
        $response->assertJsonPath('meta.branchId', (string) $this->branch->id);
        $this->assertNotNull($response->json('meta.generatedAt'));
    }

    public function test_dashboard_query_count_does_not_scale_with_row_count(): void
    {
        $owner = $this->userWithRole('owner');
        $category = Category::query()->create(['code' => 'FILT2', 'name' => 'Filters 2', 'row_version' => 1]);

        for ($i = 1; $i <= 6; $i++) {
            $product = Product::query()->create([
                'category_id' => $category->id, 'stock_unit_id' => $this->unit->id, 'sku' => "SHX-FLT-{$i}00",
                'name' => "Filter {$i}", 'product_type' => 'stock', 'default_tax_rate' => '12.0000',
                'selling_price' => '100.0000', 'row_version' => 1,
            ]);

            InventoryBalance::query()->create([
                'branch_id' => $this->branch->id, 'product_id' => $product->id,
                'on_hand_quantity' => '40.0000', 'reserved_quantity' => '0.0000',
                'available_quantity' => '6.0000', 'incoming_quantity' => '0.0000', 'row_version' => 1,
            ]);

            $policy = ReorderPolicy::query()->create([
                'branch_id' => $this->branch->id, 'product_id' => $product->id,
                'safety_stock_quantity' => '5.0000', 'reorder_point_quantity' => '20.0000',
                'is_active' => true, 'row_version' => 1,
            ]);

            RestockingAlert::query()->create([
                'reorder_policy_id' => $policy->id, 'status' => 'active', 'severity' => 'critical',
                'available_quantity_snapshot' => '6.0000', 'incoming_quantity_snapshot' => '0.0000',
                'reorder_point_snapshot' => '20.0000', 'recommended_order_quantity' => '30.0000',
                'first_triggered_at' => now(), 'last_evaluated_at' => now(), 'row_version' => 1,
            ]);

            $supplier = Supplier::query()->create([
                'code' => "SUP{$i}", 'legal_name' => "Supplier {$i}",
                'country_code' => 'PH', 'default_currency_code' => 'PHP', 'is_active' => true, 'row_version' => 1,
            ]);

            PurchaseOrder::query()->create([
                'branch_id' => $this->branch->id, 'supplier_id' => $supplier->id, 'po_number' => "PO-DASH-{$i}",
                'status' => 'submitted', 'currency_code' => 'PHP', 'subtotal_amount' => 5000, 'tax_amount' => 600,
                'discount_amount' => 0, 'total_amount' => 5600, 'row_version' => 1,
                'created_by_user_id' => $owner->id, 'updated_by_user_id' => $owner->id,
            ]);

            $cashier = $this->userWithRole('staff');
            $sale = Sale::query()->create([
                'branch_id' => $this->branch->id, 'sale_number' => "SALE-DASH-{$i}", 'status' => 'completed',
                'currency_code' => 'PHP', 'sold_at' => now(), 'completed_at' => now(),
                'subtotal_amount' => 1250, 'discount_amount' => 0, 'tax_amount' => 150, 'total_amount' => 1400,
                'cashier_user_id' => $cashier->id, 'idempotency_key' => "dash-key-{$i}",
                'correlation_id' => (string) Str::uuid(), 'row_version' => 1,
            ]);
            SaleLine::query()->create([
                'sale_id' => $sale->id, 'line_number' => 1, 'product_id' => $product->id, 'unit_id' => $this->unit->id,
                'product_sku_snapshot' => $product->sku, 'product_name_snapshot' => $product->name,
                'quantity' => 5, 'stock_quantity_delta' => -5, 'unit_price' => 250, 'discount_amount' => 0,
                'tax_rate' => 12, 'tax_amount' => 150, 'line_total_amount' => 1400,
            ]);
        }

        $this->actAs($owner);

        DB::enableQueryLog();
        DB::flushQueryLog();
        $response = $this->getJson('/api/v1/dashboard?branchId='.$this->branch->id);
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $response->assertOk();
        // Regression guard: each dashboard section issues a fixed, small number of
        // queries regardless of row count (eager loading of supplier/cashier/product
        // relations), never one query per related row.
        $this->assertLessThanOrEqual(25, $queryCount, "Dashboard issued {$queryCount} queries for 6 rows per section — check for N+1 eager loading regressions.");
    }

    public function test_dashboard_with_no_data_returns_empty_but_valid_shape(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actAs($owner);

        $response = $this->getJson('/api/v1/dashboard?branchId='.$this->branch->id);
        $response->assertOk();
        $response->assertJsonPath('data.kpis.inventoryOnHand.value', '0.0000');
        $response->assertJsonPath('data.lowStock', []);
        $response->assertJsonPath('data.pendingPurchaseOrders.count', 0);
        $response->assertJsonPath('data.recentSales', []);
        $response->assertJsonPath('data.forecastSummary', null);
    }
}
