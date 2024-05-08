<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\ProductColourSupplierText
 *
 * @property int $id
 * @property int $product_id
 * @property string|null $name
 * @property string|null $details
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|ProductColourSupplierText newModelQuery()
 * @method static Builder|ProductColourSupplierText newQuery()
 * @method static Builder|ProductColourSupplierText query()
 * @method static Builder|ProductColourSupplierText whereCreatedAt($value)
 * @method static Builder|ProductColourSupplierText whereDetails($value)
 * @method static Builder|ProductColourSupplierText whereId($value)
 * @method static Builder|ProductColourSupplierText whereName($value)
 * @method static Builder|ProductColourSupplierText whereProductId($value)
 * @method static Builder|ProductColourSupplierText whereUpdatedAt($value)
 *
 * @property string|null $detail
 *
 * @method static Builder|ProductColourSupplierText whereDetail($value)
 *
 * @property-read Product $product
 *
 * @mixin Eloquent
 * @mixin \Eloquent
 */
class ProductColourSupplierText extends Model
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
