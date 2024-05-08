<?php

namespace App\Services\Magento;

use App\Jobs\MagentoCreateCustomOptionsJob;
use App\Jobs\MagentoUpdateCustomOptionsJob;
use App\Jobs\updateMagentoOptionTypeIdForExistingDataJob;
use App\Jobs\UpdateProductMagentoIdJob;
use App\Magento\ProductCustomOptions;
use App\Magento\ProductMedia;
use App\Models\GeneralLog;
use App\Models\MagentoColour;
use App\Models\Product;
use App\Models\ProductColourList;
use App\Models\ProductColourMapper;
use App\Models\ProductType;
use App\Models\ProductTypeSub;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Services\CommonService;
use Exception;
use Illuminate\Support\Facades\Log;
use JetBrains\PhpStorm\NoReturn;
use Mavinoo\Batch\Batch;
use Tops\Magento\Magento\GetProducts;
use Tops\Magento\Magento\PostProduct;
use Tops\Magento\Magento\UpdateProduct;

class ProductsServiceMagento extends MagentoBaseService
{
    public GetProducts $getProducts;

    protected PostProduct $postProduct;

    protected \App\Magento\Product $productUpdateAsyncAll;

    protected ProductMedia $productMedia;

    protected ProductCustomOptions $productCustomOptions;

    protected UpdateProduct $updateProduct;
    protected \App\Magento\Product $productDeleteAsyncAll;

    private Batch $batch;
    private CommonService $commonService;

    public function __construct(PostProduct $postProduct, ProductCustomOptions $productCustomOptions,
        Batch $batch, GetProducts $getProducts, ProductMedia $productMedia,
        CommonService $commonService, UpdateProduct $updateProduct, \App\Magento\Product $productUpdateAsyncAll, \App\Magento\Product $productDeleteAsyncAll)
    {
        parent::__construct();

        // Product instance
        $this->postProduct = $postProduct;
        $this->getProducts = $getProducts;
        $this->productMedia = $productMedia;

        //Custom options instance
        $this->productCustomOptions = $productCustomOptions;
        $this->batch = $batch;
        $this->commonService = $commonService;
        $this->updateProduct = $updateProduct;
        $this->productUpdateAsyncAll = $productUpdateAsyncAll;
        $this->productDeleteAsyncAll = $productDeleteAsyncAll;

    }

    #[NoReturn]
    public function postProducts(array $productData): void
    {
        $this->postProduct->ENDPOINT = config('services.magento.end_point') . '/all';
        $result = $this->postProduct->postProduct($productData);

        if (! empty($result) && isset($result['message'])) {
            $logData = [
                'module_name' => 'Products',
                'action' => 'postProducts',
                'payload' => json_encode($productData, true),
                'response' => json_encode($result, true)];
            GeneralLog::create($logData);
        } else {
            $sku = $result['sku'];
            $status = $result['status'];
            $magentoId = $result['id'];
            $explode = explode('-', $sku);
            $end = end($explode);
            $promodataId = is_numeric($end) ? $end : null;
            Product::where('promodata_id', $promodataId)->update(['magento_id' => $magentoId, 'magento_status' => $status]);
        }
    }

    public function getMediaGalleryEntries(?string $image, bool $isHeroImage = false, $callFrom = 'CRON'): array
    {

        $media_gallery_entries = [];
        if ($image) {
            $pathInfo = pathinfo($image);
            $filaName = $pathInfo['filename'];
            $baseName = $pathInfo['basename'];
            $extension = $pathInfo['extension'] ?? 'jpeg';
            $extension = $extension === 'jpg' ? 'jpeg' : $extension;
            $mimeType = 'image/' . $extension;
            $fileUrl = asset($image);
            if (! $callFrom) {
                // Remove the base URL and '/storage' from the original URL
                $relativePath = str_replace(url('/storage'), '', $fileUrl);

                // Generate the Laravel asset URL using the url helper function
                $fileUrl = "storage{$relativePath}";
            }

            $types = [];
            if ($isHeroImage) {
                $types = [
                    'image',
                    'small_image',
                    'thumbnail',
                    'swatch_image',
                ];
            }

            try {
                $base64Image = base64_encode(file_get_contents($fileUrl));
            } catch (Exception $exception) {
                Log::error('getMediaGalleryEntries ==>' . $exception->getMessage());

                return $media_gallery_entries;
            }
            // Media Gallery Entries
            $media_gallery_entries['entry'] =
                [
                    'media_type' => 'image',
                    'label' => $filaName,
                    'position' => 1,
                    'disabled' => false,
                    'types' => $types,
                    'file' => $fileUrl,
                    'content' => [
                        'base64_encoded_data' => $base64Image,
                        'type' => $mimeType,
                        'name' => time() . '_' . $baseName,
                    ],
                ];
        }

        return $media_gallery_entries;
    }

