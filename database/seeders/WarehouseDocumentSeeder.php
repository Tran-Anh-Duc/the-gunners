<?php

namespace Database\Seeders;

use App\Support\NameSlug;
use App\Models\WarehouseDocument;
use App\Models\WarehouseDocumentDetail;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class WarehouseDocumentSeeder extends Seeder
{
    protected const BUSINESS_ID = 1;

    protected const CREATED_BY = 1;

    /**
     * @var int[]
     */
    protected array $warehouseIds = [1, 2, 3];

    /**
     * @var int[]
     */
    protected array $productIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

    /**
     * @var int[]
     */
    protected array $unitIds = [1, 2, 3];

    /**
     * @var string[]
     */
    protected array $documentTypes = [
        WarehouseDocument::TYPE_IMPORT,
        WarehouseDocument::TYPE_EXPORT,
    ];

    /**
     * @var string[]
     */
    protected array $statuses = [
        WarehouseDocument::STATUS_DRAFT,
        WarehouseDocument::STATUS_CONFIRMED,
    ];

    public function run(): void
    {
        $createdCount = 0;
        $nextSequence = $this->resolveNextSequence();

        DB::transaction(function () use (&$createdCount, &$nextSequence): void {
            $this->ensureRequiredRecords();

            for ($index = 0; $index < 10; $index++) {
                $documentType = $this->randomDocumentType();
                $documentCode = $this->generateDocumentCode($documentType, $nextSequence);
                $documentDate = $this->randomDocumentDate();
                $status = $this->randomStatus();
                $referenceCode = $this->randomReferenceCode();

                $document = WarehouseDocument::query()->create([
                    'business_id' => self::BUSINESS_ID,
                    'document_code' => $documentCode,
                    'document_type' => $documentType,
                    'warehouse_id' => $this->randomWarehouseId(),
                    'document_date' => $documentDate->toDateString(),
                    'status' => $status,
                    'reference_code' => $referenceCode,
                    'subtotal_amount' => 0,
                    'tax_amount' => 0,
                    'total_amount' => 0,
                    'note' => 'Seeder document',
                    'created_by' => self::CREATED_BY,
                    'updated_by' => null,
                    'approved_by' => $status === WarehouseDocument::STATUS_CONFIRMED ? self::CREATED_BY : null,
                    'approved_at' => $status === WarehouseDocument::STATUS_CONFIRMED ? $documentDate->copy()->endOfDay() : null,
                ]);

                $details = $this->generateDetailRows($document->id);

                WarehouseDocumentDetail::query()->insert($details);

                $document->update([
                    'subtotal_amount' => collect($details)->sum('subtotal'),
                    'tax_amount' => collect($details)->sum('tax_price'),
                    'total_amount' => collect($details)->sum('total_price'),
                ]);

                $createdCount++;
                $nextSequence++;
            }
        });

        if ($this->command) {
            $this->command->info("Created {$createdCount} warehouse documents.");

            return;
        }

        echo "Created {$createdCount} warehouse documents.".PHP_EOL;
    }

    protected function ensureRequiredRecords(): void
    {
        $now = now();

        if (! DB::table('businesses')->where('id', self::BUSINESS_ID)->exists()) {
            DB::table('businesses')->insert([
                'id' => self::BUSINESS_ID,
                'code' => 'business-001',
                'name' => 'Business 1',
                'name_slug' => NameSlug::from('Business 1'),
                'phone' => '0900000001',
                'email' => 'business1@example.com',
                'address' => 'Seeder business',
                'plan_code' => 'starter',
                'status' => 'active',
                'currency_code' => 'VND',
                'timezone' => 'Asia/Ho_Chi_Minh',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);
        }

        if (! DB::table('users')->where('id', self::CREATED_BY)->exists()) {
            DB::table('users')->insert([
                'id' => self::CREATED_BY,
                'name' => 'Seeder User 1',
                'name_slug' => NameSlug::from('Seeder User 1'),
                'email' => 'seeder-user-1@example.com',
                'password' => Hash::make('password'),
                'phone' => '0900000002',
                'avatar' => null,
                'is_active' => true,
                'last_login_at' => null,
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);
        }

        if (! DB::table('business_users')->where('business_id', self::BUSINESS_ID)->where('user_id', self::CREATED_BY)->exists()) {
            DB::table('business_users')->insert([
                'business_id' => self::BUSINESS_ID,
                'user_id' => self::CREATED_BY,
                'role' => 'owner',
                'status' => 'active',
                'is_owner' => true,
                'joined_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($this->unitIds as $unitId) {
            if (DB::table('units')->where('id', $unitId)->exists()) {
                continue;
            }

            DB::table('units')->insert([
                'id' => $unitId,
                'business_id' => self::BUSINESS_ID,
                'code' => 'UNIT-'.$unitId,
                'name' => 'Unit '.$unitId,
                'name_slug' => NameSlug::from('Unit '.$unitId),
                'description' => 'Seeder unit '.$unitId,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);
        }

        foreach ($this->warehouseIds as $warehouseId) {
            if (DB::table('warehouses')->where('id', $warehouseId)->exists()) {
                continue;
            }

            DB::table('warehouses')->insert([
                'id' => $warehouseId,
                'business_id' => self::BUSINESS_ID,
                'code' => 'WH-'.$warehouseId,
                'name' => 'Warehouse '.$warehouseId,
                'name_slug' => NameSlug::from('Warehouse '.$warehouseId),
                'address' => 'Seeder warehouse '.$warehouseId,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);
        }

        $categoryId = DB::table('categories')
            ->where('business_id', self::BUSINESS_ID)
            ->orderBy('id')
            ->value('id');

        foreach ($this->productIds as $productId) {
            if (DB::table('products')->where('id', $productId)->exists()) {
                continue;
            }

            $unitId = $this->unitIds[($productId - 1) % count($this->unitIds)];

            DB::table('products')->insert([
                'id' => $productId,
                'business_id' => self::BUSINESS_ID,
                'unit_id' => $unitId,
                'category_id' => $categoryId,
                'sku' => 'SKU-'.$productId,
                'name' => 'Product '.$productId,
                'name_slug' => NameSlug::from('Product '.$productId),
                'barcode' => '8938503'.str_pad((string) $productId, 6, '0', STR_PAD_LEFT),
                'product_type' => 'simple',
                'track_inventory' => true,
                'cost_price' => 10,
                'sale_price' => 100,
                'is_active' => true,
                'description' => 'Seeder product '.$productId,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);
        }
    }

    protected function resolveNextSequence(): int
    {
        $maxSequence = WarehouseDocument::query()
            ->where('business_id', self::BUSINESS_ID)
            ->where(function ($query): void {
                $query->where('document_code', 'like', 'WH-IMPORT-%')
                    ->orWhere('document_code', 'like', 'WH-EXPORT-%');
            })
            ->get(['document_code'])
            ->map(function (WarehouseDocument $document): int {
                if (! preg_match('/(\d+)$/', (string) $document->document_code, $matches)) {
                    return 0;
                }

                return (int) $matches[1];
            })
            ->max();

        return ((int) $maxSequence) + 1;
    }

    protected function generateDocumentCode(string $documentType, int $sequence): string
    {
        $prefix = $documentType === WarehouseDocument::TYPE_IMPORT ? 'WH-IMPORT-' : 'WH-EXPORT-';

        return $prefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    protected function generateDetailRows(int $documentId): array
    {
        $detailCount = random_int(2, 4);
        $selectedProductIds = collect($this->productIds)
            ->shuffle()
            ->take($detailCount)
            ->values();

        $now = now();

        return $selectedProductIds->map(function (int $productId) use ($documentId, $now): array {
            $unitId = $this->randomUnitId();
            $quantity = (float) random_int(1, 20);
            $unitPrice = $this->randomDecimal(10, 100);
            $taxRate = (float) collect([0, 5, 10])->random();
            $subtotal = round($quantity * $unitPrice, 2);
            $taxPrice = round($subtotal * ($taxRate / 100), 2);
            $totalPrice = round($subtotal + $taxPrice, 2);

            return [
                'warehouse_document_id' => $documentId,
                'product_id' => $productId,
                'product_name' => 'Product '.$productId,
                'unit_id' => $unitId,
                'unit_name' => 'Unit '.$unitId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_price' => $taxPrice,
                'total_price' => $totalPrice,
                'note' => 'Seeder detail',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();
    }

    protected function randomWarehouseId(): int
    {
        return $this->warehouseIds[array_rand($this->warehouseIds)];
    }

    protected function randomUnitId(): int
    {
        return $this->unitIds[array_rand($this->unitIds)];
    }

    protected function randomDocumentType(): string
    {
        return $this->documentTypes[array_rand($this->documentTypes)];
    }

    protected function randomStatus(): string
    {
        return $this->statuses[array_rand($this->statuses)];
    }

    protected function randomReferenceCode(): ?string
    {
        if (random_int(0, 1) === 0) {
            return null;
        }

        return 'REF-'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
    }

    protected function randomDocumentDate(): Carbon
    {
        return now()->subDays(random_int(0, 29));
    }

    protected function randomDecimal(int $minWhole, int $maxWhole): float
    {
        return round(random_int($minWhole * 100, $maxWhole * 100) / 100, 2);
    }
}
