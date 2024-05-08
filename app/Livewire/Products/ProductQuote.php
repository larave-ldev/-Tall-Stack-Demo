<?php

namespace App\Livewire\Products;

use App\Mail\SendProductQuotationMail;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CommonService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class ProductQuote extends Component
{
    public array $unDecoratedData = [];
    public array $defaultUnDecoratedData = [];
    public array $productVariant = [];
    public array $buyTableData = [];
    public array $sellTableData = [];
    public array $variantPrices = [];
    public array $calculateBuyData = [];
    public array $productDetails = [];
    public int $markupQty = 0;
    public float $markupPercentage = 60;
    public float $defaultSetup = 50;
    public string $email = '';

    public function mount(int $productId, CommonService $commonService): void
    {
        if ($productId) {
            $this->initializeProductData($productId, $commonService);
        }
    }

    public function render(): Factory|\Illuminate\Foundation\Application|View|Application
    {
        return view('livewire.products.product-quote');
    }

    public function changeOption($value, $price, $setup, $variantId, $desc): void
    {
        $this->calculateBuyData($value, $price, $setup, $variantId, $desc);
    }

    public function changeMarkup($qty, $markupPercentage): void
    {
        $this->calculateMarkupData($qty, $markupPercentage);
    }

    public function changeSetupValue($setup): void
    {
        $this->defaultSetup = $setup;
        $this->calculateMarkupData($this->markupQty, $this->markupPercentage);
    }

    public function emailProductQuotation(): void
    {

        $this->validate([
            'email' => 'required|email',
        ]);

        Mail::to($this->email)->send(new SendProductQuotationMail(['productDetails' => $this->productDetails, 'unDecoratedData' => $this->unDecoratedData, 'calculateBuyData' => $this->calculateBuyData, 'sellTableData' => $this->sellTableData]));
        $this->reset('email');
        $this->dispatch('close-modal', id: 'custom-modal-handle');
        Notification::make()
            ->title('Price quotation email has been sent successfully.')
            ->success()
            ->send();
    }

    protected function initializeProductData(int $productId, CommonService $commonService): void
    {
        $this->resetProperties();

        $productData = Product::select(['code', 'name', 'description', 'downloaded_hero_image', 'promodata_id'])->where('id', $productId)->first();
        if ($productData) {
            $this->setProductDetails($productData, $commonService);
        }

        $productVariants = ProductVariant::with('productVariantPrice')
            ->where('product_id', $productId)
            ->get();

        foreach ($productVariants as $productVariant) {
            $type = ucfirst($productVariant->type);
            if (in_array($type, ['Undecorated', 'Un-branded', 'Unprinted'])) {
                $this->initializeUndecoratedVariantData($productVariant);
            } else {
                $this->initializeDecoratedVariantData($productVariant);
            }
        }

        if (! $this->unDecoratedData) {
            return;
        }

        // Calculating the Buy Table Data
        foreach ($this->unDecoratedData['prices'] as $buy) {
            $this->buyTableData[$buy['qty']] = ['qty' => $buy['qty'], 'price' => $buy['price'], 'setup' => 0];
        }
        $this->defaultUnDecoratedData = $this->unDecoratedData;

        // Calculating the Markup on buy Table Data
        $this->calculateMarkupData($this->markupQty, $this->markupPercentage);
    }

    protected function resetProperties(): void
    {
        $this->markupQty = 0;
        $this->markupPercentage = 60;
        $this->unDecoratedData = [];
        $this->defaultUnDecoratedData = [];
        $this->productVariant = [];
        $this->buyTableData = [];
        $this->sellTableData = [];
        $this->variantPrices = [];
        $this->calculateBuyData = [];
        $this->productDetails = [];
    }

    protected function setProductDetails($productData, $commonService): void
    {
        $sku = $commonService->createSKU($productData->code, $productData->promodata_id);
        $this->productDetails = ['sku' => $sku, 'name' => $productData->name, 'description' => $productData->description, 'hero_image' => $productData->downloaded_hero_image];
    }

    protected function initializeUndecoratedVariantData($productVariant): void
    {
        $this->unDecoratedData = [
            'markup' => [],
            'setup' => 50,
            'description' => $productVariant->description,
            'prices' => $productVariant->productVariantPrice,
            'lead' => $productVariant->lead_time ?? '1D',
        ];

        foreach ($productVariant->productVariantPrice as $price) {
            $this->unDecoratedData['markup'][] = ['qty' => $price['qty'], 'markup' => 60];
        }
    }

    protected function initializeDecoratedVariantData($productVariant): void
    {
        if (! $productVariant->type) {
            return;
        }

        $prices = $productVariant->productVariantPrice;
        $this->variantPrices[] = $prices;
        $minPrice = $prices->min('price');

        $this->productVariant[] = [
            'id' => $productVariant->id,
            'description' => $productVariant->description,
            'price' => $minPrice,
            'setup' => $productVariant->setup,
            'lead' => preg_match('!\d+!', $productVariant->lead_time, $matches) ? $matches[0] . 'D' : '',
            'lead_desc' => $productVariant->lead_time ?? '',
        ];
    }

    protected function calculateMarkupData($qty = 0, $markupPercentage = 0): void
    {
        if (empty($this->unDecoratedData['markup'])) {
            return;
        }
        if (! empty($this->sellTableData)) {
            foreach ($this->sellTableData as $markupArr) {
                if ($qty == $markupArr['qty']) {
                    $setup = $this->calculateSetup($this->buyTableData[$markupArr['qty']]['setup'], $this->buyTableData[$markupArr['qty']]['setup'], $this->defaultSetup);
                    $price = $this->calculateMarkupPrice($markupArr, $markupPercentage);
                    $sub_total = ($markupArr['qty'] * $price) + $setup;
                    $withGST = $this->calculateGST($sub_total);
                    $grandTotalPrice = number_format($withGST, 2);
                    $this->sellTableData[$markupArr['qty']] = ['qty' => $markupArr['qty'], 'price' => $price, 'setup' => $setup, 'delivery' => 0, 'applied_markup' => $markupPercentage,
                        'grand_total_price' => $grandTotalPrice];
                } else {
                    $setup = $this->calculateSetup($this->buyTableData[$markupArr['qty']]['setup'], $this->buyTableData[$markupArr['qty']]['setup'], $this->defaultSetup);
                    $price = $this->calculateMarkupPrice($markupArr, $markupArr['applied_markup']);
                    $sub_total = ($markupArr['qty'] * $price) + $setup;
                    $withGST = $this->calculateGST($sub_total);
                    $grandTotalPrice = number_format($withGST, 2);
                    $this->sellTableData[$markupArr['qty']] = ['qty' => $markupArr['qty'], 'price' => $price, 'setup' => $setup, 'delivery' => 0, 'applied_markup' => $markupArr['applied_markup'],
                        'grand_total_price' => $grandTotalPrice];
                }
            }
        } else {
            foreach ($this->unDecoratedData['markup'] as $markupArr) {
                $setup = $this->buyTableData[$markupArr['qty']]['setup'] + ($this->buyTableData[$markupArr['qty']]['setup'] * $this->defaultSetup) / 100;
                $price = $this->calculateMarkupPrice($markupArr, $markupPercentage);
                $sub_total = ($markupArr['qty'] * $price) + $setup;
                $withGST = $this->calculateGST($sub_total);
                $grandTotalPrice = number_format($withGST, 2);
                $this->sellTableData[$markupArr['qty']] = ['qty' => $markupArr['qty'], 'price' => $price, 'setup' => $setup, 'delivery' => 0, 'applied_markup' => $markupArr['markup'],
                    'grand_total_price' => $grandTotalPrice];

            }
        }
    }

    protected function calculateSetup($buySetupValue, $markupSetupValue, $changedSetupValue): float
    {
        return $buySetupValue + ($markupSetupValue * $changedSetupValue) / 100;
    }

    protected function calculateMarkupPrice($markupArr, $markupPercentage): float
    {
        $price = $this->buyTableData[$markupArr['qty']]['price'];
        $price += ($price * $markupPercentage) / 100;

        return number_format($price, 2);

    }

    protected function calculateGST(float $value): float
    {
        // Define GST rate (e.g., 18%)
        $gstRate = config('promodata.current_gst');

        // Calculate GST amount
        return $value + (($value * $gstRate) / 100);
    }

    protected function calculateBuyData($value = '', $price = 0, $setup = 0, $variantId = '', $desc = ''): void
    {
        // Exit early if variantId is empty
        if (! $variantId) {
            return;
        }

        if (! $this->unDecoratedData) {
            return;
        }

        $buyData = $this->unDecoratedData['prices'];
        // Convert inputs to appropriate types
        $optionPrice = (float) $price;
        $optionSetup = (float) $setup;

        if ($value == 1) {
            $this->calculateBuyData[] = [
                'variant_id' => $variantId,
                'price' => $optionPrice,
                'setup' => $optionSetup,
                'description' => $desc,
            ];

            foreach ($this->calculateBuyData as $item) {
                $price = $item['price'];
                $setup = $item['setup'];
                foreach ($buyData as $productVariantPrice) {
                    $productVariantPrice['price'] += $price;
                    $productVariantPrice['setup'] += $setup;

                    $this->buyTableData[$productVariantPrice['qty']] = ['qty' => $productVariantPrice['qty'], 'price' => $productVariantPrice['price'], 'setup' => $productVariantPrice['setup']];
                }
            }
        } elseif ($value == 0) {
            $existingVariantIndex = null;
            foreach ($this->calculateBuyData as $index => $item) {
                if ($item['variant_id'] == $variantId) {
                    $existingVariantIndex = $index;
                    foreach ($this->buyTableData as $buy) {
                        $buy['price'] = $buy['price'] - $price;
                        $buy['setup'] = $buy['setup'] - $setup;
                        $this->buyTableData[$buy['qty']] = ['qty' => $buy['qty'], 'price' => $buy['price'], 'setup' => $buy['setup']];
                    }
                    break;
                }
            }

            // If the variant_id exists, remove the array
            if ($existingVariantIndex !== null) {
                unset($this->calculateBuyData[$existingVariantIndex]);
                // Re-index the array after removing an element
                $this->calculateBuyData = array_values($this->calculateBuyData);
            }
        } else {

            foreach ($buyData as $buy) {
                $buy['price'] = $buy['price'] - $price;
                $buy['setup'] = $buy['setup'] - $setup;
                $this->buyTableData[$buy['qty']] = ['qty' => $buy['qty'], 'price' => $buy['price'], 'setup' => $buy['setup']];
            }
        }

        $this->calculateMarkupData($this->markupQty, $this->markupPercentage);
    }
}
