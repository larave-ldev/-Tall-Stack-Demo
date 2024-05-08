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
 * App\Models\ProductAddition
 *
 * @property int $id
 * @property int $product_id
 * @property int $product_variant_id
 * @property string|null $key
 * @property string|null $type
 * @property int|null $setup
 * @property string|null $currency
 * @property string|null $lead_time
 * @property string|null $description
 * @property int $undecorated
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|ProductAddition newModelQuery()
 * @method static Builder|ProductAddition newQuery()
 * @method static Builder|ProductAddition query()
 * @method static Builder|ProductAddition whereCreatedAt($value)
 * @method static Builder|ProductAddition whereCurrency($value)
 * @method static Builder|ProductAddition whereDescription($value)
 * @method static Builder|ProductAddition whereId($value)
 * @method static Builder|ProductAddition whereKey($value)
 * @method static Builder|ProductAddition whereLeadTime($value)
 * @method static Builder|ProductAddition whereProductId($value)
 * @method static Builder|ProductAddition whereProductVariantId($value)
 * @method static Builder|ProductAddition whereSetup($value)
 * @method static Builder|ProductAddition whereType($value)
 * @method static Builder|ProductAddition whereUndecorated($value)
 * @method static Builder|ProductAddition whereUpdatedAt($value)
 *
 * @property-read Product $product
 * @property-read Collection<int, ProductAdditionPrice> $productAdditionPrice
 * @property-read int|null $product_addition_price_count
 * @property-read ProductVariant $productVariant
 *
 * @mixin Eloquent
 * @mixin \Eloquent
 */
class ProductAddition extends Model
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
        'product_variant_id',
        'key',
        'type',
        'setup',
        'currency',
        'lead_time',
        'description',
        'undecorated',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function productAdditionPrice(): HasMany
    {
        return $this->hasMany(ProductAdditionPrice::class);
    }
}
