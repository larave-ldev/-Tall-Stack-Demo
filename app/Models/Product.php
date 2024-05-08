<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\Product
 *
 * @property int $id
 * @property int $supplier_id
 * @property int|null $product_type_id
 * @property string|null $country
 * @property string|null $data_source
 * @property int $discontinued
 * @property int $can_check_stock
 * @property string|null $discontinued_at
 * @property string|null $first_listed_at
 * @property string|null $last_changed_at
 * @property string|null $price_currencies
 * @property string|null $prices_changed_at
 * @property string|null $discontinued_reason
 * @property string|null $source_data_changed_at
 * @property string|null $name
 * @property string|null $code
 * @property string|null $hero_image
 * @property string|null $downloaded_hero_image
 * @property int|null $min_qty
 * @property string|null $display_prices
 * @property string|null $description
 * @property string|null $supplier_brand
 * @property string|null $supplier_label
 * @property string|null $supplier_catalogue
 * @property string|null $supplier_website_page
 * @property string|null $appa_attributes
 * @property string|null $appa_product_type
 * @property string|null $supplier_category
 * @property string|null $supplier_subcategory
 * @property int|null $promodata_id Insert id of products API
 * @property string|null $promodata_created_at Insert created_at of products API
 * @property string|null $promodata_updated_at Insert updated_at of products API
 * @property int|null $magento_id Update last inserted id of magento
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|Product newModelQuery()
 * @method static Builder|Product newQuery()
 * @method static Builder|Product query()
 * @method static Builder|Product whereAppaAttributes($value)
 * @method static Builder|Product whereAppaProductType($value)
 * @method static Builder|Product whereCanCheckStock($value)
 * @method static Builder|Product whereCode($value)
 * @method static Builder|Product whereCountry($value)
 * @method static Builder|Product whereCreatedAt($value)
 * @method static Builder|Product whereDataSource($value)
 * @method static Builder|Product whereDescription($value)
 * @method static Builder|Product whereDiscontinued($value)
 * @method static Builder|Product whereDiscontinuedAt($value)
 * @method static Builder|Product whereDiscontinuedReason($value)
 * @method static Builder|Product whereDisplayPrices($value)
 * @method static Builder|Product whereDownloadedHeroImage($value)
 * @method static Builder|Product whereFirstListedAt($value)
 * @method static Builder|Product whereHeroImage($value)
 * @method static Builder|Product whereId($value)
 * @method static Builder|Product whereLastChangedAt($value)
 * @method static Builder|Product whereMagentoId($value)
 * @method static Builder|Product whereMinQty($value)
 * @method static Builder|Product whereName($value)
 * @method static Builder|Product wherePriceCurrencies($value)
 * @method static Builder|Product wherePricesChangedAt($value)
 * @method static Builder|Product whereProductTypeId($value)
 * @method static Builder|Product wherePromodataCreatedAt($value)
 * @method static Builder|Product wherePromodataId($value)
 * @method static Builder|Product wherePromodataUpdatedAt($value)
 * @method static Builder|Product whereSourceDataChangedAt($value)
 * @method static Builder|Product whereSupplierBrand($value)
 * @method static Builder|Product whereSupplierCatalogue($value)
 * @method static Builder|Product whereSupplierCategory($value)
 * @method static Builder|Product whereSupplierId($value)
 * @method static Builder|Product whereSupplierLabel($value)
 * @method static Builder|Product whereSupplierSubcategory($value)
 * @method static Builder|Product whereSupplierWebsitePage($value)
 * @method static Builder|Product whereUpdatedAt($value)
 *
 * @property int|null $product_type_sub_id
 * @property-read ProductType|null $productType
 *
 * @method static Builder|Product whereProductTypeSubId($value)
 *
 * @property-read Collection<int, ProductAddition> $additions
 * @property-read int|null $additions_count
 * @property-read Collection<int, ProductExtraInfo> $extraInfos
 * @property-read int|null $extra_infos_count
 * @property-read ProductTypeSub|null $productTypeSub
 * @property-read Supplier $supplier
 * @property-read Collection<int, ProductVariant> $variants
 * @property-read int|null $variants_count
 * @property-read Collection<int, ProductMedia> $medias
 * @property-read int|null $medias_count
 * @property-read Collection<int, ProductColourList> $colourLists
 * @property-read int|null $colour_lists_count
 * @property-read Collection<int, ProductColourSupplierText> $colourSupplierTexts
 * @property-read int|null $colour_supplier_texts_count
 * @property string|null $appa_colours
 * @property string|null $magento_colours
 * @property string|null $magento_colours_ids
 *
 * @method static Builder|Product whereAppaColours($value)
 * @method static Builder|Product whereMagentoColours($value)
 * @method static Builder|Product whereMagentoColoursIds($value)
 *
 * @property int|null $is_image_uploaded
 *
 * @method static Builder|Product whereIsImageUploaded($value)
 *
 * @property int|null $is_uploaded This flag for media is uploaded on magento or not
 *
 * @method static Builder|Product whereIsUploaded($value)
 *
 * @property float|null $custom_option_price Custom option price apply on variant base prices in percentage
 *
 * @method static Builder|Product whereCustomOptionPrice($value)
 *
 * @property float|null $magento_price It will store the lowest price of variant
 *
 * @method static Builder|Product whereMagentoPrice($value)
 *
 * @property int|null $magento_status 1: Enable, 2: Disable
 * @property int $is_enabled 0: Disable, 1: Enable, product will post only when set enabled
 *
 * @method static Builder|Product whereIsEnabled($value)
 * @method static Builder|Product whereMagentoStatus($value)
 *
 * @property float|null $magento_setup_cost
 * @property float|null $magento_delivery_price
 *
 * @method static Builder|Product whereMagentoDeliveryPrice($value)
 * @method static Builder|Product whereMagentoSetupCost($value)
 *
 * @property int $is_archived For managing archive products
 *
 * @method static Builder|Product whereIsArchived($value)
 *
 * @mixin Eloquent
 * @mixin Eloquent
 *
 * @property float|null $magento_calculated_price It will store the lowest price of variant with applied custom price
 * @property int|null $magento_img_id
 *
 * @method static Builder|Product whereMagentoCalculatedPrice($value)
 * @method static Builder|Product whereMagentoImgId($value)
 *
 * @mixin \Eloquent
 */
