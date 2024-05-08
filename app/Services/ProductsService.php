<?php

namespace App\Services;

use App\Jobs\PromodataInsertProductData;
use App\Jobs\PromodataUpdateProductData;
use App\Models\MagentoColour;
use App\Models\Product;
use App\Models\ProductAddition;
use App\Models\ProductAdditionPrice;
use App\Models\ProductColourList;
use App\Models\ProductColourMapper;
use App\Models\ProductColourSupplierText;
use App\Models\ProductExtraInfo;
use App\Models\ProductMedia;
use App\Models\ProductType;
use App\Models\ProductTypeSub;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Supplier;
use App\Promodata\Products;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use JetBrains\PhpStorm\NoReturn;
use Mavinoo\Batch\Batch;

use function App\Helpers\cleanJsonString;
use function App\Helpers\cleanString;

class ProductsService extends Products
{
    private const HTTP_TIMEOUT = 60;
    private array $extraInfoDataToInsert = [];
    private array $mediaDataToInsert = [];
    private array $coloursListDataToInsert = [];
    private array $coloursSupplierTextDataToInsert = [];
    private array $variantPricesDataToInsert = [];
    private array $additionPricesDataToInsert = [];
    private Products $products;
    private Batch $batch;
    private int $chunkSize;

    private CommonService $commonService;

    private array $variantPriceData = [];

    public function __construct(Products $products, Batch $batch, CommonService $commonService)
    {
        parent::__construct();
        $this->products = $products;
        $this->batch = $batch;
        $this->chunkSize = 5000; // Adjust this based on your server's capabilities

        $this->commonService = $commonService;
    }

    /**
     * @throws Exception
     */
    #[NoReturn]
    public function getProducts(): void
    {
        $page = 1;
        $itemsPerPage = 500;
        do {
            PromodataInsertProductData::dispatch($page, $itemsPerPage);
            $page++;
        } while ($page <= $this->getTotalPages());

    }

    /**
     * @throws Exception
     */
    public function updateProducts(int $sinceDaysAgo): void
    {
        $page = 1;
        $ItemsPerPage = 250;
        do {
            PromodataUpdateProductData::dispatch($page, $ItemsPerPage, $sinceDaysAgo);
            $page++;
        } while ($page <= $this->getTotalPagesOfUpdate($sinceDaysAgo));
    }

    public function processBulkUpdateProductsData(array $data): array
    {
        $productBulkUpdateData = [];
        $productBulkInsertData = [];
        $promodataIds = [];
        foreach ($data as $row) {
            $meta = (object) ($row['meta'] ?? []);
            $supplier = (object) ($row['supplier'] ?? []);
            $overview = (object) ($row['overview'] ?? []);
            $product_product = (object) ($row['product'] ?? []);
            $product_categorisation = (object) ($product_product?->categorisation ?? []);
            $productColours = (object) ($product_product?->colours ?? []);

            // Checking for does not exist products
            if (Product::where('promodata_id', $meta?->id)->doesntExist()) {
                $productBulkInsertData[] = $row;
            } else {
                $extractProductData = $this->extractProductData($meta, $supplier, $overview, $product_product, $product_categorisation, $productColours, 'UPDATE');
                if (! empty($extractProductData)) {
                    $productBulkUpdateData[] = $extractProductData;
                    $promodataIds[] = $meta?->id;
                }
            }
        }

        if (! empty($productBulkUpdateData)) {
            $productInstance = new Product;
            $index = 'promodata_id';
            $this->batch->update($productInstance, $productBulkUpdateData, $index);
        }

        // Retrieve the IDs of the inserted products
        $productIds = Product::whereIn('promodata_id', $promodataIds)->pluck('id')->toArray();

        // Insert new products
        if (! empty($productBulkInsertData)) {
            $data = $this->processProductsBulkInsert($productBulkInsertData);
            $this->processProductsData($data);
            $this->bulkInsertData();
        }

        if (empty($productIds)) {
            return [];
        }

        // Mapping Ids with actual array of product data
        return array_map(function ($id, $data) {
            $data['product_id'] = $id;

            return $data;
        }, $productIds, $data);
    }

    public function processProductsColourMapping(array $colours, int $id): array
    {
        $appaColoursData = [];
        $magentoColoursData = [];
        $magentoColoursIdsData = [];
        foreach ($colours as $colour) {
            // Check color exist to product colour mapper
            $colourMapperData = $this->commonService->isColourExistToProductColourMapper($colour);

            if (! $colourMapperData) {
                // If it does not exist then create that colour to product colour mapper table
                $data = [
                    'product_colour' => $colour,
                ];
                ProductColourMapper::create($data);

                // Send notification
                $title = $colour . ' colour found from promo data, please map it';
                $notificationData = ['title' => $title];
                $this->commonService->sendFilamentNotification($notificationData);

                // Only add to appa_colour, due to not exist
                if (! in_array($colour, $appaColoursData)) {
                    $appaColoursData[] = $colour;
                }
            } else {
                // For existing color, check magento id and append to product table
                $magentoColourId = $colourMapperData->magento_colour_id ?? null;
                if ($magentoColourId) {
                    if (! in_array($colour, $appaColoursData)) {
                        $appaColoursData[] = $colour;
                    }
                    if (! in_array($colour, $magentoColoursData)) {
                        $magentoColoursData[] = $colour;
                    }
                    if (! in_array($magentoColourId, $magentoColoursIdsData)) {
                        $result = MagentoColour::where('id', $magentoColourId)->get();
                        $magentoColoursIdsData[] = $result[0]['magento_id'];
                    }
                } else {
                    if (! in_array($colour, $appaColoursData)) {
                        $appaColoursData[] = $colour;
                    }
                }
            }
        }

        return [
            'id' => $id,
            'appa_colours' => implode(',', $appaColoursData),
            'magento_colours' => implode(',', $magentoColoursData),
            'magento_colours_ids' => implode(',', $magentoColoursIdsData),
        ];
    }

