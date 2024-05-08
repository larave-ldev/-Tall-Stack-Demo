<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\ProductExtraInfo
 *
 * @property int $id
 * @property int $product_id
 * @property string|null $name
 * @property string|null $detail
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|ProductExtraInfo newModelQuery()
 * @method static Builder|ProductExtraInfo newQuery()
 * @method static Builder|ProductExtraInfo query()
 * @method static Builder|ProductExtraInfo whereCreatedAt($value)
 * @method static Builder|ProductExtraInfo whereDetail($value)
 * @method static Builder|ProductExtraInfo whereId($value)
 * @method static Builder|ProductExtraInfo whereName($value)
 * @method static Builder|ProductExtraInfo whereProductId($value)
 * @method static Builder|ProductExtraInfo whereUpdatedAt($value)
 *
 * @property-read Collection<int, Product> $products
 * @property-read int|null $products_count
 *
 * @mixin Eloquent
 * @mixin \Eloquent
 */
class ProductExtraInfo extends Model
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
        'name',
        'detail',
    ];

    public function products(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