class Product extends Model
{
    use HasFactory;

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'supplier_id',
        'product_type_id',
        'product_type_sub_id',
        'country',
        'data_source',
        'discontinued',
        'can_check_stock',
        'discontinued_at',
        'first_listed_at',
        'last_changed_at',
        'price_currencies',
        'prices_changed_at',
        'discontinued_reason',
        'source_data_changed_at',
        'name',
        'code',
        'hero_image',
        'downloaded_hero_image',
        'min_qty',
        'display_prices',
        'description',
        'supplier_brand',
        'supplier_label',
        'supplier_catalogue',
        'supplier_website_page',
        'appa_attributes',
        'appa_product_type',
        'supplier_category',
        'supplier_subcategory',
        'promodata_id',
        'magento_id',
        'appa_colours',
        'magento_colours',
        'magento_colours_ids',
        'is_uploaded',
        'magento_status',
        'is_enabled',
        'is_archived',
        'magento_img_id',
    ];

    public static function getProductCountsByMagentoStatus()
    {
        return Product::selectRaw('SUM(magento_status = 1) as enabled_count, SUM(magento_status = 2) as disabled_count, SUM(magento_status IN (1, 2)) as total_enabled_disabled_count')
            ->first();

    }

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }

    public function productTypeSub(): BelongsTo
    {
        return $this->belongsTo(ProductTypeSub::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function additions(): HasMany
    {
        return $this->hasMany(ProductAddition::class);
    }

    public function extraInfos(): HasMany
    {
        return $this->hasMany(ProductExtraInfo::class);
    }

    public function medias(): HasMany
    {
        return $this->hasMany(ProductMedia::class);
    }

    public function colourLists(): HasMany
    {
        return $this->hasMany(ProductColourList::class);
    }

    public function colourSupplierTexts(): HasMany
    {
        return $this->hasMany(ProductColourSupplierText::class);
    }

    protected function downloadedHeroImage(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? asset($value) : null,
        );
    }
}
