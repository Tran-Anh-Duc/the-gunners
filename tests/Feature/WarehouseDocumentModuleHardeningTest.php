<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithBusinessApi;
use Tests\TestCase;

class WarehouseDocumentModuleHardeningTest extends TestCase
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
            'code' => 'warehouse-doc-hardening',
            'name' => 'Warehouse Document Hardening',
            'email' => 'owner@warehouse-doc-hardening.local',
            'status' => 'active',
            'currency_code' => 'VND',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $this->owner = User::query()->create([
            'name' => 'Owner Warehouse Hardening',
            'email' => 'owner-warehouse-hardening@test.local',
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
            'name' => 'Kho hardening',
            'is_active' => true,
        ]);

        $this->product = Product::query()->create([
            'business_id' => $this->business->id,
            'unit_id' => $this->unit->id,
            'sku' => 'SKU-WD-H-001',
            'name' => 'San pham hardening',
            'product_type' => 'simple',
            'track_inventory' => true,
            'cost_price' => 10000,
            'sale_price' => 15000,
            'is_active' => true,
        ]);
    }

    public function test_store_rejects_foreign_warehouse_for_current_business(): void
    {
        $foreignBusiness = $this->createForeignBusinessBundle();

        $response = $this->postJson('/api/warehouse-documents', $this->baseStorePayload([
            'warehouse_id' => $foreignBusiness['warehouse']->id,
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('message', 'The selected value is invalid for the current business.');
    }

    public function test_store_rejects_foreign_product_in_details_for_current_business(): void
    {
        $foreignBusiness = $this->createForeignBusinessBundle();

        $response = $this->postJson('/api/warehouse-documents', $this->baseStorePayload([
            'details' => [[
                'product_id' => $foreignBusiness['product']->id,
                'product_name' => $foreignBusiness['product']->name,
                'unit_id' => $this->unit->id,
                'unit_name' => $this->unit->name,
                'quantity' => 2,
                'unit_price' => 100,
                'tax_rate' => 10,
            ]],
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('message', 'The selected value is invalid for the current business.');
    }

    public function test_store_rejects_foreign_unit_in_details_for_current_business(): void
    {
        $foreignBusiness = $this->createForeignBusinessBundle();

        $response = $this->postJson('/api/warehouse-documents', $this->baseStorePayload([
            'details' => [[
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'unit_id' => $foreignBusiness['unit']->id,
                'unit_name' => $foreignBusiness['unit']->name,
                'quantity' => 2,
                'unit_price' => 100,
                'tax_rate' => 10,
            ]],
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('message', 'The selected value is invalid for the current business.');
    }

    public function test_update_to_confirmed_sets_approver_and_approved_at(): void
    {
        $documentId = $this->createWarehouseDocument([
            'status' => 'draft',
        ]);

        $this->putJson("/api/warehouse-documents/{$documentId}", [
            'status' => 'confirmed',
        ])->assertOk();

        $this->assertDatabaseHas('warehouse_documents', [
            'id' => $documentId,
            'status' => 'confirmed',
            'approved_by' => $this->owner->id,
            'updated_by' => $this->owner->id,
        ]);

        $approvedAt = \App\Models\WarehouseDocument::query()->whereKey($documentId)->value('approved_at');
        $this->assertNotNull($approvedAt);
    }

    public function test_update_to_cancelled_clears_approval_fields(): void
    {
        $documentId = $this->createWarehouseDocument([
            'status' => 'confirmed',
        ]);

        $this->putJson("/api/warehouse-documents/{$documentId}", [
            'status' => 'cancelled',
        ])->assertOk();

        $this->assertDatabaseHas('warehouse_documents', [
            'id' => $documentId,
            'status' => 'cancelled',
            'approved_by' => null,
        ]);

        $approvedAt = \App\Models\WarehouseDocument::query()->whereKey($documentId)->value('approved_at');
        $this->assertNull($approvedAt);
    }

    public function test_update_with_empty_details_explicitly_clears_rows_and_totals(): void
    {
        $documentId = $this->createWarehouseDocument();
        $this->assertDatabaseCount('warehouse_document_details', 1);

        $this->putJson("/api/warehouse-documents/{$documentId}", [
            'details' => [],
        ])->assertOk();

        $this->assertDatabaseCount('warehouse_document_details', 0);

        $this->assertDatabaseHas('warehouse_documents', [
            'id' => $documentId,
            'subtotal_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 0,
        ]);
    }

    protected function createWarehouseDocument(array $overrides = []): int
    {
        $response = $this->postJson('/api/warehouse-documents', $this->baseStorePayload($overrides));

        $response->assertOk();

        return (int) $response->json('data.id');
    }

    protected function baseStorePayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'document_type' => 'import',
            'warehouse_id' => $this->warehouse->id,
            'document_date' => '2026-04-22',
            'status' => 'confirmed',
            'reference_code' => 'REF-HARDENING',
            'details' => [[
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'unit_id' => $this->unit->id,
                'unit_name' => $this->unit->name,
                'quantity' => 2,
                'unit_price' => 100,
                'tax_rate' => 10,
            ]],
        ], $overrides);
    }

    /**
     * @return array{business: Business, warehouse: Warehouse, unit: Unit, product: Product}
     */
    protected function createForeignBusinessBundle(): array
    {
        $foreignBusiness = Business::query()->create([
            'code' => 'warehouse-doc-hardening-foreign',
            'name' => 'Warehouse Doc Hardening Foreign',
            'email' => 'owner@warehouse-doc-hardening-foreign.local',
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
            'name' => 'Kho foreign hardening',
            'is_active' => true,
        ]);

        $foreignProduct = Product::query()->create([
            'business_id' => $foreignBusiness->id,
            'unit_id' => $foreignUnit->id,
            'sku' => 'SKU-WD-H-FOREIGN',
            'name' => 'San pham foreign hardening',
            'product_type' => 'simple',
            'track_inventory' => true,
            'cost_price' => 9000,
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