    public function updateCustomOptions(array $updateCustomOptionsData): void
    {
        $this->productCustomOptions->ENDPOINT = config('services.magento.end_point') . '/all';
        $optionId = $updateCustomOptionsData['option']['magento_option_id'];
        if (! $optionId) {
            return;
        }

        $productVariantIds = $updateCustomOptionsData['option']['product_variant_id'];
        unset($updateCustomOptionsData['option']['magento_option_id']);
        unset($updateCustomOptionsData['option']['product_variant_id']);
        $variantIds = $productVariantIds ? explode(',', trim($productVariantIds, ',')) : null;
        $result = $this->productCustomOptions->updateCustomOptions($optionId, $updateCustomOptionsData);
        if (! empty($result) && isset($result['message'])) {
            $logData = [
                'module_name' => 'Products',
                'action' => 'updateCustomOptions',
                'api_end_point' => $this->productCustomOptions->ENDPOINT,
                'payload' => json_encode($updateCustomOptionsData, true),
                'response' => json_encode($result, true)];
            GeneralLog::create($logData);
        } else {
            if ($variantIds && ! empty($result) && $result['option_id'] && $result['values']) {
                $this->updateToProductVariant($variantIds, $result);

            }
        }
    }

    public function updateVariantMagentoId(object $product): void
    {

        $this->productCustomOptions->ENDPOINT = config('services.magento.end_point');
        $productSKU = $this->commonService->createSKU($product->code, $product->promodata_id);
        $result = $this->productCustomOptions->getCustomOptions($productSKU);

        if (! empty($result) && ! isset($result['message'])) {
            $variantUpdateData = [];
            foreach ($result as $value) {
                $option = (object) $value;
                $optionId = $option->option_id;
                $title = $option->title;
                // for bulk update
                $variantUpdateData[] = [
                    'product_id' => $product->id,
                    'type' => $title,
                    'magento_option_id' => $optionId,
                    'updated_at' => now(),
                ];
            }
            $this->bulkUpdateVariantMagentoId($variantUpdateData);
        } else {
            if (! empty($result) && isset($result['message'])) {
                // Insert to general Log
                $logData = [
                    'module_name' => 'Product',
                    'action' => 'updateVariantMagentoId',
                    'api_end_point' => $this->productCustomOptions->ENDPOINT,
                    'payload' => $productSKU,
                    'response' => json_encode($result, true),
                ];
                GeneralLog::create($logData);
            }
        }
    }

    public function bulkUpdateVariantMagentoId(array $variantData): void
    {
        if (! empty($variantData)) {
            $productVariantInstance = new ProductVariant;
            $index = 'product_id';
            $index1 = 'type';
            $this->batch->updateWithTwoIndex($productVariantInstance, $variantData, $index, $index1);
        }
    }

    public function processCustomOptionsData(object $product, bool $isDefault = false, ?float $increasePrice = null): void
    {
        $increasePrice = $increasePrice ?: $product->custom_option_price;
        $this->productCustomOptions->ENDPOINT = config('services.magento.end_point');

        $optionsArray = [];

        $productSKU = $this->commonService->createSKU($product->code, $product->promodata_id);

        $sortOrder = 1;
        foreach ($product->variants as $variant) {
            if ($variant?->is_approved && $variant?->is_created === 0 && $variant?->magento_type && $variant?->magento_option_id === null) {
                // Check duplicate option
                if ($this->checkDuplicateMagentoType($variant)) {
                    continue;
                }

                $option = $this->createOption($variant, $productSKU, $isDefault, $sortOrder, $increasePrice);
                if (empty($option)) {
                    continue;
                }
                $option['product_variant_id'] = $variant?->id;
                $optionsArray[]['option'] = $option;
                $isDefault = false;
                $sortOrder++;
            }
        }
        // put values of each option under same title for uniqueness
        $finalOptionsArray = $this->getUniqueOptionTitleOptionArray($optionsArray, true);
        $this->createCustomOptionJobData($product, $finalOptionsArray);
    }

    public function getLowestPrice(object $productVariantPrice): ?float
    {
        return collect($productVariantPrice)->pluck('price')->min();
    }

    public function processUpdateCustomOptionsData(object $product, ?float $increasePrice = null): void
    {
        $increasePrice = $increasePrice ?: $product->custom_option_price;
        $this->productCustomOptions->ENDPOINT = config('services.magento.end_point');
        $optionsArray = [];

        $productSKU = $this->commonService->createSKU($product->code, $product->promodata_id);
        $isDefault = false;
        $sortOrder = 1;
        $optionDisabledValues = [];
        foreach ($product->variants as $variant) {
            if ($variant?->magento_type) {
                // Check duplicate option
                if ($this->checkDuplicateMagentoType($variant)) {
                    continue;
                }
                $option = $this->createOption($variant, $productSKU, $isDefault, $sortOrder, $increasePrice);
                if (empty($option)) {
                    continue;
                }
                $option['magento_option_id'] = $variant?->magento_option_id;
                $option['option_id'] = $variant?->magento_option_id;
                $option['product_variant_id'] = $variant?->id;
                $optionsArray[]['option'] = $option;

                // put all option disabled key values
                $optionDisabledValues[] = $variant->is_approved;

                $sortOrder++;
            }

        }

        // put values of each option under same title for uniqueness
        $finalOptionsArray = $this->getUniqueOptionTitleOptionArray($optionsArray);

        // put to job
        $sortOrderOption = 1;
        foreach ($finalOptionsArray as $option) {
            $sortOrder = 1;
            // Loop through the "values" array and update the "sort_order" value by $sortOrder variable
            foreach ($option['option']['values'] as &$value) {
                $value['sort_order'] = $sortOrder;
                $sortOrder++;
            }
            $option['option']['sort_order'] = $sortOrderOption;

            // Filter out non-zero values and check if the resulting array is empty
            if (empty(array_filter($optionDisabledValues, function ($disabledValue) {
                return $disabledValue != 0;
            }))) {
                // When all option type value disabled then it will disable the option
                $option['option']['extension_attributes']['disabled_by_values'] = 1;
            }

            $sortOrderOption++;
            MagentoUpdateCustomOptionsJob::dispatch($option);
        }
    }

