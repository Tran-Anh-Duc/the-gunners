<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\InventoryOpening;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithBusinessApi;
use Tests\TestCase;

class InventoryOpeningModuleTest extends TestCase
{
    use InteractsWithBusinessApi;
    use RefreshDatabase;

    protected Business $business;

    protected User $owner;

    protected Unit $unit;

    protected Warehouse $warehouse;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disableJwtMiddleware();

        $this->business = Business::query()->create([
            'code' => 'inventory-opening-test',
            'name' => 'Inventory Opening Test',
            'email' => 'owner@inventory-opening.local',
            'status' => 'active',
            'currency_code' => 'VND',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $this->owner = User::query()->create([
            'name' => 'Owner Inventory Opening',
            'email' => 'owner-inventory-opening@test.local',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->attachUserToBusiness($this->owner, $this->business->id);
        $this->actingAsBusinessUser($this->owner);

        $this->unit = Unit::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Cai',
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Kho test',
            'is_active' => true,
        ]);

        $this->product = Product::query()->create([
            'business_id' => $this->business->id,
            'unit_id' => $this->unit->id,
            'sku' => 'SKU-IO-001',
            'name' => 'San pham test',
            'product_type' => 'simple',
            'track_inventory' => true,
            'cost_price' => 10000,
            'sale_price' => 15000,
            'is_active' => true,
        ]);
    }

    public function test_store_creates_inventory_opening_successfully_with_valid_payload(): void
    {
        $response = $this->postJson('/api/inventory-openings', $this->baseStorePayload());

        $response->assertOk()
            ->assertJsonPath('data.business_id', $this->business->id)
            ->assertJsonPath('data.warehouse_id', $this->warehouse->id)
            ->assertJsonPath('data.product_id', $this->product->id);

        $this->assertDatabaseHas('inventory_openings', [
            'business_id' => $this->business->id,
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'unit_id' => $this->unit->id,
            'unit_name' => $this->unit->name,
            'quantity' => 2,
            'unit_cost' => 100,
            'total_cost' => 200,
            'created_by' => $this->owner->id,
            'updated_by' => null,
        ]);
    }

    public function test_store_rejects_duplicate_opening_for_same_business_warehouse_product(): void
    {
        $payload = $this->baseStorePayload();
        $this->postJson('/api/inventory-openings', $payload)->assertOk();

        $this->postJson('/api/inventory-openings', $payload)
            ->assertStatus(422)
            ->assertJsonPath('code', 'error_failed');
    }

    public function test_store_rejects_when_confirmed_warehouse_document_exists_for_same_business_warehouse_product(): void
    {
        $this->createWarehouseDocumentWithSingleDetail(
            business: $this->business,
            warehouse: $this->warehouse,
            product: $this->product,
            unit: $this->unit,
            user: $this->owner,
            status: 'confirmed',
            documentDate: '2026-04-20',
        );

        $response = $this->postJson('/api/inventory-openings', $this->baseStorePayload());

        $response->assertStatus(422);
        $this->assertDatabaseCount('inventory_openings', 0);
    }

    public function test_store_allows_when_warehouse_document_is_draft(): void
    {
        $this->createWarehouseDocumentWithSingleDetail(
            business: $this->business,
            warehouse: $this->warehouse,
            product: $this->product,
            unit: $this->unit,
            user: $this->owner,
            status: 'draft',
            documentDate: '2026-04-20',
        );

        $this->postJson('/api/inventory-openings', $this->baseStorePayload())
            ->assertOk();
    }

    public function test_store_allows_when_warehouse_document_is_cancelled(): void
    {
        $this->createWarehouseDocumentWithSingleDetail(
            business: $this->business,
            warehouse: $this->warehouse,
            product: $this->product,
            unit: $this->unit,
            user: $this->owner,
            status: 'cancelled',
            documentDate: '2026-04-20',
        );

        $this->postJson('/api/inventory-openings', $this->baseStorePayload())
            ->assertOk();
    }

    public function test_update_recalculates_total_cost_sets_updated_by_and_keeps_created_by_when_no_related_confirmed_document(): void
    {
        $opening = $this->createInventoryOpening();

        $this->putJson("/api/inventory-openings/{$opening->id}", [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'unit_id' => $this->unit->id,
            'unit_name' => $this->unit->name,
            'opening_date' => '2026-04-23',
            'quantity' => 3,
            'unit_cost' => 150,
            'note' => 'Cap nhat',
        ])->assertOk();

        $this->assertDatabaseHas('inventory_openings', [
            'id' => $opening->id,
            'quantity' => 3,
            'unit_cost' => 150,
            'total_cost' => 450,
            'created_by' => $this->owner->id,
            'updated_by' => $this->owner->id,
            'note' => 'Cap nhat',
        ]);
    }

    public function test_update_rejects_when_confirmed_warehouse_document_exists_for_same_business_warehouse_product(): void
    {
        $opening = $this->createInventoryOpening();

        $this->createWarehouseDocumentWithSingleDetail(
            business: $this->business,
            warehouse: $this->warehouse,
            product: $this->product,
            unit: $this->unit,
            user: $this->owner,
            status: 'confirmed',
            documentDate: '2026-04-24',
        );

        $response = $this->putJson("/api/inventory-openings/{$opening->id}", [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'unit_id' => $this->unit->id,
            'unit_name' => $this->unit->name,
            'opening_date' => '2026-04-23',
            'quantity' => 4,
            'unit_cost' => 100,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('inventory_openings', [
            'id' => $opening->id,
            'quantity' => 2,
            'unit_cost' => 100,
            'total_cost' => 200,
            'updated_by' => null,
        ]);
    }

    public function test_update_is_not_blocked_when_confirmed_document_is_for_different_product(): void
    {
        $opening = $this->createInventoryOpening();
        $anotherProduct = $this->createProduct('SKU-IO-002', 'San pham khac');

        $this->createWarehouseDocumentWithSingleDetail(
            business: $this->business,
            warehouse: $this->warehouse,
            product: $anotherProduct,
            unit: $this->unit,
            user: $this->owner,
            status: 'confirmed',
            documentDate: '2026-04-24',
        );

        $this->putJson("/api/inventory-openings/{$opening->id}", [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'unit_id' => $this->unit->id,
            'unit_name' => $this->unit->name,
            'opening_date' => '2026-04-23',
            'quantity' => 5,
            'unit_cost' => 100,
        ])->assertOk();
    }

    public function test_update_is_not_blocked_when_confirmed_document_is_for_different_warehouse(): void
    {
        $opening = $this->createInventoryOpening();
        $anotherWarehouse = $this->createWarehouse('Kho khac');

        $this->createWarehouseDocumentWithSingleDetail(
            business: $this->business,
            warehouse: $anotherWarehouse,
            product: $this->product,
            unit: $this->unit,
            user: $this->owner,
            status: 'confirmed',
            documentDate: '2026-04-24',
        );

        $this->putJson("/api/inventory-openings/{$opening->id}", [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'unit_id' => $this->unit->id,
            'unit_name' => $this->unit->name,
            'opening_date' => '2026-04-23',
            'quantity' => 5,
            'unit_cost' => 100,
        ])->assertOk();
    }

    public function test_update_is_not_blocked_when_confirmed_document_is_for_different_business(): void
    {
        $opening = $this->createInventoryOpening();

        $foreignBusiness = $this->createForeignBusinessBundle();
        $this->createWarehouseDocumentWithSingleDetail(
            business: $foreignBusiness['business'],
            warehouse: $foreignBusiness['warehouse'],
            product: $foreignBusiness['product'],
            unit: $foreignBusiness['unit'],
            user: $this->owner,
            status: 'confirmed',
            documentDate: '2026-04-24',
        );

        $this->putJson("/api/inventory-openings/{$opening->id}", [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'unit_id' => $this->unit->id,
            'unit_name' => $this->unit->name,
            'opening_date' => '2026-04-23',
            'quantity' => 5,
            'unit_cost' => 100,
        ])->assertOk();
    }

    public function test_store_validates_required_fields_including_product_name_and_unit_name(): void
    {
        $payload = $this->baseStorePayload();
        unset(
            $payload['warehouse_id'],
            $payload['product_id'],
            $payload['product_name'],
            $payload['unit_id'],
            $payload['unit_name'],
            $payload['opening_date'],
            $payload['quantity'],
        );

        $response = $this->postJson('/api/inventory-openings', $payload);

        $response->assertStatus(422)->assertJsonPath('code', 'error_failed');
        $errors = $response->json('data');

        $this->assertArrayHasKey('warehouse_id', $errors);
        $this->assertArrayHasKey('product_id', $errors);
        $this->assertArrayHasKey('product_name', $errors);
        $this->assertArrayHasKey('unit_id', $errors);
        $this->assertArrayHasKey('unit_name', $errors);
        $this->assertArrayHasKey('opening_date', $errors);
        $this->assertArrayHasKey('quantity', $errors);
    }

    public function test_store_validates_numeric_constraints_for_quantity_and_unit_cost(): void
    {
        $response = $this->postJson('/api/inventory-openings', $this->baseStorePayload([
            'quantity' => -1,
            'unit_cost' => -100,
        ]));

        $response->assertStatus(422)->assertJsonPath('code', 'error_failed');
        $errors = $response->json('data');

        $this->assertArrayHasKey('quantity', $errors);
        $this->assertArrayHasKey('unit_cost', $errors);
    }

    public function test_store_validates_exists_constraints_for_foreign_keys(): void
    {
        $response = $this->postJson('/api/inventory-openings', $this->baseStorePayload([
            'warehouse_id' => 999999,
            'product_id' => 999999,
            'unit_id' => 999999,
        ]));

        $response->assertStatus(422)->assertJsonPath('code', 'error_failed');
        $errors = $response->json('data');

        $this->assertArrayHasKey('warehouse_id', $errors);
        $this->assertArrayHasKey('product_id', $errors);
        $this->assertArrayHasKey('unit_id', $errors);
    }

    public function test_update_unique_check_allows_updating_same_record_without_conflict(): void
    {
        $opening = $this->createInventoryOpening();

        $this->putJson("/api/inventory-openings/{$opening->id}", [
            'warehouse_id' => $opening->warehouse_id,
            'product_id' => $opening->product_id,
            'product_name' => $opening->product_name,
            'unit_id' => $opening->unit_id,
            'unit_name' => $opening->unit_name,
            'opening_date' => '2026-04-24',
            'quantity' => 2,
            'unit_cost' => 100,
        ])->assertOk();
    }

    public function test_update_unique_check_rejects_when_switching_to_existing_opening_combination(): void
    {
        $firstOpening = $this->createInventoryOpening([
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
        ]);

        $anotherProduct = $this->createProduct('SKU-IO-003', 'San pham 3');
        $secondOpening = $this->createInventoryOpening([
            'product_id' => $anotherProduct->id,
            'product_name' => $anotherProduct->name,
        ]);

        $this->putJson("/api/inventory-openings/{$secondOpening->id}", [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'unit_id' => $this->unit->id,
            'unit_name' => $this->unit->name,
            'opening_date' => '2026-04-25',
            'quantity' => 1,
            'unit_cost' => 10,
        ])->assertStatus(422);

        $this->assertDatabaseHas('inventory_openings', ['id' => $firstOpening->id]);
    }

    public function test_index_supports_filters_and_orders_by_latest_id_first(): void
    {
        $warehouse2 = $this->createWarehouse('Kho 2');
        $product2 = $this->createProduct('SKU-IO-004', 'San pham 4');

        $first = $this->createInventoryOpening([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'opening_date' => '2026-04-10',
        ]);
        $second = $this->createInventoryOpening([
            'warehouse_id' => $warehouse2->id,
            'product_id' => $product2->id,
            'product_name' => $product2->name,
            'opening_date' => '2026-04-20',
        ]);
        $third = $this->createInventoryOpening([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $product2->id,
            'product_name' => $product2->name,
            'opening_date' => '2026-04-30',
        ]);

        $this->getJson('/api/inventory-openings')
            ->assertOk()
            ->assertJsonPath('data.items.0.id', $third->id)
            ->assertJsonPath('data.items.1.id', $second->id)
            ->assertJsonPath('data.items.2.id', $first->id);

        $this->getJson('/api/inventory-openings?warehouse_id=' . $this->warehouse->id)
            ->assertOk()
            ->assertJsonPath('data.total', 2);

        $this->getJson('/api/inventory-openings?product_id=' . $product2->id)
            ->assertOk()
            ->assertJsonPath('data.total', 2);

        $this->getJson('/api/inventory-openings?opening_date_from=2026-04-15&opening_date_to=2026-04-25')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $second->id);
    }

    public function test_show_returns_record_for_current_business(): void
    {
        $opening = $this->createInventoryOpening();

        $this->getJson('/api/inventory-openings/' . $opening->id)
            ->assertOk()
            ->assertJsonPath('data.id', $opening->id)
            ->assertJsonPath('data.business_id', $this->business->id);
    }

    public function test_show_does_not_return_record_of_other_business(): void
    {
        $foreignBusiness = $this->createForeignBusinessBundle();
        $foreignOpening = InventoryOpening::query()->create([
            'business_id' => $foreignBusiness['business']->id,
            'warehouse_id' => $foreignBusiness['warehouse']->id,
            'product_id' => $foreignBusiness['product']->id,
            'product_name' => $foreignBusiness['product']->name,
            'unit_id' => $foreignBusiness['unit']->id,
            'unit_name' => $foreignBusiness['unit']->name,
            'opening_date' => '2026-04-22',
            'quantity' => 1,
            'unit_cost' => 10,
            'total_cost' => 10,
            'created_by' => $this->owner->id,
        ]);

        $this->getJson('/api/inventory-openings/' . $foreignOpening->id)
            ->assertStatus(404);
    }

    public function test_show_response_contains_expected_transformer_fields(): void
    {
        $opening = $this->createInventoryOpening();

        $response = $this->getJson('/api/inventory-openings/' . $opening->id);
        $response->assertOk();

        $data = $response->json('data');
        $this->assertIsArray($data);

        foreach ([
            'id',
            'business_id',
            'warehouse_id',
            'product_id',
            'product_name',
            'unit_id',
            'unit_name',
            'opening_date',
            'quantity',
            'unit_cost',
            'total_cost',
            'note',
            'created_by',
            'updated_by',
            'created_at',
            'updated_at',
        ] as $key) {
            $this->assertArrayHasKey($key, $data);
        }
    }

    protected function baseStorePayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'unit_id' => $this->unit->id,
            'unit_name' => $this->unit->name,
            'opening_date' => '2026-04-22',
            'quantity' => 2,
            'unit_cost' => 100,
            'note' => 'Ton dau ky',
        ], $overrides);
    }

    protected function createInventoryOpening(array $overrides = []): InventoryOpening
    {
        $payload = $this->baseStorePayload($overrides);
        $payload['business_id'] = $this->business->id;
        $payload['total_cost'] = round(((float) $payload['quantity']) * ((float) $payload['unit_cost']), 2);
        $payload['created_by'] = $this->owner->id;
        $payload['updated_by'] = null;

        return InventoryOpening::query()->create($payload);
    }

    protected function createWarehouse(string $name): Warehouse
    {
        return Warehouse::query()->create([
            'business_id' => $this->business->id,
            'name' => $name,
            'is_active' => true,
        ]);
    }

    protected function createProduct(string $sku, string $name): Product
    {
        return Product::query()->create([
            'business_id' => $this->business->id,
            'unit_id' => $this->unit->id,
            'sku' => $sku,
            'name' => $name,
            'product_type' => 'simple',
            'track_inventory' => true,
            'cost_price' => 10000,
            'sale_price' => 15000,
            'is_active' => true,
        ]);
    }

    protected function createWarehouseDocumentWithSingleDetail(
        Business $business,
        Warehouse $warehouse,
        Product $product,
        Unit $unit,
        User $user,
        string $status,
        string $documentDate,
    ): WarehouseDocument {
        $document = WarehouseDocument::query()->create([
            'business_id' => $business->id,
            'document_type' => 'import',
            'warehouse_id' => $warehouse->id,
            'document_date' => $documentDate,
            'status' => $status,
            'reference_code' => null,
            'subtotal_amount' => 100,
            'tax_amount' => 0,
            'total_amount' => 100,
            'created_by' => $user->id,
        ]);

        $document->details()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_id' => $unit->id,
            'unit_name' => $unit->name,
            'quantity' => 1,
            'unit_price' => 100,
            'subtotal' => 100,
            'tax_rate' => 0,
            'tax_price' => 0,
            'total_price' => 100,
            'note' => null,
        ]);

        return $document;
    }

    /**
     * @return array{business: Business, warehouse: Warehouse, unit: Unit, product: Product}
     */
    protected function createForeignBusinessBundle(): array
    {
        $foreignBusiness = Business::query()->create([
            'code' => 'inventory-opening-foreign',
            'name' => 'Inventory Opening Foreign',
            'email' => 'owner@inventory-opening-foreign.local',
            'status' => 'active',
            'currency_code' => 'VND',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $foreignUnit = Unit::query()->create([
            'business_id' => $foreignBusiness->id,
            'name' => 'Foreign Unit',
            'is_active' => true,
        ]);

        $foreignWarehouse = Warehouse::query()->create([
            'business_id' => $foreignBusiness->id,
            'name' => 'Foreign Warehouse',
            'is_active' => true,
        ]);

        $foreignProduct = Product::query()->create([
            'business_id' => $foreignBusiness->id,
            'unit_id' => $foreignUnit->id,
            'sku' => 'SKU-IO-FOREIGN',
            'name' => 'Foreign Product',
            'product_type' => 'simple',
            'track_inventory' => true,
            'cost_price' => 8000,
            'sale_price' => 12000,
            'is_active' => true,
        ]);

        return [
            'business' => $foreignBusiness,
            'warehouse' => $foreignWarehouse,
            'unit' => $foreignUnit,
            'product' => $foreignProduct,
        ];
    }
}
