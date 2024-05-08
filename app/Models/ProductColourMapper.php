<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ProductColourMapper
 *
 * @property int $id
 * @property string|null $product_colour
 * @property int|null $magento_colour_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\MagentoColour|null $magentoColour
 *
 * @method static \Illuminate\Database\Eloquent\Builder|ProductColourMapper newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductColourMapper newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductColourMapper query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductColourMapper whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductColourMapper whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductColourMapper whereMagentoColourId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductColourMapper whereProductColour($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductColourMapper whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ProductColourMapper extends Model
{
    use HasFactory;

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_colour',
        'magento_colour_id',
    ];

    public function magentoColour(): BelongsTo
    {
        return $this->belongsTo(MagentoColour::class);

    }
}
