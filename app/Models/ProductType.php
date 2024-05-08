<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\ProductType
 *
 * @property int $id
 * @property string|null $parent_id
 * @property string|null $parent_name
 * @property string|null $sub_id
 * @property string|null $sub_name
 * @property string|null $promodata_id Insert id of product type API, Here id in string
 * @property int|null $magento_id Update last inserted id of magento
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|ProductType newModelQuery()
 * @method static Builder|ProductType newQuery()
 * @method static Builder|ProductType query()
 * @method static Builder|ProductType whereCreatedAt($value)
 * @method static Builder|ProductType whereId($value)
 * @method static Builder|ProductType whereMagentoId($value)
 * @method static Builder|ProductType whereParentId($value)
 * @method static Builder|ProductType whereParentName($value)
 * @method static Builder|ProductType wherePromodataId($value)
 * @method static Builder|ProductType whereSubId($value)
 * @method static Builder|ProductType whereSubName($value)
 * @method static Builder|ProductType whereUpdatedAt($value)
 *
 * @property string|null $type_id
 * @property string|null $name
 * @property-read Collection<int, Product> $products
 * @property-read int|null $products_count
 *
 * @method static Builder|ProductType whereName($value)
 * @method static Builder|ProductType whereTypeId($value)
 *
 * @property-read Collection<int, ProductTypeSub> $productTypeSub
 * @property-read int|null $product_type_sub_count
 *
 * @mixin Eloquent
 * @mixin \Eloquent
 */
class ProductType extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'type_id',
        'name',
        'promodata_id',
        'magento_id',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function productTypeSub(): HasMany
    {
        return $this->hasMany(ProductTypeSub::class);
    }
}
