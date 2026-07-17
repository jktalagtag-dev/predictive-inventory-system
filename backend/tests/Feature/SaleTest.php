<?php

namespace Tests\Feature;

use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Models\UnitOfMeasure;
use App\Domains\Identity\Models\Branch;
use App\Domains\Identity\Models\Role;
use App\Domains\Identity\Models\User;
use App\Domains\Inventory\Models\InventoryBalance;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleTest extends TestCase
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

        InventoryBalance::query()->create([
            'branch_id' => $this->branch->id, 'product_id' => $this->product->id,
            'on_hand_quantity' => 20, 'reserved_quantity' => 0, 'available_quantity' => 20,
            'incoming_quantity' => 0, 'row_version' => 1,
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

    private function salePayload(float $quantity = 5, ?float $unitPrice = null, ?float $discountAmount = null, ?string $overrideReason = null): array
    {
        $lineTotal = ($unitPrice ?? 100.0) * $quantity - ($discountAmount ?? 0);
        $tax = round($lineTotal * 0.12, 4);
        $total = round($lineTotal + $tax, 4);

        return [
            'branchId' => $this->branch->id,
            'soldAt' => now()->toIso8601String(),
            'currencyCode' => 'PHP',
            'lines' => [array_filter([
                'productId' => $this->product->id,
                'productUnitId' => $this->unit->id,
                'quantity' => $quantity,
                'unitPrice' => $unitPrice,
                'discountAmount' => $discountAmount,
                'overrideReason' => $overrideReason,
            ], fn ($value) => $value !== null)],
            'payments' => [[
                'paymentMethod' => 'cash',
                'amount' => $total,
            ]],
        ];
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/sales?branchId=1')->assertStatus(401);
    }

    public function test_finalize_sale_creates_completed_sale_and_decrements_stock(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        $response = $this->postJson('/api/v1/sales', $this->salePayload(5), ['Idempotency-Key' => 'sale-1']);
        $response->assertCreated();
        $response->assertJsonPath('data.status', 'completed');
        $response->assertJsonPath('data.totalAmount', '560.0000');

        $balance = InventoryBalance::query()->where('branch_id', $this->branch->id)->where('product_id', $this->product->id)->first();
        $this->assertSame('15.0000', $balance->on_hand_quantity);
    }

    public function test_finalize_sale_is_idempotent_and_does_not_double_apply(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        $payload = $this->salePayload(5);

        $first = $this->postJson('/api/v1/sales', $payload, ['Idempotency-Key' => 'same-key']);
        $first->assertCreated();
        $saleId = $first->json('data.id');

        $second = $this->postJson('/api/v1/sales', $payload, ['Idempotency-Key' => 'same-key']);
        $second->assertCreated();
        $this->assertSame($saleId, $second->json('data.id'));

        $balance = InventoryBalance::query()->where('branch_id', $this->branch->id)->where('product_id', $this->product->id)->first();
        $this->assertSame('15.0000', $balance->on_hand_quantity);
    }

    public function test_finalize_sale_without_idempotency_key_is_rejected(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        $this->postJson('/api/v1/sales', $this->salePayload(5))->assertStatus(422);
    }

    public function test_insufficient_stock_is_rejected(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        $response = $this->postJson('/api/v1/sales', $this->salePayload(50), ['Idempotency-Key' => 'sale-oversell']);
        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'INSUFFICIENT_STOCK');

        $balance = InventoryBalance::query()->where('branch_id', $this->branch->id)->where('product_id', $this->product->id)->first();
        $this->assertSame('20.0000', $balance->on_hand_quantity);
    }

    public function test_payment_total_mismatch_is_rejected(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        $payload = $this->salePayload(5);
        $payload['payments'][0]['amount'] = 1.00;

        $response = $this->postJson('/api/v1/sales', $payload, ['Idempotency-Key' => 'sale-mismatch']);
        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'PAYMENT_TOTAL_MISMATCH');
    }

    public function test_price_override_without_permission_is_rejected(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        $payload = $this->salePayload(5, 80.0, null, 'Loyalty customer');
        $payload['payments'][0]['amount'] = round(80 * 5 * 1.12, 4);

        $response = $this->postJson('/api/v1/sales', $payload, ['Idempotency-Key' => 'sale-override']);
        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'PRICE_OVERRIDE_FORBIDDEN');
    }

    public function test_manager_can_override_price_with_reason(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $payload = $this->salePayload(5, 80.0, null, 'Loyalty customer');
        $payload['payments'][0]['amount'] = round(80 * 5 * 1.12, 4);

        $response = $this->postJson('/api/v1/sales', $payload, ['Idempotency-Key' => 'sale-override-mgr']);
        $response->assertCreated();
        $response->assertJsonPath('data.lines.0.unitPrice', '80.0000');
    }

    public function test_discount_without_permission_is_rejected(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        $payload = $this->salePayload(5, null, 20.0, 'Damaged packaging');
        $payload['payments'][0]['amount'] = round((100 * 5 - 20) * 1.12, 4);

        $response = $this->postJson('/api/v1/sales', $payload, ['Idempotency-Key' => 'sale-discount']);
        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'DISCOUNT_FORBIDDEN');
    }

    public function test_void_restores_stock_and_marks_sale_voided(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $sale = $this->postJson('/api/v1/sales', $this->salePayload(5), ['Idempotency-Key' => 'sale-to-void']);
        $saleId = $sale->json('data.id');

        $response = $this->postJson("/api/v1/sales/{$saleId}/void", ['reason' => 'Customer cancelled', 'version' => 1], ['Idempotency-Key' => 'void-1']);
        $response->assertOk();
        $response->assertJsonPath('data.status', 'voided');

        $balance = InventoryBalance::query()->where('branch_id', $this->branch->id)->where('product_id', $this->product->id)->first();
        $this->assertSame('20.0000', $balance->on_hand_quantity);
    }

    public function test_voiding_an_already_voided_sale_is_rejected(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $sale = $this->postJson('/api/v1/sales', $this->salePayload(5), ['Idempotency-Key' => 'sale-double-void']);
        $saleId = $sale->json('data.id');

        $this->postJson("/api/v1/sales/{$saleId}/void", ['reason' => 'First void', 'version' => 1], ['Idempotency-Key' => 'void-a'])->assertOk();

        $response = $this->postJson("/api/v1/sales/{$saleId}/void", ['reason' => 'Second void', 'version' => 2], ['Idempotency-Key' => 'void-b']);
        $response->assertStatus(409);
        $response->assertJsonPath('error.code', 'ALREADY_REVERSED');
    }

    public function test_staff_cannot_void_a_sale(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        $sale = $this->postJson('/api/v1/sales', $this->salePayload(5), ['Idempotency-Key' => 'sale-staff-void']);
        $saleId = $sale->json('data.id');

        $this->postJson("/api/v1/sales/{$saleId}/void", ['reason' => 'Test', 'version' => 1], ['Idempotency-Key' => 'staff-void-attempt'])
            ->assertStatus(403);
    }

    public function test_partial_refund_restores_partial_stock_and_creates_linked_refund_document(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $sale = $this->postJson('/api/v1/sales', $this->salePayload(10), ['Idempotency-Key' => 'sale-to-refund']);
        $saleId = $sale->json('data.id');

        $refundAmount = round(100 * 4 * 1.12, 4);
        $response = $this->postJson("/api/v1/sales/{$saleId}/refunds", [
            'reason' => 'Customer returned some units',
            'version' => 1,
            'lines' => [['productId' => $this->product->id, 'quantity' => 4]],
            'payments' => [['paymentMethod' => 'cash', 'amount' => $refundAmount]],
        ], ['Idempotency-Key' => 'refund-1']);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'refunded');
        $response->assertJsonPath('data.reversesSaleId', (string) $saleId);

        $balance = InventoryBalance::query()->where('branch_id', $this->branch->id)->where('product_id', $this->product->id)->first();
        $this->assertSame('14.0000', $balance->on_hand_quantity);

        $original = $this->getJson("/api/v1/sales/{$saleId}");
        $original->assertJsonPath('data.status', 'completed');
    }

    public function test_refund_quantity_exceeding_remaining_is_rejected(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $sale = $this->postJson('/api/v1/sales', $this->salePayload(5), ['Idempotency-Key' => 'sale-over-refund']);
        $saleId = $sale->json('data.id');

        $response = $this->postJson("/api/v1/sales/{$saleId}/refunds", [
            'reason' => 'Too many returned',
            'version' => 1,
            'lines' => [['productId' => $this->product->id, 'quantity' => 6]],
            'payments' => [['paymentMethod' => 'cash', 'amount' => round(100 * 6 * 1.12, 4)]],
        ], ['Idempotency-Key' => 'refund-over']);

        $response->assertStatus(409);
        $response->assertJsonPath('error.code', 'QUANTITY_ALREADY_REFUNDED');
    }

    public function test_full_refund_marks_original_sale_refunded(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $sale = $this->postJson('/api/v1/sales', $this->salePayload(5), ['Idempotency-Key' => 'sale-full-refund']);
        $saleId = $sale->json('data.id');

        $response = $this->postJson("/api/v1/sales/{$saleId}/refunds", [
            'reason' => 'Full return',
            'version' => 1,
            'lines' => [['productId' => $this->product->id, 'quantity' => 5]],
            'payments' => [['paymentMethod' => 'cash', 'amount' => round(100 * 5 * 1.12, 4)]],
        ], ['Idempotency-Key' => 'refund-full']);

        $response->assertCreated();

        $original = $this->getJson("/api/v1/sales/{$saleId}");
        $original->assertJsonPath('data.status', 'refunded');
    }

    public function test_list_endpoint_reports_line_count_without_loading_full_lines(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        $this->postJson('/api/v1/sales', $this->salePayload(5), ['Idempotency-Key' => 'sale-list-1'])->assertCreated();

        $index = $this->getJson("/api/v1/sales?branchId={$this->branch->id}");
        $index->assertOk();
        $index->assertJsonPath('data.0.lineCount', 1);
        $index->assertJsonPath('data.0.lines', []);
        $index->assertJsonPath('data.0.cashierName', $staff->display_name);
    }

    public function test_user_without_branch_access_cannot_view_sales(): void
    {
        $staff = $this->userWithRole('staff', assignBranch: false);
        $this->actAs($staff);

        $this->getJson("/api/v1/sales?branchId={$this->branch->id}")->assertStatus(403);
    }
}