    public function processProductsBulkInsert(array $data): array
    {
        $productInsertData = [];
        $promodataIds = [];
        foreach ($data as $row) {
            $meta = (object) ($row['meta'] ?? []);

            // Skip if product is existed
            if ($this->shouldSkipProduct($meta?->id)) {
                continue;
            }

            $supplier = (object) ($row['supplier'] ?? []);
            $overview = (object) ($row['overview'] ?? []);
            $product_product = (object) ($row['product'] ?? []);
            $product_categorisation = (object) ($product_product?->categorisation ?? []);
            $productColours = (object) ($product_product?->colours ?? []);

            $extractProductData = $this->extractProductData($meta, $supplier, $overview, $product_product, $product_categorisation, $productColours, 'INSERT');

            if (! empty($extractProductData)) {
                $productInsertData[] = $extractProductData;
                $promodataIds[] = $meta?->id;
            }
        }
        if (! empty($productInsertData)) {
            Product::insert($productInsertData);
        }

        // Retrieve the IDs of the inserted products
        $productIds = Product::whereIn('promodata_id', $promodataIds)->pluck('id')->toArray();

        if (empty($productIds)) {
            return [];
        }

        // Mapping Ids with actual array of product data
        return array_map(function ($id, $data) {
            $data['product_id'] = $id;

            return $data;
        }, $productIds, $data);
    }

    public function processProductsData(array $data): void
    {
        foreach ($data as $row) {
            $heroImage = $row['overview']['hero_image'] ?? '';
            $product_product = (object) ($row['product'] ?? []);
            $product_details = (object) ($product_product?->details ?? []);
            $product_colours = (object) ($product_product?->colours ?? []);
            $product_prices = (object) ($product_product?->prices ?? []);

            if (! $row['product_id']) {
                continue;
            }

            $this->processExtraInfoData($product_details, $row['product_id']);
            $this->processMediasData($heroImage, $product_product, $row['product_id']);
            $this->processColoursListData($product_colours, $row['product_id']);
            $this->processColoursSupplierTextData($product_colours, $row['product_id']);
            $this->processProductVariantsData($product_prices, $row['product_id']);

            // Update magento_price to products table
            $this->updateProductMagentoPrice($row['product_id']);
        }
    }

    public function bulkInsertData(): void
    {
        try {
            $this->insertExtraInfoData();
            $this->insertMediaData();
            $this->insertColoursListData();
            $this->insertColoursSupplierTextData();
            $this->insertProductVariantPricesData();
            $this->insertProductAdditionPricesData();
        } catch (Exception $exception) {
            $this->createLog('ERROR', __LINE__, $exception->getMessage());
        }
    }

    public function processUpdateProductsData(array $data): void
    {
        foreach ($data as $row) {
            $heroImage = $row['overview']['hero_image'] ?? '';
            $product_product = (object) ($row['product'] ?? []);
            $product_details = (object) ($product_product?->details ?? []);
            $product_colours = (object) ($product_product?->colours ?? []);
            $product_prices = (object) ($product_product?->prices ?? []);

            if (! $row['product_id']) {
                continue;
            }

            $this->processExtraInfoDataUpdate($product_details, $row['product_id']);
            $this->processMediasDataUpdate($heroImage, $product_product, $row['product_id']);
            $this->processColoursListDataUpdate($product_colours, $row['product_id']);
            $this->processColoursSupplierTextDataUpdate($product_colours, $row['product_id']);
            $this->processProductVariantsDataUpdate($product_prices, $row['product_id']);

            $this->updateProductMagentoPrice($row['product_id']);

        }
    }

    /**
     * Update magento_price to products table
     */
    protected function updateProductMagentoPrice(int $productId): void
    {
        if (! empty($this->variantPriceData)) {
            $prices = array_map(function ($data) {
                return $data['price'] ?? null;
            }, $this->variantPriceData);

            // Remove null values from the array before finding the minimum
            $filteredPrices = array_filter($prices);

            // Check if there are elements in the array before finding the minimum
            if (! empty($filteredPrices)) {
                $lowestPrice = min($filteredPrices);
                Product::whereId($productId)->update(['magento_price' => $lowestPrice]);
            } else {
                // Handle the case when all prices are null or the array is empty
                Product::whereId($productId)->update(['magento_price' => 0]);
            }

            $this->variantPriceData = [];
        }
    }

    /**
     * @throws Exception
     */
    private function getTotalPages(): int
    {
        $firstPageData = $this->products->get(['page' => 1]);

        return $firstPageData['total_pages'];

    }

    /**
     * @throws Exception
     */
    private function getTotalPagesOfUpdate(int $sinceDaysAgo): int
    {
        $firstPageData = $this->products->changed(['page' => 1, 'since_days_ago' => $sinceDaysAgo]);

        return $firstPageData['total_pages'];

    }

