<?php

namespace App\Services\Magento;

use App\Magento\Categories;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CategoriesService extends MagentoBaseService
{
    protected Categories $categories;

    public function __construct(Categories $categories)
    {
        parent::__construct();
        $this->categories = $categories;
    }

    public function createCategory(int $categoryId, string $table, array $categoriesData): void
    {
        $this->categories->ENDPOINT = config('services.magento.end_point');
        $result = $this->categories->createCategory($categoriesData);
        if (! empty($result) && isset($result['message'])) {
            Log::info('Error message:' . json_encode($result, true));
        } else {
            if ($categoryId && $table && ! empty($result) && ! isset($result['message'])) {
                DB::table($table)->where('id', $categoryId)->update(['magento_id' => $result['id'] ?? null]);
            }
        }
    }
}
