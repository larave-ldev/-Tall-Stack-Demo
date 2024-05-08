<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\ProductAdditionPrice
 *
 * @property int $id
 * @property int $product_id
 * @property int $product_addition_id
 * @property int $qty
 * @property float $price
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|ProductAdditionPrice newModelQuery()
 * @method static Builder|ProductAdditionPrice newQuery()
 * @method static Builder|ProductAdditionPrice query()
 * @method static Builder|ProductAdditionPrice whereCreatedAt($value)
 * @method static Builder|ProductAdditionPrice whereId($value)
 * @method static Builder|ProductAdditionPrice wherePrice($value)
 * @method static Builder|ProductAdditionPrice whereProductAdditionId($value)
 * @method static Builder|ProductAdditionPrice whereProductId($value)
 * @method static Builder|ProductAdditionPrice whereQty($value)
 * @method static Builder|ProductAdditionPrice whereUpdatedAt($value)
 *
 * @property-read ProductAddition $productAddition
 *
 * @mixin Eloquent
 * @mixin \Eloquent
 */
class ProductAdditionPrice extends Model
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
        'product_addition_id',
        'qty',
        'price',
    ];

    public function productAddition(): BelongsTo
    {
        return $this->belongsTo(ProductAddition::class);
    }
}