    private function extractProductData(
        object $meta,
        object $supplier,
        object $overview,
        object $product,
        object $product_categorisation,
        object $productColours,
        string $operation = 'UPDATE'
    ): array {
        $priceCurrencies = $meta->price_currencies ?? null;
        $display_prices = $overview->display_prices ?? null;
        $appa_attributes = $product_categorisation->appa_attributes ?? null;
        $appa_product_type = $product_categorisation->appa_product_type ?? null;

        $appaColours = '';
        $magentoColours = '';
        $magentoColoursIds = '';

        $productColoursData = $this->processColours($productColours);
        if (! empty($productColoursData[0])) {
            $appaColours = trim($productColoursData[0]['appa_colours'], ',') ?? null;
            $magentoColours = trim($productColoursData[0]['magento_colours'], ',') ?? null;
            $magentoColoursIds = trim($productColoursData[0]['magento_colours_ids'], ',') ?? null;
        }

        $heroImage = $overview->hero_image ?? null;

        if ($heroImage) {
            $heroImageInfo = pathinfo($heroImage);
            $heroImage = $heroImageInfo['dirname'] . '/' .
                $heroImageInfo['filename'] . '.jpg';
        }

        // If suppliers is not exist then return blank data of product insert
        if (! self::getSupplierId($supplier) || self::getSupplierId($supplier) === 0) {
            return [];
        }

        $insertArray = [
            'supplier_id' => self::getSupplierId($supplier),
            'product_type_id' => self::getProductTypeId($product_categorisation),
            'product_type_sub_id' => self::getProductTypeSubId($product_categorisation),
            'country' => $meta->country ?? null,
            'data_source' => $meta->data_source ?? null,
            'discontinued' => $meta->discontinued ?? null,
            'can_check_stock' => $meta->can_check_stock ?? null,
            'discontinued_at' => $meta->discontinued_at ?? null,
            'first_listed_at' => $meta->first_listed_at ?? null,
            'last_changed_at' => $meta->last_changed_at ?? null,
            'price_currencies' => json_encode($priceCurrencies, true),
            'prices_changed_at' => $meta->prices_changed_at ?? null,
            'discontinued_reason' => $meta->discontinued_reason ?? null,
            'source_data_changed_at' => $meta->source_data_changed_at ?? null,
            'name' => $overview->name ?? null,
            'code' => $overview->code ?? null,
            'hero_image' => $heroImage,
            'min_qty' => $overview->min_qty ?? null,
            'display_prices' => json_encode($display_prices, true),
            'description' => $product->description ?? null,
            'supplier_brand' => $product->supplier_brand ?? null,
            'supplier_label' => $product->supplier_label ?? null,
            'supplier_catalogue' => $product->supplier_catalogue ?? null,
            'supplier_website_page' => $product->supplier_website_page ?? null,
            'appa_attributes' => json_encode($appa_attributes, true),
            'appa_product_type' => json_encode($appa_product_type, true),
            'supplier_category' => $product_categorisation->supplier_category ?? null,
            'supplier_subcategory' => $product_categorisation->supplier_subcategory ?? null,
            'promodata_id' => $meta->id ?? null,
            'appa_colours' => $appaColours ?? null,
            'magento_colours' => $magentoColours ?? null,
            'magento_colours_ids' => $magentoColoursIds ?? null,
            //            'created_at' => now(),
        ];

        if ($operation === 'INSERT') {
            $insertArray['created_at'] = now();
            $insertArray['updated_at'] = now();
        } else {
            $insertArray['updated_at'] = now();
        }

        return $insertArray;
    }

    private function processColours(object $productColours): array
    {
        $productColourList = (object) $productColours->list ?? [];
        if (empty($productColourList)) {
            return [];
        }

        $mergedData = [];
        foreach ($productColourList as $list) {
            $list = (object) $list;
            if ($list && $list->appa_colours) {
                $id = 1;
                $item = $this->processProductsColourMapping($list->appa_colours, $id);

                // Initialize the merged item if it doesn't exist
                if (! isset($mergedData[$id])) {
                    $mergedData[$id] = $item;
                } else {
                    // Merge and make values unique
                    $mergedData[$id]['appa_colours'] = implode(',', array_unique(array_merge(
                        explode(',', $mergedData[$id]['appa_colours']),
                        explode(',', $item['appa_colours'])
                    )));

                    $mergedData[$id]['magento_colours'] = implode(',', array_unique(array_merge(
                        explode(',', $mergedData[$id]['magento_colours']),
                        explode(',', $item['magento_colours'])
                    )));

                    $mergedData[$id]['magento_colours_ids'] = implode(',', array_unique(array_merge(
                        explode(',', $mergedData[$id]['magento_colours_ids']),
                        explode(',', $item['magento_colours_ids'])
                    )));
                }
            }
        }

        return array_values($mergedData);
    }

    private function getSupplierId(object $supplier): int
    {
        if (Supplier::where('promodata_id', $supplier->supplier_id)->exists()) {
            return Supplier::select('id')->where('promodata_id', $supplier->supplier_id)->first()->id;
        } else {
            return 0;
        }
    }

    private function getProductTypeId(object $product_categorisation): ?int
    {
        $product_type = (object) $product_categorisation->product_type ?? [];
        if ($product_type) {
            $type_group_id = $product_type->type_group_id ?? null;
            if (ProductType::where('promodata_id', $type_group_id)->exists()) {
                return ProductType::select('id')->where('promodata_id', $type_group_id)->first()->id;
            }
        }

        return null;
    }

