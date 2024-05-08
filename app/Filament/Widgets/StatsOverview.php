<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use App\Models\Supplier;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        // Supplier Counts
        $supplierCounts = Supplier::getSupplierCountsBySyncStatus();
        $enabledSupplierCount = $supplierCounts[true] ?? 0;
        $disabledSupplierCount = $supplierCounts[false] ?? 0;
        $totalSupplier = $enabledSupplierCount + $disabledSupplierCount;

        // Product Counts
        $productCounts = Supplier::getProductCountsBySyncStatus();
        $enabledSupplierProductCount = $productCounts[true] ?? 0;
        $disabledSupplierProductCount = $productCounts[false] ?? 0;
        $totalProduct = $enabledSupplierProductCount + $disabledSupplierProductCount;

        // Magento Product Counts
        $magentoProductCounts = Product::getProductCountsByMagentoStatus();
        $enabledMagentoProductCount = $magentoProductCounts['enabled_count'] ?? 0;
        $disabledMagentoProductCount = $magentoProductCounts['disabled_count'] ?? 0;
        $totalEnabledDisabledCount = $magentoProductCounts['total_enabled_disabled_count'] ?? 0;

        return [
            Stat::make('Total Products', $totalProduct)
                ->description('Enable '.$enabledSupplierProductCount.', Disable '.$disabledSupplierProductCount)
                ->color('success')
                ->url(ProductResource::getUrl()),
        ];
    }
}
