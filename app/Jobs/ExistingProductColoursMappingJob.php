<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ProductColourList;
use App\Services\Magento\ProductsServiceMagento;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mavinoo\Batch\Batch;

class ExistingProductColoursMappingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $offset;
    protected int $perPage;

    /**
     * Create a new job instance.
     */
    public function __construct($offset, $perPage)
    {
        $this->offset = $offset;
        $this->perPage = $perPage;
    }

    /**
     * Execute the job.
     */
    public function handle(ProductsServiceMagento $productsService, Batch $batch): void
    {
        $productColourList = ProductColourList::skip($this->offset)->take($this->perPage)->get();
        $productColourData = [];
        foreach ($productColourList as $productColour) {
            if ($productColour->appa_colours != '[]') {
                $appaColours = json_decode($productColour->appa_colours) ?? [];
                $productColourData[] = $productsService->processExistingProductsColourMapping($appaColours, $productColour->product_id);
            }
        }
        $mergedData = [];

        foreach ($productColourData as $item) {
            $id = $item['id'];

            // Initialize the merged item if it doesn't exist
            if (! isset($mergedData[$id])) {
                $mergedData[$id] = $item;

                continue;
            }

            // Make values unique by splitting, merging, and imploding
            $mergedData[$id]['appa_colours'] = implode(',', array_unique(explode(',', $mergedData[$id]['appa_colours'] . ',' . $item['appa_colours'])));
            $mergedData[$id]['magento_colours'] = implode(',', array_unique(explode(',', $mergedData[$id]['magento_colours'] . ',' . $item['magento_colours'])));
            $mergedData[$id]['magento_colours_ids'] = implode(',', array_unique(explode(',', $mergedData[$id]['magento_colours_ids'] . ',' . $item['magento_colours_ids'])));
        }
        // Convert the associative array back to indexed array
        $finalMergedData = array_values($mergedData);
        if (! empty($finalMergedData)) {
            $productInstance = new Product;
            $index = 'id';
            $batch->update($productInstance, $finalMergedData, $index);
        }
    }
}
