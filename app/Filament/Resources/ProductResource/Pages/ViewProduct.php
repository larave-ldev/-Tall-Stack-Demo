<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Jobs\MagentoUploadImagesJob;
use App\Models\Product;
use App\Models\ProductColourList;
use App\Models\ProductMedia;
use App\Services\CommonService;
use App\Services\Magento\ProductsServiceMagento;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    public Model|int|string|null $record;

    protected ?string $heading = '';

    public function getHeading(): string
    {
        return $this->record->name ?? '';
    }

    protected function getHeaderActions(): array
    {

        return [
            Action::make('promodata_id')->label('Latest Update From Promodata')
                ->requiresConfirmation()->action(function (Product $record) {
                    Artisan::call('promodata-single-latest-product-update ' . $record->promodata_id);
                    sleep(3);
                    Notification::make('update_product')
                        ->title('Product has been updated successfully.')
                        ->success()
                        ->send();
                }),
            Action::make('id')->label('Update on Magento')
                ->visible(fn (Product $record) => $record->magento_id && $record->supplier->is_sync_on_magento)
                ->requiresConfirmation()->action(function (Product $record) {
                    Artisan::call('magento-single-product-update ' . $record->id);

                }),
            Action::make('create_product')->label('Create On Magento')
                ->color('success')
                ->visible(fn (Product $record) => $record->is_enabled && ! $record->magento_id && $record->name != '' && $record->supplier->is_sync_on_magento)
                ->requiresConfirmation()->action(function (Product $record, ProductsServiceMagento $productsServiceMagento, CommonService $commonService) {
                    $productData['product'] = $productsServiceMagento->setProductPostData($record);

                    // Create single product on magento
                    $isCreated = $productsServiceMagento->postSingleProduct($productData);

                    if ($isCreated) {
                        $sku = $commonService->createSKU($record->code, $record->promodata_id);

                        // Fetch product details
                        $product = Product::with(
                            ['supplier', 'variants'],
                            'variants.productVariantPrice',
                        )
                            ->withCount([
                                'variants' => function ($query) {
                                    $query->whereNotNull('magento_option_id');
                                },
                            ])
                            ->where('products.name', '!=', '')
                            ->whereNotNull('products.magento_id')
                            ->whereHas('supplier', function ($query) {
                                // Get product only where is_sync_on_magento is true
                                $query->where('is_sync_on_magento', true);
                            })
                            ->where('products.id', $record->id)
                            ->first();

                        // Process custom options only if variants are not present
                        if ($product->variants_count === 0) {
                            $productsServiceMagento->processCustomOptionsData($product);
                        }

                        // Upload hero image
                        if ($record->is_uploaded === 0 && $record->downloaded_hero_image) {
                            $productMediaData = $productsServiceMagento->getMediaGalleryEntries($record?->downloaded_hero_image, true, '');
                            if (! empty($productMediaData)) {
                                MagentoUploadImagesJob::dispatch($record->id, 'Product', $sku, $productMediaData);
                            }
                        }

                        // Upload other images
                        $productMedias = ProductMedia::select('id', 'downloaded_url')
                            ->where('type', '=', 'image')
                            ->where('is_uploaded', '=', 0)
                            ->whereNotNull('downloaded_url')
                            ->where('product_id', $record->id)
                            ->get();
                        if ($productMedias) {
                            foreach ($productMedias as $productMedia) {
                                $productMediaData = $productsServiceMagento->getMediaGalleryEntries($productMedia?->downloaded_url, false, '');
                                if (! empty($productMediaData)) {
                                    MagentoUploadImagesJob::dispatch($productMedia->id, 'ProductMedia', $sku, $productMediaData);
                                }
                            }
                        }

                        // Upload colour images
                        $productColourLists = ProductColourList::select('id', 'downloaded_image')
                            ->where('is_uploaded', '=', 0)
                            ->whereNotNull('downloaded_image')
                            ->where('product_id', $record->id)
                            ->get();

                        if ($productColourLists) {
                            foreach ($productColourLists as $productColourList) {
                                $productMediaData = $productsServiceMagento->getMediaGalleryEntries($productColourList?->downloaded_image, false, '');
                                if (! empty($productMediaData)) {
                                    MagentoUploadImagesJob::dispatch($productColourList->id, 'ProductColourList', $sku, $productMediaData);
                                }
                            }
                        }
                        sleep(3);

                        Notification::make('create_product_notification')
                            ->title('Product has been created successfully on Magento.')
                            ->body('The images will be uploaded in a few minutes')
                            ->success()
                            ->send();
                    }

                }),
        ];
    }
}
