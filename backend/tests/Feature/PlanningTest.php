<?php

namespace Tests\Feature;

use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Models\UnitOfMeasure;
use App\Domains\Identity\Models\Branch;
use App\Domains\Identity\Models\Role;
use App\Domains\Identity\Models\User;
use App\Domains\Inventory\Models\InventoryBalance;
use App\Domains\Sales\Models\Sale;
use App\Domains\Sales\Models\SaleLine;
use Carbon\CarbonImmutable;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanningTest extends TestCase
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

    private function createCompletedSale(CarbonImmutable $soldAt, float $quantity, ?int $cashierId = null): Sale
    {
        static $counter = 0;
        $counter++;

        $sale = Sale::query()->create([
            'branch_id' => $this->branch->id, 'sale_number' => 'SALE-TEST-'.$counter, 'status' => 'completed',
            'currency_code' => 'PHP', 'sold_at' => $soldAt, 'completed_at' => $soldAt,
            'subtotal_amount' => $quantity * 100, 'discount_amount' => 0, 'tax_amount' => 0,
            'total_amount' => $quantity * 100, 'cashier_user_id' => $cashierId ?? $this->userWithRole('staff')->id,
            'idempotency_key' => 'test-key-'.$counter, 'correlation_id' => (string) \Illuminate\Support\Str::uuid(),
            'row_version' => 1,
        ]);

        SaleLine::query()->create([
            'sale_id' => $sale->id, 'line_number' => 1, 'product_id' => $this->product->id, 'unit_id' => $this->unit->id,
            'product_sku_snapshot' => $this->product->sku, 'product_name_snapshot' => $this->product->name,
            'quantity' => $quantity, 'stock_quantity_delta' => -$quantity, 'unit_price' => 100, 'discount_amount' => 0,
            'tax_rate' => 12, 'tax_amount' => 0, 'line_total_amount' => $quantity * 100,
        ]);

        return $sale;
    }

    private function createRefundSale(Sale $original, CarbonImmutable $soldAt, float $quantity): Sale
    {
        static $counter = 0;
        $counter++;

        $sale = Sale::query()->create([
            'branch_id' => $this->branch->id, 'sale_number' => 'SALE-REFUND-'.$counter, 'status' => 'refunded',
            'currency_code' => 'PHP', 'sold_at' => $soldAt, 'completed_at' => $soldAt, 'refunded_at' => $soldAt,
            'reverses_sale_id' => $original->id,
            'subtotal_amount' => $quantity * 100, 'discount_amount' => 0, 'tax_amount' => 0,
            'total_amount' => $quantity * 100, 'cashier_user_id' => $original->cashier_user_id,
            'idempotency_key' => 'test-refund-key-'.$counter, 'correlation_id' => (string) \Illuminate\Support\Str::uuid(),
            'row_version' => 1,
        ]);

        SaleLine::query()->create([
            'sale_id' => $sale->id, 'line_number' => 1, 'product_id' => $this->product->id, 'unit_id' => $this->unit->id,
            'product_sku_snapshot' => $this->product->sku, 'product_name_snapshot' => $this->product->name,
            'quantity' => $quantity, 'stock_quantity_delta' => $quantity, 'unit_price' => 100, 'discount_amount' => 0,
            'tax_rate' => 12, 'tax_amount' => 0, 'line_total_amount' => $quantity * 100,
        ]);

        return $sale;
    }

    private function forecastRunPayload(int $windowPeriods, string $periodGrain, CarbonImmutable $end): array
    {
        $periodLengthDays = ['daily' => 1, 'weekly' => 7, 'monthly' => 30][$periodGrain];
        $start = $end->subDays(($windowPeriods * $periodLengthDays) - 1);

        return [
            'branchId' => $this->branch->id,
            'modelCode' => 'sma',
            'periodGrain' => $periodGrain,
            'windowPeriods' => $windowPeriods,
            'historyStartDate' => $start->toDateString(),
            'historyEndDate' => $end->toDateString(),
        ];
    }

    // --- SMA forecast ---

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/forecast-runs?branchId=1')->assertStatus(401);
    }

    public function test_forecast_run_produces_sma_average_from_completed_sales(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $yesterday = CarbonImmutable::now()->subDay();
        $twoDaysAgo = CarbonImmutable::now()->subDays(2);
        $this->createCompletedSale($twoDaysAgo, 10);
        $this->createCompletedSale($yesterday, 20);

        $response = $this->postJson('/api/v1/forecast-runs', $this->forecastRunPayload(2, 'daily', $yesterday));
        $response->assertCreated();
        $response->assertJsonPath('data.status', 'completed');

        $items = $response->json('data.items');
        $item = collect($items)->firstWhere('productId', (string) $this->product->id);
        $this->assertSame('sufficient_history', $item['coldStartStatus']);
        $this->assertSame('30.0000', $item['demandTotal']);
        $this->assertSame('15.0000', $item['forecastQuantity']);
    }

    public function test_product_with_no_sales_is_marked_insufficient_history(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $yesterday = CarbonImmutable::now()->subDay();

        $response = $this->postJson('/api/v1/forecast-runs', $this->forecastRunPayload(2, 'daily', $yesterday));
        $response->assertCreated();

        $item = collect($response->json('data.items'))->firstWhere('productId', (string) $this->product->id);
        $this->assertSame('insufficient_history', $item['coldStartStatus']);
        $this->assertNull($item['forecastQuantity']);
    }

    public function test_refund_reduces_net_demand_in_the_period_it_occurred(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $yesterday = CarbonImmutable::now()->subDay();
        $twoDaysAgo = CarbonImmutable::now()->subDays(2);
        $sale = $this->createCompletedSale($twoDaysAgo, 10);
        $this->createRefundSale($sale, $twoDaysAgo, 4);

        $response = $this->postJson('/api/v1/forecast-runs', $this->forecastRunPayload(2, 'daily', $yesterday));
        $response->assertCreated();

        $item = collect($response->json('data.items'))->firstWhere('productId', (string) $this->product->id);
        $this->assertSame('6.0000', $item['demandTotal']);
    }

    public function test_window_periods_below_minimum_is_rejected(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $yesterday = CarbonImmutable::now()->subDay();
        $payload = $this->forecastRunPayload(2, 'daily', $yesterday);
        $payload['windowPeriods'] = 1;

        $response = $this->postJson('/api/v1/forecast-runs', $payload);
        $response->assertStatus(422);
    }

    public function test_history_range_not_matching_window_periods_is_rejected(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $yesterday = CarbonImmutable::now()->subDay();
        $payload = $this->forecastRunPayload(2, 'daily', $yesterday);
        $payload['historyStartDate'] = CarbonImmutable::now()->subDays(5)->toDateString();

        $response = $this->postJson('/api/v1/forecast-runs', $payload);
        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'INVALID_DATE_RANGE');
    }

    public function test_history_end_date_within_current_period_is_rejected(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $today = CarbonImmutable::now();
        $payload = $this->forecastRunPayload(2, 'daily', $today);

        $response = $this->postJson('/api/v1/forecast-runs', $payload);
        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'INVALID_DATE_RANGE');
    }

    public function test_manual_plan_override_is_recorded_and_audited(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $yesterday = CarbonImmutable::now()->subDay();
        $runId = $this->postJson('/api/v1/forecast-runs', $this->forecastRunPayload(2, 'daily', $yesterday))->json('data.id');

        $response = $this->postJson("/api/v1/forecast-runs/{$runId}/items/{$this->product->id}/manual-plan", [
            'manualQuantity' => 25, 'reason' => 'Upcoming promotion expected to raise demand', 'expiresAt' => now()->addDays(30)->toIso8601String(),
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.manualQuantity', '25.0000');
        $response->assertJsonPath('data.coldStartStatus', 'manual_override');
    }

    // --- EOQ ---

    public function test_eoq_calculation_returns_expected_quantity(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $policyId = $this->postJson('/api/v1/reorder-policies', [
            'branchId' => $this->branch->id, 'productId' => $this->product->id,
            'safetyStockQuantity' => 5, 'safetyStockBasis' => 'policy_minimum', 'leadTimeBasis' => 'product_default',
        ])->json('data.id');

        $response = $this->postJson("/api/v1/reorder-policies/{$policyId}/eoq-calculations", [
            'annualDemandQuantity' => 1000, 'orderingCost' => 50, 'annualHoldingCostPerUnit' => 2, 'currencyCode' => 'PHP',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'valid');
        $response->assertJsonPath('data.recommendedOrderQuantity', '224.0000');
    }

    public function test_eoq_with_zero_holding_cost_is_rejected(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $policyId = $this->postJson('/api/v1/reorder-policies', [
            'branchId' => $this->branch->id, 'productId' => $this->product->id,
            'safetyStockQuantity' => 5, 'safetyStockBasis' => 'policy_minimum', 'leadTimeBasis' => 'product_default',
        ])->json('data.id');

        $response = $this->postJson("/api/v1/reorder-policies/{$policyId}/eoq-calculations", [
            'annualDemandQuantity' => 1000, 'orderingCost' => 50, 'annualHoldingCostPerUnit' => 0, 'currencyCode' => 'PHP',
        ]);

        $response->assertStatus(422);
    }

    // --- ROP ---

    public function test_recalculate_rop_uses_lookback_demand_lead_time_and_safety_stock(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $this->createCompletedSale(CarbonImmutable::now()->subDays(10), 900);

        $policyId = $this->postJson('/api/v1/reorder-policies', [
            'branchId' => $this->branch->id, 'productId' => $this->product->id,
            'safetyStockQuantity' => 5, 'safetyStockBasis' => 'policy_minimum',
            'leadTimeDaysOverride' => 7, 'leadTimeBasis' => 'override',
        ])->json('data.id');

        $response = $this->postJson("/api/v1/reorder-policies/{$policyId}/recalculate-rop", []);

        $response->assertOk();
        $response->assertJsonPath('data.reorderPointQuantity', '75.0000');
    }

    public function test_recalculate_rop_using_a_forecast_run(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $yesterday = CarbonImmutable::now()->subDay();
        $twoDaysAgo = CarbonImmutable::now()->subDays(2);
        $this->createCompletedSale($twoDaysAgo, 20);
        $this->createCompletedSale($yesterday, 20);

        $runId = $this->postJson('/api/v1/forecast-runs', $this->forecastRunPayload(2, 'daily', $yesterday))->json('data.id');

        $policyId = $this->postJson('/api/v1/reorder-policies', [
            'branchId' => $this->branch->id, 'productId' => $this->product->id,
            'safetyStockQuantity' => 5, 'safetyStockBasis' => 'policy_minimum',
            'leadTimeDaysOverride' => 7, 'leadTimeBasis' => 'override',
        ])->json('data.id');

        $response = $this->postJson("/api/v1/reorder-policies/{$policyId}/recalculate-rop", ['forecastRunId' => $runId]);

        $response->assertOk();
        $response->assertJsonPath('data.reorderPointQuantity', '145.0000');
    }

    public function test_recalculate_rop_without_any_lead_time_source_fails(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $this->createCompletedSale(CarbonImmutable::now()->subDays(10), 900);

        $policyId = $this->postJson('/api/v1/reorder-policies', [
            'branchId' => $this->branch->id, 'productId' => $this->product->id,
            'safetyStockQuantity' => 5, 'safetyStockBasis' => 'policy_minimum', 'leadTimeBasis' => 'product_default',
        ])->json('data.id');

        $response = $this->postJson("/api/v1/reorder-policies/{$policyId}/recalculate-rop", []);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'MISSING_DEMAND_OR_LEAD_TIME');
    }

    public function test_duplicate_reorder_policy_is_rejected(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $payload = [
            'branchId' => $this->branch->id, 'productId' => $this->product->id,
            'safetyStockQuantity' => 5, 'safetyStockBasis' => 'policy_minimum', 'leadTimeBasis' => 'product_default',
        ];
        $this->postJson('/api/v1/reorder-policies', $payload)->assertCreated();

        $response = $this->postJson('/api/v1/reorder-policies', $payload);
        $response->assertStatus(409);
        $response->assertJsonPath('error.code', 'DUPLICATE_REORDER_POLICY');
    }

    // --- Restocking alerts ---

    private function createReorderPolicyWithRop(User $actor, float $ropQuantity): int
    {
        $this->actAs($actor);
        $policyId = $this->postJson('/api/v1/reorder-policies', [
            'branchId' => $this->branch->id, 'productId' => $this->product->id,
            'safetyStockQuantity' => 5, 'safetyStockBasis' => 'policy_minimum',
            'leadTimeDaysOverride' => 7, 'leadTimeBasis' => 'override',
        ])->json('data.id');

        $this->createCompletedSale(CarbonImmutable::now()->subDays(10), ($ropQuantity - 5) / 7 * 90);

        $this->postJson("/api/v1/reorder-policies/{$policyId}/recalculate-rop", [])->assertOk();

        return (int) $policyId;
    }

    public function test_evaluate_creates_an_alert_when_available_stock_is_at_or_below_rop(): void
    {
        $manager = $this->userWithRole('manager');
        $policyId = $this->createReorderPolicyWithRop($manager, 75.0);

        InventoryBalance::query()->create([
            'branch_id' => $this->branch->id, 'product_id' => $this->product->id,
            'on_hand_quantity' => 50, 'reserved_quantity' => 0, 'available_quantity' => 50,
            'incoming_quantity' => 0, 'row_version' => 1,
        ]);

        $response = $this->postJson('/api/v1/restocking-alerts/evaluate', ['branchId' => $this->branch->id]);
        $response->assertOk();
        $response->assertJsonPath('data.evaluatedActiveAlertCount', 1);

        $list = $this->getJson("/api/v1/restocking-alerts?branchId={$this->branch->id}");
        $list->assertOk();
        $list->assertJsonPath('data.0.status', 'active');
        $list->assertJsonPath('data.0.reorderPolicyId', (string) $policyId);
    }

    public function test_evaluate_twice_does_not_duplicate_the_active_alert(): void
    {
        $manager = $this->userWithRole('manager');
        $this->createReorderPolicyWithRop($manager, 75.0);

        InventoryBalance::query()->create([
            'branch_id' => $this->branch->id, 'product_id' => $this->product->id,
            'on_hand_quantity' => 50, 'reserved_quantity' => 0, 'available_quantity' => 50,
            'incoming_quantity' => 0, 'row_version' => 1,
        ]);

        $this->postJson('/api/v1/restocking-alerts/evaluate', ['branchId' => $this->branch->id])->assertOk();
        $this->postJson('/api/v1/restocking-alerts/evaluate', ['branchId' => $this->branch->id])->assertOk();

        $list = $this->getJson("/api/v1/restocking-alerts?branchId={$this->branch->id}");
        $list->assertJsonCount(1, 'data');
    }

    public function test_alert_auto_resolves_when_stock_recovers_above_rop(): void
    {
        $manager = $this->userWithRole('manager');
        $this->createReorderPolicyWithRop($manager, 75.0);

        $balance = InventoryBalance::query()->create([
            'branch_id' => $this->branch->id, 'product_id' => $this->product->id,
            'on_hand_quantity' => 50, 'reserved_quantity' => 0, 'available_quantity' => 50,
            'incoming_quantity' => 0, 'row_version' => 1,
        ]);

        $this->postJson('/api/v1/restocking-alerts/evaluate', ['branchId' => $this->branch->id])->assertOk();

        $balance->available_quantity = 200;
        $balance->save();

        $this->postJson('/api/v1/restocking-alerts/evaluate', ['branchId' => $this->branch->id])->assertOk();

        $list = $this->getJson("/api/v1/restocking-alerts?branchId={$this->branch->id}&status=resolved");
        $list->assertJsonCount(1, 'data');
    }

    public function test_dismiss_requires_a_reason(): void
    {
        $manager = $this->userWithRole('manager');
        $this->createReorderPolicyWithRop($manager, 75.0);

        InventoryBalance::query()->create([
            'branch_id' => $this->branch->id, 'product_id' => $this->product->id,
            'on_hand_quantity' => 50, 'reserved_quantity' => 0, 'available_quantity' => 50,
            'incoming_quantity' => 0, 'row_version' => 1,
        ]);
        $this->postJson('/api/v1/restocking-alerts/evaluate', ['branchId' => $this->branch->id])->assertOk();

        $alertId = $this->getJson("/api/v1/restocking-alerts?branchId={$this->branch->id}")->json('data.0.id');

        $this->postJson("/api/v1/restocking-alerts/{$alertId}/dismiss", ['version' => 1])->assertStatus(422);

        $ok = $this->postJson("/api/v1/restocking-alerts/{$alertId}/dismiss", ['reason' => 'Discontinuing this product', 'version' => 1]);
        $ok->assertOk();
        $ok->assertJsonPath('data.status', 'dismissed');
    }

    public function test_staff_can_read_but_not_resolve_alerts(): void
    {
        $manager = $this->userWithRole('manager');
        $this->createReorderPolicyWithRop($manager, 75.0);

        InventoryBalance::query()->create([
            'branch_id' => $this->branch->id, 'product_id' => $this->product->id,
            'on_hand_quantity' => 50, 'reserved_quantity' => 0, 'available_quantity' => 50,
            'incoming_quantity' => 0, 'row_version' => 1,
        ]);
        $this->postJson('/api/v1/restocking-alerts/evaluate', ['branchId' => $this->branch->id])->assertOk();
        $alertId = $this->getJson("/api/v1/restocking-alerts?branchId={$this->branch->id}")->json('data.0.id');

        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        $this->getJson("/api/v1/restocking-alerts?branchId={$this->branch->id}")->assertOk();
        $this->postJson("/api/v1/restocking-alerts/{$alertId}/resolve", ['reason' => 'Test', 'version' => 1])->assertStatus(403);
    }
}