    #[NoReturn]
    public function updateProductMagentoId(): void
    {
        $this->getProducts->ENDPOINT = config('services.magento.end_point');
        $page = 1;
        $pageSize = 500;
        do {
            UpdateProductMagentoIdJob::dispatch($page, $pageSize);
            $page++;
        } while ($page <= $this->getTotalPages($pageSize));
    }

    public function getProducts(int $page, int $pageSize): array
    {
        $this->getProducts->ENDPOINT = config('services.magento.end_point');

        return $this->getProducts->getProducts('', $page, $pageSize);
    }

    public function bulkUpdateProductMagentoId(array $productUpdateData): void
    {
        if (! empty($productUpdateData)) {
            $productInstance = new Product;
            $index = 'promodata_id';
            $this->batch->update($productInstance, $productUpdateData, $index);
        }
    }

    public function getProductMagentoId(string $sku): ?int
    {
        $this->getProducts->ENDPOINT = config('services.magento.end_point');
        $this->getProducts->API_URL_PRODUCT = $this->getProducts->API_URL . '/' . $sku;
        $result = $this->getProducts->getProductDetail($sku);
        if (! empty($result) && ! isset($result['message'])) {
            $magentoProductData = (object) $result ?? [];

            return $magentoProductData && $magentoProductData->id ? $magentoProductData->id : null;
        } else {
            if (! empty($result) && isset($result['message'])) {
                // Insert to general Log
                $logData = [
                    'module_name' => 'Product',
                    'action' => 'getProductMagentoId',
                    'api_end_point' => $this->getProducts->ENDPOINT,
                    'payload' => $sku,
                    'response' => json_encode($result, true),
                ];
                GeneralLog::create($logData);
            }

            return null;
        }
    }

    public function postProductMedia(int $id, string $model, string $sku, array $productMediaData): void
    {
        $this->productMedia->ENDPOINT = config('services.magento.end_point') . '/all';
        $result = $this->productMedia->postMedia($sku, $productMediaData);

        $result = json_decode($result, true);
        if (! empty($result) && isset($result['message'])) {
            // Insert to general Log
            $logData = [
                'module_name' => $model,
                'action' => 'postProductMedia',
                'api_end_point' => $this->productMedia->ENDPOINT . '/V1/product/' . $sku . '/media',
                'payload' => json_encode($productMediaData, true),
                'response' => json_encode($result, true),
            ];
            GeneralLog::create($logData);
        } else {
            $magento_img_id = $result ? (int) $result : null;
            match ($model) {
                'Product' => Product::where('id', $id)->update(['is_uploaded' => 1, 'magento_img_id' => $magento_img_id]),
                'ProductColourList' => ProductColourList::where('id', $id)->update(['is_uploaded' => 1, 'magento_img_id' => $magento_img_id]),
                'ProductMedia' => \App\Models\ProductMedia::where('id', $id)->update(['is_uploaded' => 1, 'magento_img_id' => $magento_img_id]),
                default => Log::error('Invalid model')
            };
        }
    }

