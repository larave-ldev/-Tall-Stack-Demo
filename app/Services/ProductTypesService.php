<?php

namespace App\Services;

use App\Models\ProductType;
use App\Models\ProductTypeSub;
use App\Promodata\ProductTypes;
use App\Promodata\PromodataClient;
use Exception;

class ProductTypesService
{
    private ProductTypes $productTypes;
    private array $subTypesDataToInsert = [];

    public function __construct(ProductTypes $productTypes)
    {
        $this->productTypes = $productTypes;
    }

    public function getProductTypes(): void
    {
        $page = 1;
        do {
            $result = $this->productTypes->get(['page' => $page]);
            self::processProductTypesData($result['data']);
            self::bulkInsertData();
            $page++;
        } while ($page <= $result['total_pages']);
    }

    private function processProductTypesData(array $data): void
    {
        foreach ($data as $row) {

            if ($this->shouldSkipProductType($row['id'])) {
                continue;
            }
            $productTypeData = $this->extractProductTypeData($row);
            $productType = ProductType::create($productTypeData);
            $this->processSubtypesData($row['subTypes'] ?? [], $productType->id);
        }

    }

    private function shouldSkipProductType(string $productTypeId): bool
    {
        return ProductType::where('promodata_id', $productTypeId)->exists();
    }

    private function extractProductTypeData(array $row): array
    {
        return [
            'type_id' => $row['id'] ?? null,
            'name' => $row['name'] ?? null,
            'promodata_id' => $row['id'] ?? null,
        ];
    }

    private function processSubtypesData(array $subTypes, $productTypeId): void
    {
        foreach ($subTypes as $subType) {
            $subType = (object) $subType;
            $this->subTypesDataToInsert[] = [
                'product_type_id' => $productTypeId,
                'sub_id' => $subType?->id ?? null,
                'name' => $subType?->name ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
    }

    private function bulkInsertData(): void
    {
        try {
            $this->insertSubTypesData();
        } catch (Exception $exception) {
            PromodataClient::createLog('ERROR', __LINE__, $exception->getMessage());
        }
    }

    private function insertSubTypesData(): void
    {
        if (! empty($this->subTypesDataToInsert)) {
            ProductTypeSub::insert($this->subTypesDataToInsert);
            $this->subTypesDataToInsert = []; // Clear the array after inserting
        }
    }
}
