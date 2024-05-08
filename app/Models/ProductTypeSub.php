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
 * App\Models\ProductTypeSub
 *
 * @property int $id
 * @property int|null $product_type_id
 * @property string|null $sub_id
 * @property string|null $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|ProductTypeSub newModelQuery()
 * @method static Builder|ProductTypeSub newQuery()
 * @method static Builder|ProductTypeSub query()
 * @method static Builder|ProductTypeSub whereCreatedAt($value)
 * @method static Builder|ProductTypeSub whereId($value)
 * @method static Builder|ProductTypeSub whereName($value)
 * @method static Builder|ProductTypeSub whereProductTypeId($value)
 * @method static Builder|ProductTypeSub whereSubId($value)
 * @method static Builder|ProductTypeSub whereUpdatedAt($value)
 *
 * @property-read ProductType|null $productType
 * @property-read Collection<int, Product> $products
 * @property-read int|null $products_count
 *
 * @mixin Eloquent
 *
 * @property int|null $magento_id Update last inserted id of magento categories
 *
 * @method static Builder|ProductTypeSub whereMagentoId($value)
 *
 * @mixin \Eloquent
 */
class ProductTypeSub extends Model
{
    use HasFactory;

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_type_id',
        'sub_id',
        'name',
    ];

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
