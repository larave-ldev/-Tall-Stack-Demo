<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\ProductVariantPrice
 *
 * @property int $id
 * @property int $product_id
 * @property int $product_variant_id
 * @property int $qty
 * @property float $price
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|ProductVariantPrice newModelQuery()
 * @method static Builder|ProductVariantPrice newQuery()
 * @method static Builder|ProductVariantPrice query()
 * @method static Builder|ProductVariantPrice whereCreatedAt($value)
 * @method static Builder|ProductVariantPrice whereId($value)
 * @method static Builder|ProductVariantPrice wherePrice($value)
 * @method static Builder|ProductVariantPrice whereProductId($value)
 * @method static Builder|ProductVariantPrice whereProductVariantId($value)
 * @method static Builder|ProductVariantPrice whereQty($value)
 * @method static Builder|ProductVariantPrice whereUpdatedAt($value)
 *
 * @property-read ProductVariant $productVariant
 *
 * @mixin Eloquent
 * @mixin \Eloquent
 */
class ProductVariantPrice extends Model
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
        'qty',
        'price',
    ];

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
