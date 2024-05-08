<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\ProductVariant
 *
 * @property int $id
 * @property int $product_id
 * @property string|null $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|ProductVariant newModelQuery()
 * @method static Builder|ProductVariant newQuery()
 * @method static Builder|ProductVariant query()
 * @method static Builder|ProductVariant whereCreatedAt($value)
 * @method static Builder|ProductVariant whereId($value)
 * @method static Builder|ProductVariant whereName($value)
 * @method static Builder|ProductVariant whereProductId($value)
 * @method static Builder|ProductVariant whereUpdatedAt($value)
 *
 * @property string|null $key
 * @property mixed|null $tags
 * @property string|null $type
 * @property int|null $setup
 * @property int $indent
 * @property string|null $currency
 * @property string|null $lead_time
 * @property string|null $description
 * @property int $undecorated
 *
 * @method static Builder|ProductVariant whereCurrency($value)
 * @method static Builder|ProductVariant whereDescription($value)
 * @method static Builder|ProductVariant whereIndent($value)
 * @method static Builder|ProductVariant whereKey($value)
 * @method static Builder|ProductVariant whereLeadTime($value)
 * @method static Builder|ProductVariant whereSetup($value)
 * @method static Builder|ProductVariant whereTags($value)
 * @method static Builder|ProductVariant whereType($value)
 * @method static Builder|ProductVariant whereUndecorated($value)
 *
 * @property int|null $free
 *
 * @method static Builder|ProductVariant whereFree($value)
 *
 * @property-read Product $product
 * @property-read Collection<int, ProductAddition> $productAddition
 * @property-read int|null $product_addition_count
 * @property-read Collection<int, ProductVariantPrice> $productVariantPrice
 * @property-read int|null $product_variant_price_count
 * @property int|null $magento_option_id
 *
 * @method static Builder|ProductVariant whereMagentoOptionId($value)
 *
 * @property int|null $is_created This flag for custom option is created on magento or not
 *
 * @method static Builder|ProductVariant whereIsCreated($value)
 *
 * @property int|null $pd_id Primary decoration id as a foreign key
 *
 * @method static Builder|ProductVariant wherePdId($value)
 *
 * @property string|null $magento_type Custom name of type
 * @property int $is_approved Approved variants will be created on magento custom options only
 * @property string|null $magento_updated_at If updated, that means it will take same day in cron and update on magento
 *
 * @method static Builder|ProductVariant whereIsApproved($value)
 * @method static Builder|ProductVariant whereMagentoType($value)
 * @method static Builder|ProductVariant whereMagentoUpdatedAt($value)
 *
 * @property int|null $supplier_id
 * @property-read Supplier|null $supplier
 *
 * @method static Builder|ProductVariant whereSupplierId($value)
 *
 * @property int|null $magento_option_type_id
 *
 * @method static Builder|ProductVariant whereMagentoOptionTypeId($value)
 *
 * @property int $is_archived For managing archive decorations
 *
 * @method static Builder|ProductVariant whereIsArchived($value)
 *
 * @mixin Eloquent
 * @mixin \Eloquent
 */
class ProductVariant extends Model
{
    use HasFactory;

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'supplier_id',
        'key',
        'tags',
        'type',
        'setup',
        'indent',
        'currency',
        'lead_time',
        'description',
        'undecorated',
        'magento_option_id',
        'magento_option_type_id',
        'is_created',
        'magento_type',
        'is_approved',
        'magento_updated_at',
        'is_archived',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productAddition(): HasMany
    {
        return $this->hasMany(ProductAddition::class);
    }

    public function productVariantPrice(): HasMany
    {
        return $this->hasMany(ProductVariantPrice::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