    public function processExistingProductsColourMapping(array $colours, int $productId): array
    {
        $appaColoursData = [];
        $magentoColoursData = [];
        $magentoColoursIdsData = [];
        foreach ($colours as $colour) {

            // Check color exist to product colour mapper
            $colourMapperData = $this->commonService->isColourExistToProductColourMapper($colour);

            if (! $colourMapperData) {
                // if, it does not exist then create that colour to product colour mapper table
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
                $magentoColourId = $colourMapperData?->magento_colour_id ?? null;
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
            'id' => $productId,
            'appa_colours' => implode(',', $appaColoursData),
            'magento_colours' => implode(',', $magentoColoursData),
            'magento_colours_ids' => implode(',', $magentoColoursIdsData),
        ];
    }

    #[NoReturn]
    public function updateProducts(string $sku, array $productData): void
    {
        $this->updateProduct->ENDPOINT = config('services.magento.end_point');
        $result = $this->updateProduct->updateall($sku, $productData);
        if (! empty($result) && isset($result['message'])) {
            $logData = [
                'module_name' => 'Products',
                'action' => 'updateProducts',
                'api_end_point' => $this->updateProduct->ENDPOINT . '/all/V1/products/',
                'payload' => json_encode($productData, true),
                'response' => json_encode($result, true)];
            GeneralLog::create($logData);
        } else {
            $sku = $result['sku'];
            $status = $result['status'];
            $magentoId = $result['id'];
            $explode = explode('-', $sku);
            $end = end($explode);
            $promodataId = is_numeric($end) ? $end : null;
            Product::where('promodata_id', $promodataId)->update(['magento_id' => $magentoId, 'magento_status' => $status]);
        }
    }

    public function setProductPostData(object $product, $operation = 'Create'): array
    {
        $additionalInfo = $this->getExtraInfos($product->extraInfos);
        $urlKey = str_replace(' ', '-', $product->name) . '-' . $product->promodata_id;
        $sku = $this->commonService->createSKU($product->code, $product->promodata_id);
        $minSaleQty = $this->getMinimumSaleQty($product->id);
        $checkPrice = $this->checkPriceExist($product->id);
        $categoryLinks = $product->product_type_id && $product->product_type_sub_id
            ? $this->getCategoryLinks($product->product_type_id, $product->product_type_sub_id)
            : [];
        $quotationType = '';

        if (! $checkPrice) {
            $quotationType = [
                'attribute_code' => 'quotation_type',
                'value' => 17, // It's enable ask for quote at magento side
            ];
        }
        $leadTime = $this->getVariantsLeadTime($product->id);

        $increasePrice = $product->custom_option_price ?: 0;
        $magentoPrice = $product->magento_price ?: 0;
        $calculatedPrice = ($magentoPrice * $increasePrice) / 100;
        $finalMagentoPrice = round(($magentoPrice + $calculatedPrice), 2);

        // Setup fee
        $setupCost = '';
        $deliveryPrice = '';
        if (isset($product->magento_setup_cost) && $product->magento_setup_cost != '') {
            $setupCost = [
                'attribute_code' => 'setup_cost',
                'value' => $product->magento_setup_cost,
            ];
        }

        if (isset($product->magento_delivery_price) && $product->magento_delivery_price != '') {
            $deliveryPrice = [
                'attribute_code' => 'delivery_price',
                'value' => $product->magento_delivery_price,
            ];
        }
        $productArray = [
            'sku' => $sku,
            'attribute_set_id' => 4,
            'price' => $finalMagentoPrice,
            'status' => $product->discontinued ? 2 : ($product->magento_status ? $product->magento_status : 1),
            'visibility' => 4,
            'type_id' => 'simple',
            'weight' => 1,
            'extension_attributes' => [
                'website_ids' => [
                    1,
                ],
                'category_links' => $categoryLinks,
                'stock_item' => [
                    'is_in_stock' => true,
                    'qty' => 9999,
                    'min_sale_qty' => $minSaleQty,
                    'use_config_min_sale_qty' => $minSaleQty !== 0 ? 0 : 1, // Default will be checked (1) in config, means unable to change qty
                ],
                'absolute_cost' => true,
                'absolute_price' => true,
                'absolute_weight' => false,
                'hide_additional_product_price' => false,
                'shareable_link' => 'use_config',
                'sku_policy' => 'use_config',
            ],
            'product_links' => [],
            //            'options' => [], // Enabled when pass product option at product create time
            //            'tier_prices' => [],  // Enabled when pass product level tier prices
            'custom_attributes' => [
                [
                    'attribute_code' => 'page_layout',
                    'value' => '1column', // 'product-full-width',
                ],
                //                [
                //                    'attribute_code' => 'url_key',
                //                    'value' => $urlKey,
                //                ],
                [
                    'attribute_code' => 'tax_class_id',
                    'value' => '2',
                ],
                [
                    'attribute_code' => 'supplier_name',
                    'value' => $product->supplier->name,
                ],
                [
                    'attribute_code' => 'discontinued_at',
                    'value' => $product->discontinued_at,
                ],
                [
                    'attribute_code' => 'discontinued_reason',
                    'value' => $product->discontinued_reason,
                ],
                [
                    'attribute_code' => 'country_of_manufacture',
                    'value' => $product->country,
                ],
                [
                    'attribute_code' => 'aapa_description',
                    'value' => $product->description,
                ],
                [
                    'attribute_code' => 'filter_min_qty',
                    'value' => $minSaleQty, //$product->min_qty === 0 ? 1 : $product->min_qty,
                ],
                [
                    'attribute_code' => 'primary_colour',
                    'value' => $product->magento_colours_ids,
                ],
                [
                    'attribute_code' => 'additional_info_aapa',
                    'value' => $additionalInfo,
                ],
                [
                    'attribute_code' => 'aapa_lead_time',
                    'value' => $leadTime, // It's enable ask for quote at magento side
                ],
            ],
        ];

        // url_key pass at create time
        if ($operation === 'Create') {
            $productArray['custom_attributes'][] = [
                'attribute_code' => 'url_key',
                'value' => $urlKey,
            ];
        }

        // If quotation type set then pass to custom attributes
        if ($quotationType) {
            $productArray['custom_attributes'][] = $quotationType;
        }

        if ($setupCost) {
            $productArray['custom_attributes'][] = $setupCost;
        }
        if ($deliveryPrice) {
            $productArray['custom_attributes'][] = $deliveryPrice;
        }

        if ($operation !== 'Update') {
            $productArray['name'] = $product->name;
        }
        $productArray['custom_attributes'][] = [
            'attribute_code' => 'aapa_product_name',
            'value' => $product->name,
        ];

        return $productArray;

    }

    public function getExtraInfos(object $extraInfos): ?string
    {
        $info = '';
        foreach ($extraInfos as $extraInfo) {
            if ($extraInfo->name) {
                $name = ucfirst(str_replace('_', ' ', $extraInfo->name));
                $detail = $extraInfo->detail;
                $info .= '<div class="product-info-otr">';
                $info .= '<span class="product-linfo-label"> ' . $name . ': </span>';
                $info .= '<span class="product-linfo-value">' . $detail . '</span>';
                $info .= '</div>';
            }
        }

        return $info;
    }

    public function updateCustomOptionPrice(object $product, ?float $increasePrice): ?array
    {
        $optionsArray = [];
        $productSKU = $this->commonService->createSKU($product->code, $product->promodata_id);

        $sortOrder = 1;
        $isDefault = true;
        foreach ($product->variants as $variant) {
            if ($variant?->is_created === 0 && $variant?->magento_option_id === null) {
                $option = $this->createOption($variant, $productSKU, $isDefault, $sortOrder, $increasePrice);
                if (empty($option)) {
                    continue;
                }
                $option['product_variant_id'] = $variant?->id;
                $optionsArray[]['option'] = $option;
                $isDefault = false;
                $sortOrder++;
            }
        }
        // put values of each option under same title for uniqueness
        $finalOptionsArray = $this->getUniqueOptionTitleOptionArray($optionsArray, true);
        $this->createCustomOptionJobData($product, $finalOptionsArray);

        return [];
    }

    public function supplierBasedProductUpdate(array $products): void
    {
        $this->productUpdateAsyncAll->ENDPOINT = config('services.magento.end_point');
        $result = $this->productUpdateAsyncAll->updateBulkAsync($products);
        if (! empty($result) && isset($result['errors']) && $result['errors']) {
            $logData = [
                'module_name' => 'Products',
                'action' => 'supplierBasedProductUpdate',
                'api_end_point' => $this->productUpdateAsyncAll->ENDPOINT . '/all/async/bulk/V1/products/bySku',
                'payload' => json_encode($products, true),
                'response' => json_encode($result, true)];
            GeneralLog::create($logData);
        }
    }

    public function createCustomOptions(array $createCustomOptionsData): void
    {
        // Set Variant ids and unset from custom options:
        $productVariantIds = $createCustomOptionsData['option']['product_variant_id'];
        unset($createCustomOptionsData['option']['product_variant_id']);
        $variantIds = $productVariantIds ? explode(',', trim($productVariantIds, ',')) : null;
        // End

        $this->productCustomOptions->ENDPOINT = config('services.magento.end_point') . '/all';
        $result = $this->productCustomOptions->createCustomOptions($createCustomOptionsData);
        if (! empty($result) && isset($result['message'])) {
            $logData = [
                'module_name' => 'Products',
                'action' => 'createCustomOptions',
                'api_end_point' => $this->productCustomOptions->ENDPOINT . '/V1/products/options',
                'payload' => json_encode($createCustomOptionsData, true),
                'response' => json_encode($result, true)];
            GeneralLog::create($logData);
        } else {
            if ($variantIds && ! empty($result) && $result['option_id'] && isset($result['values'])) {
                $this->updateToProductVariant($variantIds, $result);
            }
        }
    }

    #[NoReturn]
    public function updateVariantOptionTypeId(): void
    {
        $this->getProducts->ENDPOINT = config('services.magento.end_point');
        $page = 1;
        $pageSize = 500;
        do {
            updateMagentoOptionTypeIdForExistingDataJob::dispatch($page, $pageSize);
            $page++;
        } while ($page <= $this->getTotalPages($pageSize));
    }

    public function postSingleProduct(array $productData): bool
    {
        $this->postProduct->ENDPOINT = config('services.magento.end_point') . '/all';
        $result = $this->postProduct->postProduct($productData);

        if (! empty($result) && isset($result['message'])) {
            $logData = [
                'module_name' => 'Products',
                'action' => 'postSingleProduct',
                'payload' => json_encode($productData, true),
                'response' => json_encode($result, true)];
            GeneralLog::create($logData);

            return false;
        } else {
            $sku = $result['sku'];
            $status = $result['status'];
            $magentoId = $result['id'];
            $explode = explode('-', $sku);
            $end = end($explode);
            $promodataId = is_numeric($end) ? $end : null;
            Product::where('promodata_id', $promodataId)->update(['magento_id' => $magentoId, 'magento_status' => $status]);

            return true;
        }
    }

    #[NoReturn]
    public function deleteBulkAsync(array $productSkus): void
    {
        $this->productDeleteAsyncAll->ENDPOINT = config('services.magento.end_point');
        $result = $this->productDeleteAsyncAll->deleteBulkAsync($productSkus);
        if (! empty($result) && isset($result['errors']) && $result['errors']) {
            $logData = [
                'module_name' => 'Products',
                'action' => 'supplierBasedProductUpdate',
                'api_end_point' => $this->productUpdateAsyncAll->ENDPOINT . '/all/async/bulk/V1/products/bySku',
                'payload' => json_encode($productSkus, true),
                'response' => json_encode($result, true)];
            GeneralLog::create($logData);
        }
    }

    public function getProductMedia(string $sku): array
    {
        $this->productMedia->ENDPOINT = config('services.magento.end_point');
        $result = $this->productMedia->getMedia($sku);
        $result = json_decode($result, true);
        if (! empty($result) && isset($result['message'])) {
            // Insert to general Log
            $logData = [
                'module_name' => 'Product',
                'action' => 'getProductMedia',
                'api_end_point' => $this->productMedia->ENDPOINT . '/V1/product/' . $sku . '/media',
                'response' => json_encode($result, true),
            ];
            GeneralLog::create($logData);

            return [];
        } else {
            return $result;
        }
    }

    public function deleteProductMedia(string $sku, int $valueId): bool
    {

        $this->productMedia->ENDPOINT = config('services.magento.end_point') . '/all';
        $result = $this->productMedia->deleteMedia($sku, $valueId);

        if (! $result) {
            $result = json_decode($result, true);
            if (! empty($result) && isset($result['message'])) {
                // Insert to general Log
                $logData = [
                    'module_name' => 'Product',
                    'action' => 'deleteProductMedia',
                    'api_end_point' => $this->productMedia->ENDPOINT . '/V1/product/' . $sku . '/media',
                    'response' => json_encode($result, true),
                ];
                GeneralLog::create($logData);

                return false;
            }
        }

        return $result;
    }

    public function deleteProductCustomOption(string $sku, int $optionId): bool
    {

        $this->productCustomOptions->ENDPOINT = config('services.magento.end_point') . '/all';
        $result = $this->productCustomOptions->deleteCustomOptions($sku, $optionId);

        if (! $result) {
            $result = json_decode($result, true);
            if (! empty($result) && isset($result['message'])) {
                // Insert to general Log
                $logData = [
                    'module_name' => 'Product',
                    'action' => 'deleteProductCustomOption',
                    'api_end_point' => $this->productMedia->ENDPOINT . '/V1/product/' . $sku . '/options/' . $optionId,
                    'response' => json_encode($result, true),
                ];
                GeneralLog::create($logData);

                return false;
            }
        }

        return $result;
    }

    #[NoReturn]
    private function updateToProductVariant(array $variantIds, array $result): void
    {
        $magentoTypeToOptionTypeIdMap = [];
        $optionId = $result['option_id'];
        $productSKU = $result['product_sku'];

        foreach ($result['values'] as $value) {
            $magentoType = $value['title'];
            $optionTypeId = $value['option_type_id'] ?? null;

            if (! $optionTypeId) {
                $result1 = $this->getCustomOptionFromMagento($productSKU);
                foreach ($result1['values'] as $value1) {
                    $magentoType1 = $value1['title'];
                    $optionTypeId1 = $value1['option_type_id'] ?? null;
                    if ($magentoType === $magentoType1) {
                        $magentoTypeToOptionTypeIdMap[$magentoType] = $optionTypeId1;
                    }
                }
            } else {
                $magentoTypeToOptionTypeIdMap[$magentoType] = $optionTypeId;
            }
        }

        ProductVariant::whereIn('id', $variantIds)->each(function ($variant) use ($magentoTypeToOptionTypeIdMap, $optionId) {
            $magentoType = $variant->magento_type;
            $optionTypeId = $magentoTypeToOptionTypeIdMap[$magentoType] ?? null;
            if ($optionTypeId !== null) {
                $variant->update(['is_created' => 1, 'magento_option_id' => $optionId, 'magento_option_type_id' => $optionTypeId]);
            }
        });
    }

    private function getCustomOptionFromMagento(string $productSKU): array
    {
        // Fetch Custom options based on option_id for newly created options update to product_variant:
        $this->productCustomOptions->ENDPOINT = config('services.magento.end_point');
        $result = $this->productCustomOptions->getCustomOptions($productSKU);

        if (! empty($result) && ! isset($result['message'])) {
            return $result[0];
        }

        return [];
    }

    private function checkDuplicateMagentoType(object $variant): bool
    {

        $variantFirstPrice = $this->getLowestPrice($variant->productVariantPrice);

        $countSameVariant = ProductVariant::where('is_approved', 1)
            ->where('magento_type', '=', $variant->magento_type)
            ->where('product_id', '=', $variant->product_id)->count();
        $flag = false;
        if ($countSameVariant > 1) {
            // Get the lowest price variant from duplicates
            $variantWithLowestPrice = ProductVariant::where('is_approved', 1)
                ->where('magento_type', '=', $variant->magento_type)
                ->where('product_id', '=', $variant->product_id)
                ->with(['productVariantPrice' => function ($query) {
                    $query->orderBy('price', 'asc');
                }])->get();
            foreach ($variantWithLowestPrice as $vlp) {
                $lowestPrice = $vlp->productVariantPrice->first()->price ?? 0;
                if ($lowestPrice) {
                    if ($variantFirstPrice > $lowestPrice) {
                        $flag = true;
                    }
                }

            }
        }

        return $flag;
    }

    private function createOption(object $variant, string $productSKU, int $isDefault, int $sortOrder, ?float $increasePrice): array
    {
        $title = 'Select your preferred branding method';
        //        $description = [];
        $option = [
            'product_sku' => $productSKU,
            'title' => $title,
            'type' => 'drop_down',
            'sort_order' => $sortOrder,
            'is_require' => true,
            'max_characters' => 0,
            'image_size_x' => 0,
            'image_size_y' => 0,
            'values' => $this->createOptionValues($variant, $isDefault, $increasePrice),
            'extension_attributes' => [
                'qty_input' => false,
                'one_time' => false,
                'div_class' => '',
                'mageworx_option_image_mode' => 0,
                'selection_limit_from' => 0,
                'selection_limit_to' => 0,
                'is_hidden' => false,
                'mageworx_option_gallery' => 0,
                'option_title_id' => '',
                'dependency_type' => '0',
                'sku_policy' => 'use_config',
                'is_swatch' => 0,
                'is_all_groups' => true,
                'is_all_websites' => true,
                'disabled' => false,
                'disabled_by_values' => false,
                //                'description' => json_encode($description, true),
            ],
        ];

        return $option;
    }

    private function createOptionValues(object $variant, int $isDefault, ?float $increasePrice): array
    {
        $values = [];
        $sortOrder = 1;
        $variantFirstPrice = $this->getLowestPrice($variant->productVariantPrice);
        $calculatedPrice = $increasePrice ? ($variantFirstPrice * $increasePrice) / 100 : 0;
        $variantFirstPrice = round(($variantFirstPrice + $calculatedPrice), 2);

        // No prices available then set 0 as of now
        if (! $variantFirstPrice) {
            $variantFirstPrice = 0;
        }

        $variantSubTitle = $variant->magento_type;
        $attributesParams = [
            'is_default' => $isDefault,
            'variant_tier_price' => $variant->productVariantPrice,
            'addition_tier_price' => [],
            'increase_price' => $increasePrice,
            'is_approved' => $variant->is_approved,
        ];
        $values[] = [
            'extension_attributes' => $this->createExtensionAttributes($attributesParams),
            'title' => $variantSubTitle,
            'sort_order' => $sortOrder,
            'price' => $variantFirstPrice,
            'price_type' => 'fixed',
        ];
        if ($variant->magento_option_type_id) {
            $values[0]['option_type_id'] = $variant->magento_option_type_id;
        }

        return $values;
    }

    private function createExtensionAttributes(array $attributesParams): array
    {
        $tierPrice = $this->getVariantTierPrices($attributesParams);

        return [
            'cost' => 0,
            'is_default' => $attributesParams['is_default'],
            'load_linked_product' => false,
            'qty_multiplier' => 0,
            'weight' => 0,
            'weight_type' => 'fixed',
            'dependency_type' => '0',
            'option_type_title_id' => '',
            'sku_is_valid' => false,
            'manage_stock' => false,
            'qty' => 0,
            'tier_price' => $tierPrice,
            'disabled' => $attributesParams['is_approved'] ? false : true,
        ];
    }

    private function getVariantTierPrices(array $attributesParams): string
    {
        $tierPrices = [];
        $increasePrice = $attributesParams['increase_price'];
        $variantPrices = json_decode($attributesParams['variant_tier_price'], true) ?? [];
        foreach ($variantPrices as $variantPrice) {
            $caculatedPrice = $increasePrice ? ($variantPrice['price'] * $increasePrice) / 100 : 0;
            $price = round(($variantPrice['price'] + $caculatedPrice), 2);
            $tierPrices[] = [
                'price' => $price,
                'customer_group_id' => 32000,
                'price_type' => 'fixed',
                'date_from' => '',
                'date_to' => '',
                'qty' => $variantPrice['qty'],
            ];
        }

        return json_encode($tierPrices, true);
    }

    private function getUniqueOptionTitleOptionArray(array $optionsArray, bool $isCreate = false): array
    {
        $finalOptionsArray = [];
        foreach ($optionsArray as &$item) {
            $titleMatched = false; // Initialize a flag to track if 'title' matches
            foreach ($finalOptionsArray as &$finalItem) {
                if (isset($item['option']['title']) && isset($finalItem['option']['title']) && $item['option']['title'] === $finalItem['option']['title']) {
                    // 'title' matches, append $item to $finalOptionsArray
                    foreach ($item['option']['values'] as $values) {
                        $finalItem['option']['values'][] = $values;
                    }

                    // Same variants id
                    $finalItem['option']['product_variant_id'] = $finalItem['option']['product_variant_id'] . ',' . $item['option']['product_variant_id'];
                    if (! $isCreate) {
                        if ($item['option']['magento_option_id']) {
                            $finalItem['option']['magento_option_id'] = $item['option']['magento_option_id'];
                            $finalItem['option']['option_id'] = $item['option']['option_id'];
                        }
                    }
                    $titleMatched = true;
                    break; // No need to continue searching
                }
            }

            if (! $titleMatched) {
                $finalOptionsArray[] = $item;
            }

        }

        return $finalOptionsArray;
    }

    private function createCustomOptionJobData(object $product, array $finalOptionsArray): void
    {
        // Count created custom options and set sort order properly
        $countOptions = ProductVariant::where('is_created', 1)
            ->where('product_id', $product->id)
            ->count();
        $sortOrderOption = $countOptions ? $countOptions + 1 : 1;
        foreach ($finalOptionsArray as $option) {
            // skip custom option for update, which type's already exist
            $duplicateVariantType = 0;
            if ($countOptions && $countOptions !== 0) { // it's true, means call for update
                $duplicateVariantType = ProductVariant::where('type', $option['option']['title'])
                    ->where('product_id', $product->id)
                    ->count();
            }
            if ($duplicateVariantType > 1) {
                $logData = [
                    'module_name' => 'Products',
                    'action' => 'createCustomOptions On Update',
                    'api_end_point' => $this->productCustomOptions->ENDPOINT,
                    'payload' => json_encode($option, true),
                    'response' => ''];
                GeneralLog::create($logData);

                continue;
            }
            //end

            $sortOrder = 1;
            // Loop through the "values" array and update the "sort_order" value by $sortOrder variable
            foreach ($option['option']['values'] as &$value) {
                $value['sort_order'] = $sortOrder;
                $sortOrder++;
            }
            $option['option']['sort_order'] = $sortOrderOption;
            $sortOrderOption++;
            MagentoCreateCustomOptionsJob::dispatch($option);
        }
    }

    private function getTotalPages(int $pageSize): int
    {
        $firstPageData = $this->getProducts->getProducts('', 1, 10);

        return ceil($firstPageData['total_count'] / $pageSize);

    }

    private function getMinimumSaleQty(int $productId): int
    {
        if (ProductVariantPrice::where('product_id', $productId)->exists()) {
            return ProductVariantPrice::where('product_id', $productId)
                ->get()
                ->min('qty');
        } else {
            return 1;
        }
    }

    private function checkPriceExist(int $productId): bool
    {
        if (ProductVariantPrice::where('product_id', $productId)->exists()) {
            $minPrice = ProductVariantPrice::where('product_id', $productId)
                ->get()
                ->min('price');

            return ($minPrice > 0) ? true : false;
        } else {
            return false;
        }
    }

    private function getCategoryLinks(int $categoryId, int $subCategoryId): array
    {
        //Fetch magento id based on $categoryId
        $categoryData = ProductType::select('magento_id')
            ->where('id', $categoryId)
            ->whereNotNull('magento_id')
            ->first();
        $subCategoryData = ProductTypeSub::select('magento_id')
            ->where('id', $subCategoryId)
            ->whereNotNull('magento_id')
            ->first();
        if ($categoryData && $subCategoryData) {
            return [
                [
                    'position' => 0,
                    'category_id' => (string) $categoryData->magento_id,
                ],
                [
                    'position' => 0,
                    'category_id' => (string) $subCategoryData->magento_id,
                ],
            ];
        } else {
            return [];
        }
    }

    private function getVariantsLeadTime(int $productId): ?string
    {
        if (ProductVariant::where('product_id', $productId)->exists()) {
            return ProductVariant::where('product_id', $productId)
                ->distinct()
                ->pluck('lead_time')
                ->implode(',');
        } else {
            return '';
        }
    }

    private function getFirstPrice(object $productVariantPrice): ?float
    {
        return collect($productVariantPrice)->pluck('price')->first();
    }

    private function getAdditionTierPrices(object $productVariantPrice, object $productAdditionPrice): string
    {
        $tierPrices = [];
        $variantPrices = collect(json_decode($productVariantPrice, true) ?? []);
        $additionPrices = json_decode($productAdditionPrice, true) ?? [];
        foreach ($additionPrices as $additionPrice) {
            // Use the where method to filter variantPrices based on quantity
            $matchingVariantPrices = $variantPrices->where('qty', $additionPrice['qty']);

            if (! $matchingVariantPrices->isEmpty()) {
                // Calculate the sum by adding the first matching variant price and the addition price
                $matchingVariantPrice = $matchingVariantPrices->first();
                $sumPrice = $matchingVariantPrice['price'] + $additionPrice['price'];
                $tierPrices[] = [
                    'price' => $sumPrice,
                    'customer_group_id' => 32000,
                    'price_type' => 'fixed',
                    'date_from' => '',
                    'date_to' => '',
                    'qty' => $additionPrice['qty'],
                ];
            }
        }

        return json_encode($tierPrices, true);
    }

    private function updateExistingProductColours(int $productId, string $colour, int $magentoId): array
    {
        // Direct append to product after fetching data
        $productData = Product::select('appa_colours', 'magento_colours_ids')->whereId($productId)->first();
        $updateProductColours = [];
        if ($productData) {
            $appaColours = $productData->appa_colours;
            $magentoColoursIds = $productData->magento_colours_ids;
            if ($appaColours) {
                $appaColoursArr = explode(',', $appaColours);
                $magentoColoursIdsArr = explode(',', $magentoColoursIds);

                if (! in_array($colour, $appaColoursArr)) {
                    $appaColoursArr[] = $colour;
                }
                if (! in_array($magentoId, $magentoColoursIdsArr)) {
                    $magentoColoursIdsArr[] = $magentoId;
                }
                $appaColours = implode(',', $appaColoursArr);
                $magentoColoursIds = implode(',', $magentoColoursIdsArr);
            } else {
                $appaColours = $colour;
                $magentoColoursIds = $magentoId;
            }
            $updateProductColours = ['appa_colours' => $appaColours, 'magento_colours_ids' => $magentoColoursIds];
        }

        return $updateProductColours;
    }
}
