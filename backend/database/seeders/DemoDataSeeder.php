<?php

namespace Database\Seeders;

use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Models\UnitOfMeasure;
use App\Domains\Identity\Models\Branch;
use App\Domains\Identity\Models\Role;
use App\Domains\Identity\Models\User;
use App\Domains\Inventory\Services\GoodsReceiptService;
use App\Domains\Inventory\Services\InventoryAdjustmentService;
use App\Domains\Planning\Models\ReorderPolicy;
use App\Domains\Planning\Services\RestockingAlertService;
use App\Domains\Planning\Services\SmaForecastService;
use App\Domains\Procurement\Models\PurchaseOrder;
use App\Domains\Procurement\Models\Supplier;
use App\Domains\Procurement\Services\PurchaseOrderService;
use App\Domains\Sales\Services\SaleService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Populates every transactional domain with realistic, mutually consistent
 * demo data for local development and CI only. Every workflow-driven record
 * (purchase orders, goods receipts, inventory adjustments, sales) is created
 * by calling the real domain services rather than raw inserts, so the
 * resulting movements, balances, and audit-log rows are exactly what the
 * app itself would have produced (CLAUDE.md section 8's single source of
 * truth applies to seed data too).
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        // Idempotency guard: skip the whole pass if it has already run.
        if (Product::query()->where('sku', 'SHX-FLT-001')->exists()) {
            return;
        }

        $branch = Branch::query()->where('code', 'MAIN')->firstOrFail();
        $unit = UnitOfMeasure::query()->where('code', 'EA')->firstOrFail();
        $owner = User::query()
            ->where('email', env('OWNER_SEED_EMAIL', 'owner@stevenhydrotech.example'))
            ->firstOrFail();

        $users = $this->seedDemoUsers($branch);
        $categories = $this->seedCategories();
        $suppliers = $this->seedSuppliers();
        $products = $this->seedProducts($categories, $unit);

        $this->seedPurchaseOrdersAndReceipts($branch, $suppliers, $products, $users['manager'], $owner, $unit);
        $this->seedInventoryAdjustments($branch, $products, $users['manager'], $owner);
        $this->seedSales($branch, $products, $users['staffOne'], $users['staffTwo'], $unit);
        $this->seedReorderPolicies($branch, $products, $users['manager']);

        app(RestockingAlertService::class)->evaluateAll($branch->id);

        $this->runForecast($branch, $owner);
    }

    /**
     * @return array{manager: User, staffOne: User, staffTwo: User}
     */
    private function seedDemoUsers(Branch $branch): array
    {
        $managerRole = Role::query()->where('code', 'manager')->firstOrFail();
        $staffRole = Role::query()->where('code', 'staff')->firstOrFail();

        return [
            'manager' => $this->seedUser('marco.villareal@stevenhydrotech.example', 'Marco', 'Villareal', $managerRole, $branch),
            'staffOne' => $this->seedUser('grace.dizon@stevenhydrotech.example', 'Grace', 'Dizon', $staffRole, $branch),
            'staffTwo' => $this->seedUser('paolo.reyes@stevenhydrotech.example', 'Paolo', 'Reyes', $staffRole, $branch),
        ];
    }

    private function seedUser(string $email, string $firstName, string $lastName, Role $role, Branch $branch): User
    {
        $displayName = "{$firstName} {$lastName}";

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'password_hash' => Hash::make('ChangeMe!12345'),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'display_name' => $displayName,
                'avatar_url' => 'https://api.dicebear.com/7.x/initials/svg?seed='.urlencode($displayName),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $user->roles()->syncWithoutDetaching([$role->id => ['effective_from' => now(), 'created_at' => now()]]);
        $user->branches()->syncWithoutDetaching([$branch->id => ['is_default' => true, 'created_at' => now()]]);

        return $user;
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(): array
    {
        $definitions = [
            'FILT' => ['name' => 'Filter Cartridges', 'description' => 'Sediment, carbon, and post-filter cartridges.'],
            'MEMB' => ['name' => 'RO Membranes', 'description' => 'Reverse osmosis membrane elements.'],
            'UV' => ['name' => 'UV Sterilizers & Lamps', 'description' => 'Ultraviolet disinfection systems and replacement lamps.'],
            'TANK' => ['name' => 'Storage Tanks', 'description' => 'Pressure and atmospheric water storage tanks.'],
            'DISP' => ['name' => 'Water Dispensers', 'description' => 'Countertop and floor-standing water dispensers.'],
            'FITT' => ['name' => 'Fittings & Valves', 'description' => 'Quick-connect fittings, valves, and adapters.'],
            'CHEM' => ['name' => 'Chemicals & Media', 'description' => 'Filter media and water treatment chemicals.'],
            'ACC' => ['name' => 'Accessories & Parts', 'description' => 'Tools and spare parts for installation and maintenance.'],
        ];

        $categories = [];
        foreach ($definitions as $code => $definition) {
            $categories[$code] = Category::query()->updateOrCreate(
                ['code' => $code],
                ['name' => $definition['name'], 'description' => $definition['description'], 'is_active' => true, 'row_version' => 1],
            );
        }

        return $categories;
    }

    /**
     * @return array<string, Supplier>
     */
    private function seedSuppliers(): array
    {
        $definitions = [
            'AQUAPH' => 'AquaPure Philippines Inc.',
            'CRYSTALWTR' => 'Crystal Clear Water Systems Corp.',
            'HYDROTECH' => 'HydroTech Filtration Supply',
            'PUREFLOW' => 'PureFlow Membrane Distributors',
            'WATERWORKS' => 'Metro WaterWorks Trading',
        ];

        $suppliers = [];
        foreach ($definitions as $code => $legalName) {
            $suppliers[$code] = Supplier::query()->updateOrCreate(
                ['code' => $code],
                [
                    'legal_name' => $legalName,
                    'country_code' => 'PH',
                    'default_currency_code' => 'PHP',
                    'is_active' => true,
                    'row_version' => 1,
                ],
            );
        }

        return $suppliers;
    }

    /**
     * @return array<string, Product>
     */
    private function seedProducts(array $categories, UnitOfMeasure $unit): array
    {
        $definitions = [
            ['sku' => 'SHX-FLT-001', 'category' => 'FILT', 'name' => '5-Micron Sediment Filter Cartridge', 'price' => '250.0000'],
            ['sku' => 'SHX-FLT-002', 'category' => 'FILT', 'name' => 'Carbon Block Filter Cartridge', 'price' => '380.0000'],
            ['sku' => 'SHX-FLT-003', 'category' => 'FILT', 'name' => 'GAC Granular Activated Carbon Filter', 'price' => '420.0000'],
            ['sku' => 'SHX-FLT-004', 'category' => 'FILT', 'name' => '1-Micron Post Carbon Filter', 'price' => '310.0000'],
            ['sku' => 'SHX-MEM-001', 'category' => 'MEMB', 'name' => '75 GPD RO Membrane', 'price' => '1450.0000'],
            ['sku' => 'SHX-MEM-002', 'category' => 'MEMB', 'name' => '100 GPD RO Membrane', 'price' => '1850.0000'],
            ['sku' => 'SHX-MEM-003', 'category' => 'MEMB', 'name' => '150 GPD Commercial RO Membrane', 'price' => '2600.0000'],
            ['sku' => 'SHX-UVL-001', 'category' => 'UV', 'name' => '11W UV Sterilizer Lamp', 'price' => '1650.0000'],
            ['sku' => 'SHX-UVL-002', 'category' => 'UV', 'name' => '40W UV Sterilizer System', 'price' => '4200.0000'],
            ['sku' => 'SHX-UVL-003', 'category' => 'UV', 'name' => 'UV Quartz Sleeve Replacement', 'price' => '980.0000'],
            ['sku' => 'SHX-TNK-001', 'category' => 'TANK', 'name' => '20L Pressure Storage Tank', 'price' => '3800.0000'],
            ['sku' => 'SHX-TNK-002', 'category' => 'TANK', 'name' => '100L Polyethylene Water Tank', 'price' => '6500.0000'],
            ['sku' => 'SHX-TNK-003', 'category' => 'TANK', 'name' => '500L Vertical Storage Tank', 'price' => '14500.0000'],
            ['sku' => 'SHX-DSP-001', 'category' => 'DISP', 'name' => 'Hot and Cold Water Dispenser', 'price' => '4800.0000'],
            ['sku' => 'SHX-DSP-002', 'category' => 'DISP', 'name' => 'Bottom-Load Water Dispenser', 'price' => '6200.0000'],
            ['sku' => 'SHX-DSP-003', 'category' => 'DISP', 'name' => 'Countertop Water Dispenser', 'price' => '3600.0000'],
            ['sku' => 'SHX-FIT-001', 'category' => 'FITT', 'name' => '1/4 inch Quick Connect Fitting', 'price' => '60.0000'],
            ['sku' => 'SHX-FIT-002', 'category' => 'FITT', 'name' => 'Push-Fit Ball Valve', 'price' => '150.0000'],
            ['sku' => 'SHX-FIT-003', 'category' => 'FITT', 'name' => 'Feed Water Adapter Valve', 'price' => '120.0000'],
            ['sku' => 'SHX-FIT-004', 'category' => 'FITT', 'name' => 'Faucet Diverter Valve', 'price' => '220.0000'],
            ['sku' => 'SHX-CHM-001', 'category' => 'CHEM', 'name' => 'Calcium Carbonate Filter Media (1kg)', 'price' => '420.0000'],
            ['sku' => 'SHX-CHM-002', 'category' => 'CHEM', 'name' => 'Sodium Hypochlorite Disinfectant (5L)', 'price' => '650.0000'],
            ['sku' => 'SHX-CHM-003', 'category' => 'CHEM', 'name' => 'Anti-Scale Filter Media', 'price' => '540.0000'],
            ['sku' => 'SHX-ACC-001', 'category' => 'ACC', 'name' => 'Filter Housing Wrench', 'price' => '180.0000'],
            ['sku' => 'SHX-ACC-002', 'category' => 'ACC', 'name' => 'O-Ring Seal Kit', 'price' => '90.0000'],
            ['sku' => 'SHX-ACC-003', 'category' => 'ACC', 'name' => 'Filter Housing Bracket', 'price' => '160.0000'],
        ];

        $products = [];
        foreach ($definitions as $definition) {
            $products[$definition['sku']] = Product::factory()->create([
                'category_id' => $categories[$definition['category']]->id,
                'stock_unit_id' => $unit->id,
                'sku' => $definition['sku'],
                'barcode' => null,
                'name' => $definition['name'],
                'description' => $definition['name'].' for residential and commercial water treatment systems.',
                'image_url' => "https://picsum.photos/seed/{$definition['sku']}/600/600",
                'product_type' => 'stock',
                'default_tax_rate' => '12.0000',
                'selling_price' => $definition['price'],
                'default_lead_time_days' => '7.00',
            ]);
        }

        return $products;
    }

    /**
     * @param  array<string, Supplier>  $suppliers
     * @param  array<string, Product>  $products
     */
    private function seedPurchaseOrdersAndReceipts(Branch $branch, array $suppliers, array $products, User $manager, User $owner, UnitOfMeasure $unit): void
    {
        $poService = app(PurchaseOrderService::class);
        $grService = app(GoodsReceiptService::class);

        // PO 1: draft only.
        $this->createDraftPo($poService, $branch, $suppliers['AQUAPH'], $manager, $unit, $products, [
            ['sku' => 'SHX-FLT-001', 'qty' => '200', 'cost' => '150.0000'],
            ['sku' => 'SHX-FLT-002', 'qty' => '150', 'cost' => '230.0000'],
        ]);

        // PO 2: submitted only.
        $po2 = $this->createDraftPo($poService, $branch, $suppliers['CRYSTALWTR'], $manager, $unit, $products, [
            ['sku' => 'SHX-MEM-001', 'qty' => '30', 'cost' => '900.0000'],
        ]);
        $poService->submit($po2, $manager, (string) Str::uuid());

        // PO 3: approved only.
        $po3 = $this->createDraftPo($poService, $branch, $suppliers['HYDROTECH'], $manager, $unit, $products, [
            ['sku' => 'SHX-UVL-001', 'qty' => '20', 'cost' => '1000.0000'],
        ]);
        $poService->submit($po3, $manager, (string) Str::uuid());
        $poService->decide($po3, 'approved', null, $owner, (string) Str::uuid());

        // PO 4: ordered only, no receiving yet.
        $po4 = $this->createDraftPo($poService, $branch, $suppliers['PUREFLOW'], $manager, $unit, $products, [
            ['sku' => 'SHX-TNK-001', 'qty' => '15', 'cost' => '2600.0000'],
        ]);
        $poService->submit($po4, $manager, (string) Str::uuid());
        $poService->decide($po4, 'approved', null, $owner, (string) Str::uuid());
        $poService->markOrdered($po4, now(), 'SO-2201', $manager, (string) Str::uuid());

        // PO 5: ordered, then closed without ever being received.
        $po5 = $this->createDraftPo($poService, $branch, $suppliers['WATERWORKS'], $manager, $unit, $products, [
            ['sku' => 'SHX-FIT-001', 'qty' => '100', 'cost' => '35.0000'],
        ]);
        $poService->submit($po5, $manager, (string) Str::uuid());
        $poService->decide($po5, 'approved', null, $owner, (string) Str::uuid());
        $poService->markOrdered($po5, now(), 'SO-2202', $manager, (string) Str::uuid());
        $poService->close($po5, 'Supplier discontinued the line before delivery.', $manager, (string) Str::uuid());

        // PO 6: ordered, fully received — the main stock source for the sales demo below.
        $po6 = $this->createDraftPo($poService, $branch, $suppliers['AQUAPH'], $manager, $unit, $products, [
            ['sku' => 'SHX-FLT-001', 'qty' => '250', 'cost' => '150.0000'],
            ['sku' => 'SHX-FLT-002', 'qty' => '180', 'cost' => '230.0000'],
            ['sku' => 'SHX-FLT-004', 'qty' => '200', 'cost' => '190.0000'],
        ]);
        $poService->submit($po6, $manager, (string) Str::uuid());
        $poService->decide($po6, 'approved', null, $owner, (string) Str::uuid());
        $poService->markOrdered($po6, now()->subDays(50), 'SO-2100', $manager, (string) Str::uuid());
        $this->receiveFullPo($grService, $po6, $branch, $manager, now()->subDays(46));

        // PO 7: ordered, partially received — leaves a deliberately thin
        // dispenser balance so it shows a real restocking alert.
        $po7 = $this->createDraftPo($poService, $branch, $suppliers['CRYSTALWTR'], $manager, $unit, $products, [
            ['sku' => 'SHX-DSP-001', 'qty' => '30', 'cost' => '3200.0000'],
            ['sku' => 'SHX-DSP-002', 'qty' => '20', 'cost' => '4100.0000'],
        ]);
        $poService->submit($po7, $manager, (string) Str::uuid());
        $poService->decide($po7, 'approved', null, $owner, (string) Str::uuid());
        $poService->markOrdered($po7, now()->subDays(40), 'SO-2150', $manager, (string) Str::uuid());
        $this->receivePartialPo($grService, $po7, $branch, $manager, now()->subDays(35));

        // PO 8: ordered, fully received but a deliberately thin quantity —
        // another guaranteed restocking-alert candidate, never sold from.
        $po8 = $this->createDraftPo($poService, $branch, $suppliers['AQUAPH'], $manager, $unit, $products, [
            ['sku' => 'SHX-FLT-003', 'qty' => '15', 'cost' => '260.0000'],
        ]);
        $poService->submit($po8, $manager, (string) Str::uuid());
        $poService->decide($po8, 'approved', null, $owner, (string) Str::uuid());
        $poService->markOrdered($po8, now()->subDays(20), 'SO-2170', $manager, (string) Str::uuid());
        $this->receiveFullPo($grService, $po8, $branch, $manager, now()->subDays(18));
    }

    /**
     * @param  array<string, Product>  $products
     * @param  array<int, array{sku: string, qty: string, cost: string}>  $lineDefs
     */
    private function createDraftPo(PurchaseOrderService $poService, Branch $branch, Supplier $supplier, User $actor, UnitOfMeasure $unit, array $products, array $lineDefs): PurchaseOrder
    {
        $lines = array_map(fn ($lineDef) => [
            'product_id' => $products[$lineDef['sku']]->id,
            'unit_id' => $unit->id,
            'ordered_quantity' => $lineDef['qty'],
            'unit_cost' => $lineDef['cost'],
            'tax_rate' => '12',
        ], $lineDefs);

        return $poService->createDraft([
            'branch_id' => $branch->id,
            'supplier_id' => $supplier->id,
            'currency_code' => 'PHP',
            'lines' => $lines,
        ], $actor, (string) Str::uuid());
    }

    private function receiveFullPo(GoodsReceiptService $grService, PurchaseOrder $po, Branch $branch, User $actor, \DateTimeInterface $receivedAt): void
    {
        $po->refresh()->load('lines');

        $lines = $po->lines->map(fn ($line) => [
            'purchase_order_line_id' => $line->id,
            'received_quantity' => $line->ordered_quantity,
            'accepted_quantity' => $line->ordered_quantity,
            'rejected_quantity' => '0.0000',
        ])->all();

        $receipt = $grService->createDraft([
            'purchase_order_id' => $po->id,
            'branch_id' => $branch->id,
            'received_at' => $receivedAt,
            'lines' => $lines,
        ], $actor, (string) Str::uuid());

        $grService->post($receipt, $actor, (string) Str::uuid());
    }

    private function receivePartialPo(GoodsReceiptService $grService, PurchaseOrder $po, Branch $branch, User $actor, \DateTimeInterface $receivedAt): void
    {
        $po->refresh()->load('lines');

        $lines = $po->lines->map(function ($line) {
            $accepted = bcmul($line->ordered_quantity, '0.4', 4);

            return [
                'purchase_order_line_id' => $line->id,
                'received_quantity' => $accepted,
                'accepted_quantity' => $accepted,
                'rejected_quantity' => '0.0000',
            ];
        })->all();

        $receipt = $grService->createDraft([
            'purchase_order_id' => $po->id,
            'branch_id' => $branch->id,
            'received_at' => $receivedAt,
            'lines' => $lines,
        ], $actor, (string) Str::uuid());

        $grService->post($receipt, $actor, (string) Str::uuid());
    }

    /**
     * @param  array<string, Product>  $products
     */
    private function seedInventoryAdjustments(Branch $branch, array $products, User $manager, User $owner): void
    {
        $adjService = app(InventoryAdjustmentService::class);

        // Posted: physical count found extra stock not yet logged.
        $adj1 = $adjService->createDraft([
            'branch_id' => $branch->id,
            'reason_code' => 'stock_count_correction',
            'reason_note' => 'Quarterly physical count found additional units not yet logged.',
            'effective_at' => now()->subDays(10),
            'lines' => [[
                'product_id' => $products['SHX-FLT-001']->id,
                'quantity_delta' => '8.0000',
                'unit_cost' => '150.0000',
            ]],
        ], $manager, (string) Str::uuid());
        $adjService->approve($adj1, $owner, (string) Str::uuid());
        $adjService->post($adj1, $manager, (string) Str::uuid());

        // Posted, then reversed: a damage write-off later found to be a
        // miscount rather than actual damage.
        $adj2 = $adjService->createDraft([
            'branch_id' => $branch->id,
            'reason_code' => 'damage',
            'reason_note' => 'Water damage during storage relocation.',
            'effective_at' => now()->subDays(8),
            'lines' => [[
                'product_id' => $products['SHX-FLT-002']->id,
                'quantity_delta' => '-6.0000',
                'unit_cost' => '230.0000',
            ]],
        ], $manager, (string) Str::uuid());
        $adjService->approve($adj2, $owner, (string) Str::uuid());
        $adjService->post($adj2, $manager, (string) Str::uuid());
        $adjService->reverse($adj2, 'Recount confirmed the units were misplaced, not damaged.', $owner, (string) Str::uuid());
    }

    /**
     * @param  array<string, Product>  $products
     */
    private function seedSales(Branch $branch, array $products, User $staffOne, User $staffTwo, UnitOfMeasure $unit): void
    {
        $saleService = app(SaleService::class);
        $cashiers = [$staffOne, $staffTwo];

        $pool = ['SHX-FLT-001', 'SHX-FLT-002', 'SHX-FLT-004'];
        // Starting balances match PO 6 plus the net effect of the two demo
        // adjustments above (adj2's -6 is undone by its own reversal).
        $remaining = ['SHX-FLT-001' => '258.0000', 'SHX-FLT-002' => '180.0000', 'SHX-FLT-004' => '200.0000'];
        $floor = ['SHX-FLT-001' => '50.0000', 'SHX-FLT-002' => '40.0000', 'SHX-FLT-004' => '50.0000'];

        $saleIndex = 0;
        for ($daysAgo = 40; $daysAgo >= 1; $daysAgo--) {
            $salesToday = random_int(0, 2);
            for ($i = 0; $i < $salesToday; $i++) {
                $sku = $pool[array_rand($pool)];
                $headroom = bcsub($remaining[$sku], $floor[$sku], 4);
                if (bccomp($headroom, '1', 4) < 0) {
                    continue;
                }

                $quantity = (string) min(3, random_int(1, 3), (int) $headroom);
                $soldAt = now()->subDays($daysAgo)->setTime(random_int(9, 17), random_int(0, 59));

                $this->recordSale($saleService, $branch, $products[$sku], $unit, $quantity, $cashiers[$saleIndex % 2], $soldAt, ++$saleIndex);
                $remaining[$sku] = bcsub($remaining[$sku], $quantity, 4);
            }
        }

        // A couple of sales dated today, so the dashboard's "Sales today" KPI has data.
        foreach (['SHX-FLT-001', 'SHX-FLT-004'] as $sku) {
            $headroom = bcsub($remaining[$sku], $floor[$sku], 4);
            if (bccomp($headroom, '1', 4) < 0) {
                continue;
            }

            $quantity = '2.0000';
            $soldAt = now()->subHours(random_int(1, 6));
            $this->recordSale($saleService, $branch, $products[$sku], $unit, $quantity, $cashiers[$saleIndex % 2], $soldAt, ++$saleIndex);
            $remaining[$sku] = bcsub($remaining[$sku], $quantity, 4);
        }
    }

    private function recordSale(SaleService $saleService, Branch $branch, Product $product, UnitOfMeasure $unit, string $quantity, User $cashier, \DateTimeInterface $soldAt, int $saleIndex): void
    {
        $unitPrice = (string) $product->selling_price;
        $gross = bcmul($quantity, $unitPrice, 4);
        $taxAmount = bcdiv(bcmul($gross, '12', 6), '100', 4);
        $total = bcadd($gross, $taxAmount, 4);

        $saleService->finalize([
            'branch_id' => $branch->id,
            'currency_code' => 'PHP',
            'sold_at' => $soldAt,
            'idempotency_key' => 'SEED-SALE-'.$saleIndex,
            'lines' => [[
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'quantity' => $quantity,
            ]],
            'payments' => [[
                'payment_method' => 'cash',
                'amount' => $total,
            ]],
        ], $cashier, (string) Str::uuid());
    }

    /**
     * @param  array<string, Product>  $products
     */
    private function seedReorderPolicies(Branch $branch, array $products, User $manager): void
    {
        // Only the products that were actually received get a reorder
        // policy, so the demo shows a focused, deliberate mix of healthy
        // and low-stock items rather than flagging the entire catalog.
        $definitions = [
            'SHX-FLT-001' => '25.0000',
            'SHX-FLT-002' => '25.0000',
            'SHX-FLT-003' => '30.0000',
            'SHX-FLT-004' => '25.0000',
            'SHX-DSP-001' => '15.0000',
            'SHX-DSP-002' => '10.0000',
        ];

        foreach ($definitions as $sku => $rop) {
            $product = $products[$sku];

            ReorderPolicy::query()->updateOrCreate(
                ['branch_id' => $branch->id, 'product_id' => $product->id],
                [
                    'safety_stock_quantity' => '10.0000',
                    'safety_stock_basis' => 'policy_minimum',
                    'reorder_point_quantity' => $rop,
                    'rop_calculated_at' => now(),
                    'is_active' => true,
                    'row_version' => 1,
                    'created_by_user_id' => $manager->id,
                    'updated_by_user_id' => $manager->id,
                ],
            );
        }
    }

    private function runForecast(Branch $branch, User $owner): void
    {
        app(SmaForecastService::class)->createRun([
            'branch_id' => $branch->id,
            'period_grain' => 'daily',
            'window_periods' => 14,
            'history_start_date' => now()->subDays(15)->toDateString(),
            'history_end_date' => now()->subDays(2)->toDateString(),
        ], $owner);
    }
}
