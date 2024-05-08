<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductVariantPrice;
use Illuminate\Console\Command;
use Mavinoo\Batch\Batch;

class UpdateMagentoPriceForExistingProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-magento-price-for-existing-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update magento price for existing products';

    /**
     * Execute the console command.
     */
    public function handle(Batch $batch)
    {
        Product::where('products.name', '!=', '')
            ->whereNull('magento_price')
            ->chunk(500, function ($products) use ($batch) {
                $bulkMagentoPriceUpdate = [];
                foreach ($products as $product) {
                    $lowestPrice = ProductVariantPrice::where('product_id', $product->id)->min('price');
                    $bulkMagentoPriceUpdate[] = ['id' => $product->id, 'magento_price' => $lowestPrice];
                }
                if (! empty($bulkMagentoPriceUpdate)) {
                    $productInstance = new Product;
                    $index = 'id';
                    $batch->update($productInstance, $bulkMagentoPriceUpdate, $index);
                }
            });
    }
}