    private function getProductTypeSubId(object $product_categorisation): ?int
    {
        $product_type = (object) $product_categorisation->product_type ?? [];
        if ($product_type) {
            $type_id = $product_type->type_id ?? null;
            if (ProductTypeSub::where('sub_id', $type_id)->exists()) {
                return ProductTypeSub::select('id')->where('sub_id', $type_id)->first()->id;
            }
        }

        return null;
    }

    private function shouldSkipProduct(int $productId): bool
    {
        return Product::where('promodata_id', $productId)->exists();
    }

    private function processExtraInfoData(object $product_details, int $productId): void
    {

        foreach ($product_details as $detail) {
            $detail = (object) $detail ?? [];
            if ($detail) {
                $this->extraInfoDataToInsert[] = [
                    'product_id' => $productId,
                    'name' => $detail->name ?? null,
                    'detail' => $detail->detail ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
    }

    private function processMediasData(string $heroImage, object $product_product, int $productId): void
    {

        // For images
        $product_images = $product_product->images ?? [];
        if ($product_images) {
            foreach ($product_images as $image) {
                $changeImageUrl = null;
                if ($heroImage === $image) {
                    // skip same image of media
                    continue;
                }

                if ($image) {
                    $changeImageUrl = $this->overWriteImageExtension($image);
                }

                $this->mediaDataToInsert[] = [
                    'product_id' => $productId,
                    'type' => 'image',
                    'actual_url' => $changeImageUrl ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // For videos
        $product_videos = $product_product->videos ?? [];
        if ($product_videos) {
            foreach ($product_videos as $video) {
                $this->mediaDataToInsert[] = [
                    'product_id' => $productId,
                    'type' => 'video',
                    'actual_url' => $video,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // For Line arts
        $product_lineArt = $product_product->line_art ?? [];
        if ($product_lineArt) {
            foreach ($product_lineArt as $lineArt) {
                $this->mediaDataToInsert[] = [
                    'product_id' => $productId,
                    'type' => 'line-art',
                    'actual_url' => $lineArt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
    }

    private function overWriteImageExtension(string $image): string
    {
        $ImageInfo = pathinfo($image);

        return $ImageInfo['dirname'] . '/' .
            $ImageInfo['filename'] . '.jpg';
    }

    private function processColoursListData(object $product_colours, int $productId): void
    {
        if (! isset($product_colours->list) || ! is_array($product_colours->list)) {
            return;
        }

        foreach ($product_colours->list as $list) {
            if (! empty($list)) {
                $changeImageUrl = null;
                $list = (object) $list;
                $swatch = $list->swatch ?? [];
                $colours = $list->colours ?? [];
                $appa_colours = $list->appa_colours ?? [];

                $colours_json = json_encode($colours, true) ?? null;
                $colours = $colours_json ? str_replace('\n', '\\\\n', $colours_json) : null;

                $image = $list->image ?? null;
                if ($image) {
                    $changeImageUrl = $this->overWriteImageExtension($image);
                }

                $this->coloursListDataToInsert[] = [
                    'product_id' => $productId,
                    'for' => $list->for ?? null,
                    'name' => trim($list->name) ?? null,
                    'image' => $changeImageUrl ?? null,
                    'swatch' => json_encode($swatch, true),
                    'colours' => $colours,
                    'appa_colours' => json_encode($appa_colours, true),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
    }

    private function processColoursSupplierTextData(?object $product_colours, int $productId): void
    {
        $supplier_texts = $product_colours->supplier_text ?? [];
        foreach ($supplier_texts as $supplier_text) {
            $supplier_text = (object) $supplier_text ?? [];
            if ($supplier_text) {
                $this->coloursSupplierTextDataToInsert[] = [
                    'product_id' => $productId,
                    'name' => $supplier_text->name ?? null,
                    'detail' => $supplier_text->detail ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
    }

    private function processProductVariantsData(object $product_prices, int $productId): void
    {

        // Check if price_groups is set and is an array
        if (! isset($product_prices->price_groups) || ! is_array($product_prices->price_groups)) {
            return; // Exit early if price_groups is not set or not an array
        }

        $priceGroups = (object) $product_prices->price_groups ?? [];
        if ($priceGroups) {
            foreach ($priceGroups as $priceGroup) {
                $basePrice = (object) $priceGroup['base_price'] ?? [];

                if (! $basePrice?->key || $basePrice?->key == '') {
                    continue;
                }

                $tags = $base_price?->tags ?? null;
                $productVariantData = [
                    'product_id' => $productId,
                    'supplier_id' => self::getSupplierIdFromProduct($productId),
                    'key' => $basePrice?->key ?? null,
                    'free' => $basePrice?->free ?? null,
                    'tags' => json_encode($tags, true),
                    'type' => $basePrice?->type != '' ? $basePrice?->type : 'Add on',
                    'setup' => $basePrice?->setup ?? null,
                    'indent' => $basePrice?->indent ?? null,
                    'currency' => $basePrice?->currency ?? null,
                    'lead_time' => $basePrice?->lead_time ?? null,
                    'description' => $basePrice?->description ?? null,
                    'undecorated' => $basePrice?->undecorated ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $productVariant = ProductVariant::create($productVariantData);
                $this->processProductVariantPricesData($basePrice, $productId, $productVariant->id);
                $this->processProductAdditionsData($priceGroup['additions'], $productId, $productVariant->id);
            }
        }
    }

    private function getSupplierIdFromProduct(int $productId): int
    {
        if (Product::where('id', $productId)->exists()) {
            return Product::select('supplier_id')->where('id', $productId)->first()->supplier_id;
        } else {
            return 0;
        }
    }

    private function processProductVariantPricesData(object $basePrice, int $productId, int $productVariantId): void
    {
        $priceBreaks = (object) $basePrice->price_breaks ?? [];
        if ($priceBreaks) {
            foreach ($priceBreaks as $priceBreak) {
                $priceBreak = (object) $priceBreak ?? null;
                if ($priceBreak) {
                    $this->variantPricesDataToInsert[] = [
                        'product_id' => $productId,
                        'product_variant_id' => $productVariantId,
                        'qty' => $priceBreak->qty ?? null,
                        'price' => $priceBreak->price ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    //For finding the lowest price
                    $this->setVariantPriceData($productId, $priceBreak->price ?? null);
                }
            }
        }
    }

    private function setVariantPriceData(int $productId, ?float $price): void
    {
        $this->variantPriceData[] = [
            'product_id' => $productId,
            'price' => $price,
        ];
    }

    private function processProductAdditionsData(array $additions, int $productId, int $productVariantId): void
    {
        $additions = (object) $additions ?? [];
        if ($additions) {
            foreach ($additions as $addition) {
                $addition = (object) $addition ?? null;
                if ($addition) {
                    $productAdditionData = [
                        'product_id' => $productId,
                        'product_variant_id' => $productVariantId,
                        'key' => $addition->key ?? null,
                        'type' => $addition->type ?? $addition->description ?? null,
                        'setup' => $addition->setup ?? null,
                        'currency' => $addition->currency ?? null,
                        'lead_time' => $addition->lead_time ?? null,
                        'description' => $addition->description ?? null,
                        'undecorated' => $addition->undecorated ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $productAddition = ProductAddition::create($productAdditionData);
                    $this->processProductAdditionPricesData($addition->price_breaks, $productId, $productAddition->id);
                }
            }
        }
    }

    private function processProductAdditionPricesData(array $priceBreaks, int $productId, int $productAdditionId): void
    {
        if ($priceBreaks) {
            foreach ($priceBreaks as $priceBreak) {
                $priceBreak = (object) $priceBreak ?? null;
                if ($priceBreak) {
                    $this->additionPricesDataToInsert[] = [
                        'product_id' => $productId,
                        'product_addition_id' => $productAdditionId,
                        'qty' => $priceBreak->qty ?? null,
                        'price' => $priceBreak->price ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }
    }

    private function insertExtraInfoData(): void
    {
        if (! empty($this->extraInfoDataToInsert)) {
            foreach (array_chunk($this->extraInfoDataToInsert, $this->chunkSize) as $chunk) {
                ProductExtraInfo::insert($chunk);
            }
            $this->extraInfoDataToInsert = []; // Clear the array after inserting
        }
    }

    private function insertMediaData(): void
    {
        if (! empty($this->mediaDataToInsert)) {
            foreach (array_chunk($this->mediaDataToInsert, $this->chunkSize) as $chunk) {
                ProductMedia::insert($chunk);
            }
            $this->mediaDataToInsert = []; // Clear the array after inserting
        }
    }

    private function insertColoursListData(): void
    {
        if (! empty($this->coloursListDataToInsert)) {
            foreach (array_chunk($this->coloursListDataToInsert, $this->chunkSize) as $chunk) {
                ProductColourList::insert($chunk);
            }
            $this->coloursListDataToInsert = []; // Clear the array after inserting
        }
    }

    private function insertColoursSupplierTextData(): void
    {
        if (! empty($this->coloursSupplierTextDataToInsert)) {
            foreach (array_chunk($this->coloursSupplierTextDataToInsert, $this->chunkSize) as $chunk) {
                ProductColourSupplierText::insert($chunk);
            }
            $this->coloursSupplierTextDataToInsert = []; // Clear the array after inserting
        }
    }

    private function insertProductVariantPricesData(): void
    {
        if (! empty($this->variantPricesDataToInsert)) {
            foreach (array_chunk($this->variantPricesDataToInsert, $this->chunkSize) as $chunk) {
                ProductVariantPrice::insert($chunk);
            }
            $this->variantPricesDataToInsert = []; // Clear the array after inserting
        }
    }

    private function insertProductAdditionPricesData(): void
    {
        if (! empty($this->additionPricesDataToInsert)) {
            foreach (array_chunk($this->additionPricesDataToInsert, $this->chunkSize) as $chunk) {
                ProductAdditionPrice::insert($chunk);
            }
            $this->additionPricesDataToInsert = []; // Clear the array after inserting
        }
    }

    private function processExtraInfoDataUpdate(object $product_details, int $productId): void
    {
        $extraInfoDataToUpdateOrInsert = [];
        foreach ($product_details as $detail) {
            $detail = (object) $detail ?? [];
            if ($detail) {
                $extraInfoDataToUpdateOrInsert[] = [
                    'product_id' => $productId,
                    'name' => $detail->name ?? null,
                    'detail' => $detail->detail ?? null,
                    'updated_at' => now(),
                ];
            }
        }

        if (! empty($extraInfoDataToUpdateOrInsert)) {
            $productExtraInfoInstance = new ProductExtraInfo;
            $index = 'product_id';
            $index1 = 'name';

            $this->batch->updateWithTwoIndex($productExtraInfoInstance, $extraInfoDataToUpdateOrInsert, $index,
                $index1);

            // Bulk insert which are not exist
            foreach ($extraInfoDataToUpdateOrInsert as &$data) {
                $data['created_at'] = now();
            }

            $productExtraInfo = ProductExtraInfo::where('product_id', $productId)->get()->toArray();
            $this->extraInfoDataToInsert[] = collect($extraInfoDataToUpdateOrInsert)->diffUsing($productExtraInfo, function ($a, $b) {
                return $a['name'] <=> $b['name'];
            })->all();

            if (! empty($this->extraInfoDataToInsert[0])) {
                $this->extraInfoDataToInsert = $this->extraInfoDataToInsert[0];
                $this->insertExtraInfoData();
            }
        }
    }

    private function processMediasDataUpdate(string $heroImage, object $product_product, int $productId): void
    {
        $mediaDataToUpdateOrInsert = [];
        // For images
        $product_images = $product_product->images ?? [];
        if ($product_images) {
            foreach ($product_images as $image) {
                $changeImageUrl = null;
                if ($heroImage === $image) {
                    // skip same image of media
                    continue;
                }

                if ($image) {
                    $changeImageUrl = $this->overWriteImageExtension($image);
                }

                $mediaDataToUpdateOrInsert[] = [
                    'product_id' => $productId,
                    'type' => 'image',
                    'actual_url' => $changeImageUrl ?? null,
                    'updated_at' => now(),
                ];
            }
        }

        // For videos
        $product_videos = $product_product->videos ?? [];
        if ($product_videos) {
            foreach ($product_videos as $video) {
                $mediaDataToUpdateOrInsert[] = [
                    'product_id' => $productId,
                    'type' => 'video',
                    'actual_url' => $video,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // For Line arts
        $product_lineArt = $product_product->line_art ?? [];
        if ($product_lineArt) {
            foreach ($product_lineArt as $lineArt) {
                $mediaDataToUpdateOrInsert[] = [
                    'product_id' => $productId,
                    'type' => 'line-art',
                    'actual_url' => $lineArt,
                    'updated_at' => now(),
                ];
            }
        }

        if (! empty($mediaDataToUpdateOrInsert)) {
            $productMediaInstance = new ProductMedia;
            $index = 'product_id';
            $index1 = 'actual_url';

            $this->batch->updateWithTwoIndex($productMediaInstance, $mediaDataToUpdateOrInsert, $index, $index1);

            // Bulk insert which are not exist
            foreach ($mediaDataToUpdateOrInsert as &$data) {
                $data['created_at'] = now();
            }
            $productMedia = ProductMedia::where('product_id', $productId)->get()->toArray();

            $this->mediaDataToInsert[] = collect($mediaDataToUpdateOrInsert)->diffUsing($productMedia,
                function ($a, $b) {
                    return $a['actual_url'] <=> $b['actual_url'];
                })->all();

            if (! empty($this->mediaDataToInsert[0])) {
                $this->mediaDataToInsert = $this->mediaDataToInsert[0];
                $this->insertMediaData();
            }

        }
    }

    private function processColoursListDataUpdate(object $product_colours, int $productId): void
    {
        if (! isset($product_colours->list) || ! is_array($product_colours->list)) {
            return;
        }

        $coloursListDataToInsertOrUpdate = [];
        foreach ($product_colours->list as $list) {

            if (! empty($list)) {
                $list = (object) $list;
                $swatch = $list->swatch ?? [];
                $colours = $list->colours ?? [];
                $appa_colours = $list->appa_colours ?? [];

                $colours_json = ! empty($colours) ? json_encode($colours, true) : null;
                $colours = cleanJsonString($colours_json);
                $name = cleanString($list->name);

                $coloursListDataToInsertOrUpdate[] = [
                    'product_id' => $productId,
                    'for' => $list->for ?? null,
                    'name' => $name, //trim($list->name) ?? null,
                    'image' => $list->image ?? null,
                    'swatch' => ! empty($swatch) ? json_encode($swatch, true) : null,
                    'colours' => $colours,
                    'appa_colours' => ! empty($appa_colours) ? json_encode($appa_colours, true) : null,
                    'updated_at' => now(),
                ];

            }
        }

        // Update or insert process if product updated
        if (! empty($coloursListDataToInsertOrUpdate)) {
            $productColourListInstance = new ProductColourList;
            $index = 'product_id';
            $index1 = 'name';

            $this->batch->updateWithTwoIndex($productColourListInstance, $coloursListDataToInsertOrUpdate, $index,
                $index1);

            // Bulk insert which are not exist
            foreach ($coloursListDataToInsertOrUpdate as &$data) {
                $data['created_at'] = now();
            }
            $productColourList = ProductColourList::where('product_id', $productId)->get()->toArray();

            $this->coloursListDataToInsert[] = collect($coloursListDataToInsertOrUpdate)->diffUsing($productColourList,
                function ($a, $b) {
                    return $a['name'] <=> $b['name'];
                })->all();

            if (! empty($this->coloursListDataToInsert[0])) {
                $this->coloursListDataToInsert = $this->coloursListDataToInsert[0];
                $this->insertColoursListData();
            }
        }
    }

    private function processColoursSupplierTextDataUpdate(?object $product_colours, int $productId): void
    {
        $coloursSupplierText = [];
        $supplier_texts = $product_colours->supplier_text ?? [];
        foreach ($supplier_texts as $supplier_text) {
            $supplier_text = (object) $supplier_text ?? [];
            if ($supplier_text) {
                $coloursSupplierText[] = [
                    'product_id' => $productId,
                    'name' => $supplier_text->name ?? null,
                    'detail' => $supplier_text->detail ?? null,
                    'updated_at' => now(),
                ];
            }
        }

        // Update or insert process if product updated
        if (! empty($coloursSupplierText)) {
            $productColourSupplierTextInstance = new ProductColourSupplierText;
            $index = 'product_id';
            $index1 = 'name';

            $this->batch->updateWithTwoIndex($productColourSupplierTextInstance, $coloursSupplierText, $index, $index1);

            // Bulk insert which are not exist
            foreach ($coloursSupplierText as &$data) {
                $data['created_at'] = now();
            }
            $productColourSupplierText = ProductColourSupplierText::where('product_id', $productId)->get()->toArray();

            $this->coloursSupplierTextDataToInsert[] = collect($coloursSupplierText)->diffUsing($productColourSupplierText,
                function ($a, $b) {
                    return $a['name'] <=> $b['name'];
                })->all();
            if (! empty($this->coloursSupplierTextDataToInsert[0])) {
                $this->coloursSupplierTextDataToInsert = $this->coloursSupplierTextDataToInsert[0];
                $this->insertColoursSupplierTextData();
            }

        }

    }

    private function processProductVariantsDataUpdate(object $product_prices, int $productId): void
    {

        // Check if price_groups is set and is an array
        if (! isset($product_prices->price_groups) || ! is_array($product_prices->price_groups)) {
            return; // Exit early if price_groups is not set or not an array
        }

        $priceGroups = (object) $product_prices->price_groups ?? [];
        if ($priceGroups) {
            foreach ($priceGroups as $priceGroup) {
                $basePrice = (object) $priceGroup['base_price'] ?? [];
                if (! $basePrice?->key || $basePrice?->key == '') {
                    // To fix the duplication issue for existing records
                    continue;
                }
                // To fix the duplication issue for existing records
                $this->checkVariantDuplicate($basePrice?->key, $productId);

                $tags = $base_price?->tags ?? null;
                $productVariantData = [
                    'product_id' => $productId,
                    'supplier_id' => self::getSupplierIdFromProduct($productId),
                    'key' => $basePrice?->key ?? null,
                    'free' => $basePrice?->free ?? null,
                    'tags' => json_encode($tags, true),
                    'type' => $basePrice?->type != '' ? $basePrice?->type : 'Add on',
                    'setup' => $basePrice?->setup ?? null,
                    'indent' => $basePrice?->indent ?? null,
                    'currency' => $basePrice?->currency ?? null,
                    'lead_time' => $basePrice?->lead_time ?? null,
                    'description' => $basePrice?->description ?? null,
                    'undecorated' => $basePrice?->undecorated ?? null,
                ];
                $productVariant = ProductVariant::updateOrCreate(
                    ['product_id' => $productId, 'key' => $basePrice?->key],
                    $productVariantData,
                );

                if ($productVariant) {
                    $this->processProductVariantPricesDataUpdate($basePrice, $productId, $productVariant['id']);
                    $this->processProductAdditionsDataUpdate($priceGroup['additions'], $productId,
                        $productVariant['id']);
                }
            }
        }
    }

    private function checkVariantDuplicate(string $key, int $productId): void
    {
        // Check key based exist then check with product, if exist then update otherwise edit key and continue
        $existVariants = ProductVariant::where('key', '=', $key)->get();
        if ($existVariants) {
            foreach ($existVariants as $existVariant) {
                $existVariantWithProduct = ProductVariant::where('key', '=', $key)
                    ->where('product_id', '=', $productId)->exists();
                if (! $existVariantWithProduct) {
                    $updatedKey = $existVariant->key . '_' . $existVariant->id;
                    ProductVariant::where('key', '=', $existVariant->key)
                        ->where('id', $existVariant->id)
                        ->update(['key' => $updatedKey]);
                }
            }
        }
    }

    private function processProductVariantPricesDataUpdate(
        object $basePrice,
        int $productId,
        int $productVariantId
    ): void {
        $variantPricesDataToInsertOrUpdate = [];
        $priceBreaks = (object) $basePrice->price_breaks ?? [];

        if ($priceBreaks) {
            foreach ($priceBreaks as $priceBreak) {
                $priceBreak = (object) $priceBreak ?? null;
                if ($priceBreak) {
                    $variantPricesDataToInsertOrUpdate[] = [
                        'product_id' => $productId,
                        'product_variant_id' => $productVariantId,
                        'qty' => $priceBreak->qty ?? null,
                        'price' => $priceBreak->price ?? null,
                        'updated_at' => now(),
                    ];

                    //For finding the lowest price

                    $this->setVariantPriceData($productId, $priceBreak->price ?? null);
                }
            }
            if (! empty($variantPricesDataToInsertOrUpdate)) {
                $productVariantPriceInstance = new ProductVariantPrice;
                // Both index combination always be unique
                $index = 'product_variant_id';
                $index1 = 'qty';
                $this->batch->updateWithTwoIndex($productVariantPriceInstance, $variantPricesDataToInsertOrUpdate,
                    $index, $index1);

                // Bulk insert which are not exist
                foreach ($variantPricesDataToInsertOrUpdate as &$data) {
                    $data['created_at'] = now();
                }
                $productVariantPrice = ProductVariantPrice::where('product_variant_id',
                    $productVariantId)->get()->toArray();

                $this->variantPricesDataToInsert[] = collect($variantPricesDataToInsertOrUpdate)->diffUsing($productVariantPrice,
                    function ($a, $b) {
                        return $a['qty'] <=> $b['qty'];
                    })->all();

                if (! empty($this->variantPricesDataToInsert[0])) {
                    $this->variantPricesDataToInsert = $this->variantPricesDataToInsert[0];
                    $this->insertProductVariantPricesData();
                }

            }
        }
    }

    private function processProductAdditionsDataUpdate(array $additions, int $productId, int $productVariantId): void
    {
        $additions = (object) $additions ?? [];
        if ($additions) {
            foreach ($additions as $addition) {
                $addition = (object) $addition ?? null;
                if ($addition) {
                    $productAdditionData = [
                        'product_id' => $productId,
                        'product_variant_id' => $productVariantId,
                        'key' => $addition->key ?? null,
                        'type' => $addition->type ?? $addition->description ?? null,
                        'setup' => $addition->setup ?? null,
                        'currency' => $addition->currency ?? null,
                        'lead_time' => $addition->lead_time ?? null,
                        'description' => $addition->description ?? null,
                        'undecorated' => $addition->undecorated ?? null,
                    ];
                    $productAddition = ProductAddition::updateOrCreate(
                        [
                            'product_id' => $productId, 'product_variant_id' => $productVariantId,
                            'key' => $addition->key,
                        ],
                        $productAdditionData,
                    );
                    if ($productAddition) {
                        $this->processProductAdditionPricesDataUpdate($addition->price_breaks, $productId,
                            $productAddition['id']);
                    }
                }
            }
        }
    }

    private function processProductAdditionPricesDataUpdate(
        array $priceBreaks,
        int $productId,
        int $productAdditionId
    ): void {
        $additionPricesDataToInsertOrUpdate = [];
        if ($priceBreaks) {
            foreach ($priceBreaks as $priceBreak) {
                $priceBreak = (object) $priceBreak ?? null;
                if ($priceBreak) {
                    $additionPricesDataToInsertOrUpdate[] = [
                        'product_id' => $productId,
                        'product_addition_id' => $productAdditionId,
                        'qty' => $priceBreak->qty ?? null,
                        'price' => $priceBreak->price ?? null,
                        'updated_at' => now(),
                    ];
                }
            }

            // Update or insert process if product updated
            if (! empty($additionPricesDataToInsertOrUpdate)) {
                $productAdditionPriceInstance = new ProductAdditionPrice;
                // Both index combination always be unique
                $index = 'product_addition_id';
                $index1 = 'qty';

                $this->batch->updateWithTwoIndex($productAdditionPriceInstance, $additionPricesDataToInsertOrUpdate,
                    $index, $index1);

                // Bulk insert which are not exist
                foreach ($additionPricesDataToInsertOrUpdate as &$data) {
                    $data['created_at'] = now();
                }

                $productAdditionPrice = ProductAdditionPrice::where('product_addition_id',
                    $productAdditionId)->get()->toArray();

                $this->additionPricesDataToInsert[] = collect($additionPricesDataToInsertOrUpdate)->diffUsing($productAdditionPrice,
                    function ($a, $b) {
                        return $a['qty'] <=> $b['qty'];
                    })->all();

                if (! empty($this->additionPricesDataToInsert[0])) {
                    $this->additionPricesDataToInsert = $this->additionPricesDataToInsert[0];
                    $this->insertProductAdditionPricesData();
                }

            }

        }
    }

    private function downloadAndStoreMediaForType(array $config, int $maxAttempts): void
    {
        $items = $config['model']::select($config['columns'])
            ->whereNotNull($config['actualUrlColumn'])
            ->whereNull($config['downloadedUrlColumn'])
            ->get();

        foreach ($items as $item) {
            $this->downloadAndStoreMediaItem($item, $config, $maxAttempts);
        }
    }

    private function downloadAndStoreMediaItem($item, array $config, int $maxAttempts): void
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::timeout(self::HTTP_TIMEOUT)->get($item->{$config['actualUrlColumn']});

                if ($response->successful()) {
                    $this->storeMediaItem($item, $response->body(), $config);
                    break; // Exit the loop since we got a successful response
                }
            } catch (Exception $exception) {
                if ($exception->getCode() === 28 && $attempt < $maxAttempts) {
                    sleep(2); // Wait for a moment before retrying

                    continue; // Retry the request
                } else {
                    $this->createLog('ERROR', __LINE__, $exception->getMessage());
                }
            }
        }
    }

    private function storeMediaItem($item, $responseBody, array $config): void
    {
        $fileName = basename($item->{$config['actualUrlColumn']});
        $subDirectory = is_array($config['subDirectory'])
            ? ($config['subDirectory'][$item->type] ?? 'Others/')
            : $config['subDirectory'];
        $path = $subDirectory . $fileName;
        $storageDisk = Storage::disk('products');

        if ($storageDisk->put($path, $responseBody)) {
            $downloadedUrl = $storageDisk->url($path);
            $item->update([$config['downloadedUrlColumn'] => $downloadedUrl]);
        }
    }
}
